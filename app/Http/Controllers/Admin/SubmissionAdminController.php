<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\ReviewerAssignment;
use App\Models\Submission;
use App\Models\SubmissionTimeline;
use App\Models\User;
use App\Support\Licenses;
use App\Support\ReviewType;
use App\Support\SubmissionStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SubmissionAdminController extends Controller
{
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
        abort_if(
            $submission->blocksEditorialProgress(),
            422,
            'This submission is awaiting payment. Editorial actions are unavailable until the author pays the submission fee.'
        );

        $data = $request->validate([
            'review_type' => ReviewType::requiredRule(),
        ]);

        $submission->update([
            'review_type' => $data['review_type'],
        ]);

        SubmissionTimeline::query()->create([
            'submission_id' => $submission->id,
            'user_id' => $request->user()?->id,
            'event' => 'review_type_updated',
            'metadata' => [
                'review_type' => $data['review_type'],
            ],
        ]);

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
        abort_if(
            $submission->blocksEditorialProgress(),
            422,
            'This submission is awaiting payment. Assign a reviewer after the author pays the submission fee.'
        );

        $data = $request->validate([
            'reviewer_id' => ['required', 'exists:users,id'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:5'],
            'due_at' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($request, $submission, $data) {
            $submission->update([
                'status' => 'under_review',
                'reviewer_id' => $data['reviewer_id'],
            ]);

            ReviewerAssignment::query()->create([
                'submission_id' => $submission->id,
                'reviewer_id' => $data['reviewer_id'],
                'status' => 'assigned',
                'priority' => $data['priority'] ?? 3,
                'due_at' => $data['due_at'] ?? null,
            ]);

            SubmissionTimeline::query()->create([
                'submission_id' => $submission->id,
                'user_id' => $request->user()?->id,
                'event' => 'reviewer_assigned',
                'metadata' => [
                    'reviewer_id' => (int) $data['reviewer_id'],
                ],
            ]);
        });

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
        $submission->loadMissing('productionFiles');

        abort_unless(
            in_array($submission->status, [SubmissionStatus::READY_TO_PUBLISH, SubmissionStatus::APPROVED], true),
            422,
            'Only manuscripts ready for publication can be published.'
        );

        abort_unless(
            $submission->hasProductionDocument(),
            422,
            'A final production document must be uploaded and production completed before this manuscript can be published.'
        );

        $productionFile = $submission->currentProductionFile();
        abort_unless($productionFile, 422, 'Production document is missing.');

        $data = $request->validate([
            'issue_id' => ['required', 'exists:issues,id'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash'],
            'doi' => ['nullable', 'string', 'max:255', \App\Support\Doi::uniqueRule()],
            'license' => ['nullable', 'string', Licenses::rule()],
            'page_range' => ['nullable', 'string', 'max:64'],
            'visibility' => ['nullable', 'in:open,members_only,paid,closed'],
            'authors_text' => ['nullable', 'string'],
            'author_name' => ['nullable', 'string', 'max:255'],
        ]);

        $data['doi'] = \App\Support\Doi::normalize($data['doi'] ?? null);

        $issue = Issue::query()->with('volume')->findOrFail($data['issue_id']);
        abort_unless((int) $issue->volume?->journal_id === (int) $submission->journal_id, 422, 'Issue must belong to the submission journal.');
        if ($submission->issue_id) {
            abort_unless((int) $issue->id === (int) $submission->issue_id, 422, 'Approved submissions must be published to their target issue.');
        }

        $article = DB::transaction(function () use ($request, $submission, $issue, $data, $productionFile) {
            $slugBase = $data['slug'] ?? Str::slug($submission->title);
            $slug = $this->uniqueSlug((int) $submission->journal_id, $slugBase !== '' ? $slugBase : 'article', $submission->id);

            /** @var Article $article */
            $article = Article::query()->updateOrCreate(
                ['submission_id' => $submission->id],
                [
                    'journal_id' => $submission->journal_id,
                    'issue_id' => $issue->id,
                    'author_user_id' => $submission->author_id,
                    'slug' => $slug,
                    'title' => $submission->title,
                    'abstract' => $submission->abstract,
                    'category' => $submission->category,
                    'keywords' => $submission->keywords,
                    'doi' => $data['doi'],
                    'license' => ! empty($data['license']) ? Licenses::normalize($data['license']) : null,
                    'page_range' => $data['page_range'] ?? null,
                    'visibility' => $data['visibility'] ?? 'open',
                    'document_path' => $productionFile->document_path,
                    'document_disk' => $productionFile->document_disk,
                    'status' => 'published',
                    'published_at' => now(),
                ]
            );

            $submission->update(['status' => SubmissionStatus::PUBLISHED]);

            $article->authors()->delete();
            $submission->loadMissing('author');

            $authorsText = trim((string) ($data['authors_text'] ?? ''));
            if ($authorsText !== '') {
                $this->createAuthorsFromText($article, $authorsText);
            } else {
                $name = trim((string) ($data['author_name'] ?? ''));
                if ($name === '') {
                    $name = $submission->author?->name ?: 'Author';
                }

                $article->authors()->create([
                    'name' => $name,
                    'email' => $submission->author?->email,
                    'affiliation' => $submission->author?->affiliation,
                    'orcid' => $submission->author?->orcid,
                    'role' => 'author',
                    'is_corresponding' => true,
                    'sort_order' => 0,
                ]);
            }

            SubmissionTimeline::query()->create([
                'submission_id' => $submission->id,
                'user_id' => request()->user()?->id,
                'event' => 'published_to_issue',
                'metadata' => [
                    'issue_id' => $issue->id,
                    'article_id' => $article->id,
                    'production_file_id' => $productionFile->id,
                ],
            ]);

            return $article;
        });

        app(\App\Services\Doi\DoiDepositService::class)->maybeAutoDeposit($article->fresh(), $request->user());

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

    private function uniqueSlug(int $journalId, string $slug, string $submissionId): string
    {
        $base = $slug;
        $candidate = $base;
        $i = 2;

        while (
            Article::query()
                ->where('journal_id', $journalId)
                ->where('slug', $candidate)
                ->where(function ($q) use ($submissionId) {
                    $q->whereNull('submission_id')
                        ->orWhere('submission_id', '!=', $submissionId);
                })
                ->exists()
        ) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        return $candidate;
    }

    private function createAuthorsFromText(Article $article, string $authorsText): void
    {
        $lines = preg_split('/\r\n|\r|\n/', $authorsText) ?: [];
        $order = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));
            $name = $parts[0] ?? '';
            if ($name === '') {
                continue;
            }

            $article->authors()->create([
                'name' => $name,
                'email' => ($parts[1] ?? '') !== '' ? $parts[1] : null,
                'affiliation' => ($parts[2] ?? '') !== '' ? $parts[2] : null,
                'orcid' => ($parts[3] ?? '') !== '' ? $parts[3] : null,
                'role' => 'author',
                'is_corresponding' => $order === 0,
                'sort_order' => $order,
            ]);

            $order++;
        }
    }
}
