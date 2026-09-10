<?php

namespace App\Http\Controllers\Api\V1\JournalManage;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ArticleSummaryResource;
use App\Http\Resources\Api\V1\JournalManageSubmissionDetailResource;
use App\Http\Resources\Api\V1\JournalManageSubmissionResource;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\Submission;
use App\Models\User;
use App\Services\Journal\JournalSubmissionAdminService;
use App\Support\Doi;
use App\Support\Licenses;
use App\Support\ReviewType;
use App\Support\SubmissionStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    public function __construct(
        private JournalSubmissionAdminService $submissions,
    ) {
    }

    public function index(Request $request, Journal $journal): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));
        $status = $request->get('status');
        $status = is_string($status) && $status !== '' ? $status : null;

        $allowedStatuses = [
            SubmissionStatus::FEE_PENDING,
            'submitted',
            'under_review',
            'revision_requested',
            'resubmitted',
            SubmissionStatus::PUBLICATION_FEE_PENDING,
            SubmissionStatus::READY_FOR_PRODUCTION,
            SubmissionStatus::IN_PRODUCTION,
            SubmissionStatus::READY_TO_PUBLISH,
            SubmissionStatus::PUBLISHED,
            'rejected',
        ];
        if ($status !== null && ! in_array($status, $allowedStatuses, true)) {
            $status = null;
        }

        $scoped = fn () => Submission::query()
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

        $stats = [
            'total' => $scoped()->count(),
            'fee_pending' => $scoped()->where('status', SubmissionStatus::FEE_PENDING)->count(),
            'submitted' => $scoped()->where('status', 'submitted')->count(),
            'under_review' => $scoped()->where('status', 'under_review')->count(),
            'revision_requested' => $scoped()->where('status', 'revision_requested')->count(),
            'resubmitted' => $scoped()->where('status', 'resubmitted')->count(),
            'approved' => $scoped()->whereIn('status', array_merge(
                SubmissionStatus::productionQueueStatuses(),
                [SubmissionStatus::PUBLICATION_FEE_PENDING]
            ))->count(),
            'rejected' => $scoped()->where('status', 'rejected')->count(),
        ];

        $submissions = $scoped()
            ->with(['author:id,name,email', 'reviewer:id,name'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        return JournalManageSubmissionResource::collection($submissions)
            ->additional(['meta' => ['stats' => $stats]])
            ->response();
    }

    public function formOptions(Journal $journal): JsonResponse
    {
        $reviewers = User::query()
            ->where(function ($q) use ($journal) {
                $q->whereIn('role', ['reviewer', 'editor', 'admin'])
                    ->orWhereHas('journals', fn ($j) => $j
                        ->where('journals.id', $journal->id)
                        ->wherePivot('role', 'reviewer'));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        $issues = Issue::query()
            ->with('volume')
            ->whereHas('volume', fn ($q) => $q->where('journal_id', $journal->id))
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => [
                'reviewers' => $reviewers->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ])->values(),
                'issues' => $issues->map(fn ($issue) => [
                    'id' => $issue->id,
                    'label' => $issue->label().($issue->title ? ' — '.$issue->title : ''),
                    'status' => $issue->status,
                ])->values(),
                'review_types' => collect(ReviewType::all())->map(fn ($type) => [
                    'value' => $type,
                    'label' => ReviewType::label($type),
                ])->values(),
                'visibility_options' => ['open', 'members_only', 'paid', 'closed'],
            ],
        ]);
    }

    public function show(Journal $journal, Submission $submission): JsonResponse
    {
        $this->assertJournalSubmission($journal, $submission);

        $submission->load([
            'journal',
            'journalFee',
            'issue.volume',
            'announcement',
            'author',
            'reviewer',
            'publicationJournalFee',
            'assignments.reviewer',
            'timelines.user',
            'revisions.uploader',
        ]);

        return response()->json([
            'data' => new JournalManageSubmissionDetailResource($submission),
        ]);
    }

    public function assignReviewer(Request $request, Journal $journal, Submission $submission): JsonResponse
    {
        $this->assertJournalSubmission($journal, $submission);

        $data = $request->validate([
            'reviewer_id' => ['required', 'exists:users,id'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:5'],
            'due_at' => ['nullable', 'date'],
        ]);

        $submission = $this->submissions->assignReviewer($request->user(), $submission, $data);

        $submission->load([
            'journal', 'author', 'reviewer', 'assignments.reviewer', 'timelines.user',
        ]);

        return response()->json([
            'data' => [
                'message' => 'Reviewer assigned.',
                'submission' => new JournalManageSubmissionDetailResource($submission),
            ],
        ]);
    }

    public function updateReviewType(Request $request, Journal $journal, Submission $submission): JsonResponse
    {
        $this->assertJournalSubmission($journal, $submission);

        $data = $request->validate([
            'review_type' => ReviewType::requiredRule(),
        ]);

        $submission = $this->submissions->updateReviewType($request->user(), $submission, $data);

        $submission->load([
            'journal', 'author', 'reviewer', 'assignments.reviewer', 'timelines.user',
        ]);

        return response()->json([
            'data' => [
                'message' => 'Review type updated.',
                'submission' => new JournalManageSubmissionDetailResource($submission),
            ],
        ]);
    }

    public function publish(Request $request, Journal $journal, Submission $submission): JsonResponse
    {
        $this->assertJournalSubmission($journal, $submission);

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
        $article->load(['journal', 'authors', 'issue.volume']);

        return response()->json([
            'data' => [
                'message' => 'Submission published to issue.',
                'article' => new ArticleSummaryResource($article),
                'submission' => new JournalManageSubmissionDetailResource($submission->fresh([
                    'journal', 'author', 'reviewer', 'assignments.reviewer', 'timelines.user',
                ])),
            ],
        ]);
    }

    private function assertJournalSubmission(Journal $journal, Submission $submission): void
    {
        abort_unless((int) $submission->journal_id === (int) $journal->id, 404);
    }
}
