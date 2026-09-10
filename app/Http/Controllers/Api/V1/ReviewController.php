<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ReviewAssignmentResource;
use App\Http\Resources\Api\V1\ReviewDetailResource;
use App\Models\ReviewerAssignment;
use App\Models\Submission;
use App\Models\SubmissionRevision;
use App\Services\Reviewer\ReviewerDecisionService;
use App\Services\Storage\HybridDisk;
use App\Support\ReviewType;
use App\Support\SubmissionStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReviewController extends Controller
{
    public function __construct(
        private ReviewerDecisionService $decisions,
        private HybridDisk $disks,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $base = ReviewerAssignment::query()
            ->where('reviewer_id', $request->user()->id)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->whereHas('submission', fn ($q) => $q->where('status', '!=', SubmissionStatus::FEE_PENDING));

        $stats = [
            'pending' => (clone $base)->count(),
        ];

        $assignments = (clone $base)
            ->with(['submission.journal'])
            ->orderBy('priority')
            ->orderBy('due_at')
            ->paginate($request->integer('per_page', 12))
            ->withQueryString();

        return ReviewAssignmentResource::collection($assignments)
            ->additional(['meta' => ['stats' => $stats]])
            ->response();
    }

    public function show(Request $request, Submission $submission): JsonResponse
    {
        $this->decisions->assertAssigned($request->user(), $submission);

        $submission->load([
            'journal',
            'assignments.reviewer',
            'timelines.user',
            'revisions.uploader',
            'issue.volume',
            'announcement',
        ]);

        if (ReviewType::isOpen(ReviewType::forSubmission($submission))) {
            $submission->load('author');
        }

        return response()->json([
            'data' => new ReviewDetailResource($submission),
        ]);
    }

    public function decide(Request $request, Submission $submission): JsonResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:accept,reject,revision_requested'],
            'comment' => ['nullable', 'string'],
            'rejection_reason' => ['nullable', 'required_if:decision,reject', 'string'],
        ]);

        $submission = $this->decisions->decide($request->user(), $submission, $data);

        if (ReviewType::isOpen(ReviewType::forSubmission($submission))) {
            $submission->load('author');
        }

        return response()->json([
            'data' => [
                'message' => 'Review decision recorded.',
                'submission' => new ReviewDetailResource($submission),
            ],
        ]);
    }

    public function downloadDocument(Request $request, Submission $submission): StreamedResponse
    {
        $this->decisions->assertAssigned($request->user(), $submission);
        abort_unless($submission->document_path, 404);

        return $this->disks->download(
            $submission->document_path,
            HybridDisk::KIND_DOCUMENTS,
            $submission->document_disk,
            basename($submission->document_path)
        );
    }

    public function downloadRevision(
        Request $request,
        Submission $submission,
        SubmissionRevision $revision,
    ): StreamedResponse {
        $this->decisions->assertAssigned($request->user(), $submission);
        abort_unless((string) $revision->submission_id === (string) $submission->id, 404);
        abort_unless($revision->document_path, 404);

        return $this->disks->download(
            $revision->document_path,
            HybridDisk::KIND_DOCUMENTS,
            $revision->document_disk,
            basename($revision->document_path)
        );
    }
}
