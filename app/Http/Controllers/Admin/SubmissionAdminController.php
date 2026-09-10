<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\Submission;
use App\Models\User;
use App\Services\Journal\JournalSubmissionAdminService;
use App\Support\Doi;
use App\Support\Licenses;
use App\Support\ReviewType;
use App\Support\SubmissionStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubmissionAdminController extends Controller
{
    public function __construct(
        private JournalSubmissionAdminService $submissions,
    ) {
    }

    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));
        $status = $request->get('status');
        $status = is_string($status) && $status !== '' ? $status : null;
        $journalId = $request->integer('journal_id') ?: null;
        $perPage = (int) $request->get('per_page', 20);
        if (! in_array($perPage, [10, 20, 50], true)) {
            $perPage = 20;
        }

        $allowedStatuses = [
            'fee_pending',
            'submitted',
            'under_review',
            'revision_requested',
            'resubmitted',
            'publication_fee_pending',
            'ready_for_production',
            'in_production',
            'ready_to_publish',
            'published',
            'rejected',
        ];
        if ($status !== null && ! in_array($status, $allowedStatuses, true)) {
            $status = null;
        }

        $scoped = function () use ($q, $journalId) {
            return Submission::query()
                ->when($journalId, fn ($query) => $query->where('journal_id', $journalId))
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($inner) use ($q) {
                        $inner->where('title', 'like', "%{$q}%")
                            ->orWhere('keywords', 'like', "%{$q}%")
                            ->orWhere('category', 'like', "%{$q}%")
                            ->orWhereHas('author', function ($author) use ($q) {
                                $author->where('name', 'like', "%{$q}%")
                                    ->orWhere('email', 'like', "%{$q}%");
                            });
                    });
                });
        };

        $stats = [
            'total' => $scoped()->count(),
            'fee_pending' => $scoped()->where('status', SubmissionStatus::FEE_PENDING)->count(),
            'submitted' => $scoped()->where('status', 'submitted')->count(),
            'under_review' => $scoped()->where('status', 'under_review')->count(),
            'revision_requested' => $scoped()->where('status', 'revision_requested')->count(),
            'resubmitted' => $scoped()->where('status', 'resubmitted')->count(),
            'approved' => $scoped()->whereIn('status', array_merge(
                \App\Support\SubmissionStatus::productionQueueStatuses(),
                [\App\Support\SubmissionStatus::PUBLICATION_FEE_PENDING]
            ))->count(),
            'rejected' => $scoped()->where('status', 'rejected')->count(),
        ];

        $submissions = $scoped()
            ->with(['journal:id,title,slug', 'author:id,name,email', 'reviewer:id,name'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $journals = Journal::query()->orderBy('title')->get(['id', 'title']);

        return view('admin.submissions.index', compact(
            'submissions',
            'journals',
            'stats',
            'q',
            'status',
            'journalId',
            'perPage',
            'allowedStatuses',
        ));
    }

    public function show(Submission $submission): View
    {
        $submission->load([
            'journal',
            'journalFee',
            'issue.volume',
            'announcement',
            'author',
            'reviewer',
            'publicationJournalFee',
            'productionFiles.uploader',
            'productionAssignee',
            'productionCompleter',
            'assignments.reviewer',
            'timelines.user',
            'revisions.uploader',
            'comments.user',
        ]);

        $reviewers = User::query()
            ->where(function ($q) use ($submission) {
                $q->whereIn('role', ['reviewer', 'editor', 'admin'])
                    ->orWhereHas('journals', fn ($j) => $j
                        ->where('journals.id', $submission->journal_id)
                        ->wherePivot('role', 'reviewer'));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        $issues = Issue::query()
            ->with('volume')
            ->whereHas('volume', fn ($q) => $q->where('journal_id', $submission->journal_id))
            ->orderByDesc('id')
            ->get();

        return view('admin.submissions.show', compact('submission', 'reviewers', 'issues'));
    }

    public function updateReviewType(Request $request, Submission $submission): RedirectResponse
    {
        $data = $request->validate([
            'review_type' => ReviewType::requiredRule(),
        ]);

        $this->submissions->updateReviewType($request->user(), $submission, $data);

        $manageJournal = $request->attributes->get('manage_journal');
        if ($manageJournal instanceof Journal) {
            return redirect()
                ->route('journal.manage.submissions.show', [$manageJournal, $submission])
                ->with('status', 'Review type updated.');
        }

        return redirect()
            ->route('admin.submissions.show', $submission)
            ->with('status', 'Review type updated.');
    }

    public function assignReviewer(Request $request, Submission $submission): RedirectResponse
    {
        $data = $request->validate([
            'reviewer_id' => ['required', 'exists:users,id'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:5'],
            'due_at' => ['nullable', 'date'],
        ]);

        $this->submissions->assignReviewer($request->user(), $submission, $data);

        $manageJournal = $request->attributes->get('manage_journal');
        if ($manageJournal instanceof Journal) {
            return redirect()
                ->route('journal.manage.submissions.show', [$manageJournal, $submission])
                ->with('status', 'Reviewer assigned.');
        }

        return redirect()
            ->route('admin.submissions.show', $submission)
            ->with('status', 'Reviewer assigned.');
    }

    public function publishToIssue(Request $request, Submission $submission): RedirectResponse
    {
        $data = $request->validate([
            'issue_id' => ['required', 'exists:issues,id'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash'],
            'doi' => ['nullable', 'string', 'max:255', Doi::uniqueRule()],
            'license' => ['nullable', 'string', Licenses::rule()],
            'page_range' => ['nullable', 'string', 'max:64'],
            'visibility' => ['nullable', 'in:open,members_only,paid,closed'],
            'authors_text' => ['nullable', 'string'],
            'author_name' => ['nullable', 'string', 'max:255'],
        ]);

        $data['doi'] = Doi::normalize($data['doi'] ?? null);

        $article = $this->submissions->publishToIssue($request->user(), $submission, $data);

        $manageJournal = $request->attributes->get('manage_journal');
        if ($manageJournal instanceof Journal) {
            return redirect()
                ->route('journal.manage.articles.edit', [$manageJournal, $article])
                ->with('status', 'Submission published to issue.');
        }

        return redirect()
            ->route('admin.articles.edit', $article)
            ->with('status', 'Submission published to issue.');
    }
}
