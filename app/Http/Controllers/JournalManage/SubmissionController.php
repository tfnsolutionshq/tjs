<?php

namespace App\Http\Controllers\JournalManage;

use App\Http\Controllers\Admin\SubmissionAdminController as PlatformSubmissionAdminController;
use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    public function __construct(
        private PlatformSubmissionAdminController $platform,
    ) {
    }

    public function index(Request $request, Journal $journal): View
    {
        $q = trim((string) $request->get('q', ''));
        $status = $request->get('status');
        $status = is_string($status) && $status !== '' ? $status : null;
        $perPage = (int) $request->get('per_page', 20);
        if (! in_array($perPage, [10, 20, 50], true)) {
            $perPage = 20;
        }

        $allowedStatuses = [
            'submitted',
            'under_review',
            'revision_requested',
            'resubmitted',
            'approved',
            'rejected',
        ];
        if ($status !== null && ! in_array($status, $allowedStatuses, true)) {
            $status = null;
        }

        $scoped = function () use ($q, $journal) {
            return Submission::query()
                ->where('journal_id', $journal->id)
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
            'submitted' => $scoped()->where('status', 'submitted')->count(),
            'under_review' => $scoped()->where('status', 'under_review')->count(),
            'revision_requested' => $scoped()->where('status', 'revision_requested')->count(),
            'resubmitted' => $scoped()->where('status', 'resubmitted')->count(),
            'approved' => $scoped()->where('status', 'approved')->count(),
            'rejected' => $scoped()->where('status', 'rejected')->count(),
        ];

        $submissions = $scoped()
            ->with(['journal:id,title,slug', 'author:id,name,email', 'reviewer:id,name'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $journals = collect([$journal]);
        $journalId = $journal->id;
        $manageJournal = $journal;

        return view('admin.submissions.index', compact(
            'submissions',
            'journals',
            'stats',
            'q',
            'status',
            'journalId',
            'perPage',
            'allowedStatuses',
            'manageJournal',
            'journal',
        ));
    }

    public function show(Journal $journal, Submission $submission): View
    {
        abort_unless((int) $submission->journal_id === (int) $journal->id, 404);

        $submission->load([
            'journal',
            'issue.volume',
            'announcement',
            'author',
            'reviewer',
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

        $manageJournal = $journal;

        return view('admin.submissions.show', compact(
            'submission',
            'reviewers',
            'issues',
            'manageJournal',
            'journal',
        ));
    }

    public function assignReviewer(Request $request, Journal $journal, Submission $submission): RedirectResponse
    {
        abort_unless((int) $submission->journal_id === (int) $journal->id, 404);
        $request->attributes->set('manage_journal', $journal);

        return $this->platform->assignReviewer($request, $submission);
    }

    public function updateReviewType(Request $request, Journal $journal, Submission $submission): RedirectResponse
    {
        abort_unless((int) $submission->journal_id === (int) $journal->id, 404);
        $request->attributes->set('manage_journal', $journal);

        return $this->platform->updateReviewType($request, $submission);
    }

    public function publishToIssue(Request $request, Journal $journal, Submission $submission): RedirectResponse
    {
        abort_unless((int) $submission->journal_id === (int) $journal->id, 404);
        $request->attributes->set('manage_journal', $journal);

        return $this->platform->publishToIssue($request, $submission);
    }
}
