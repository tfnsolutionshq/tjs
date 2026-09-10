<?php

namespace App\Http\Controllers\Api\V1\JournalManage;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\JournalReviewerRequest;
use App\Services\Journal\ReviewerRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewerRequestController extends Controller
{
    public function index(Journal $journal): JsonResponse
    {
        $pending = JournalReviewerRequest::query()
            ->with(['user:id,name,email,affiliation,orcid'])
            ->where('journal_id', $journal->id)
            ->pending()
            ->orderBy('created_at')
            ->get();

        $recent = JournalReviewerRequest::query()
            ->with(['user:id,name,email', 'reviewer:id,name'])
            ->where('journal_id', $journal->id)
            ->whereIn('status', [
                JournalReviewerRequest::STATUS_APPROVED,
                JournalReviewerRequest::STATUS_REJECTED,
                JournalReviewerRequest::STATUS_WITHDRAWN,
            ])
            ->orderByDesc('reviewed_at')
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();

        $stats = [
            'pending' => $pending->count(),
            'approved' => JournalReviewerRequest::query()
                ->where('journal_id', $journal->id)
                ->where('status', JournalReviewerRequest::STATUS_APPROVED)
                ->count(),
            'declined' => JournalReviewerRequest::query()
                ->where('journal_id', $journal->id)
                ->where('status', JournalReviewerRequest::STATUS_REJECTED)
                ->count(),
        ];

        return response()->json([
            'data' => [
                'pending' => $pending->map(fn ($request) => $this->requestPayload($request))->values(),
                'recent' => $recent->map(fn ($request) => $this->requestPayload($request))->values(),
            ],
            'meta' => ['stats' => $stats],
        ]);
    }

    public function approve(
        Request $request,
        Journal $journal,
        JournalReviewerRequest $reviewerRequest,
        ReviewerRequestService $service,
    ): JsonResponse {
        abort_unless((int) $reviewerRequest->journal_id === (int) $journal->id, 404);

        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $service->approve($reviewerRequest, $request->user(), $data['admin_note'] ?? null);
        $reviewerRequest->load('user:id,name,email');

        return response()->json([
            'data' => [
                'message' => $reviewerRequest->user->name.' is now a reviewer for this journal.',
                'request' => $this->requestPayload($reviewerRequest->fresh(['user', 'reviewer'])),
            ],
        ]);
    }

    public function reject(
        Request $request,
        Journal $journal,
        JournalReviewerRequest $reviewerRequest,
        ReviewerRequestService $service,
    ): JsonResponse {
        abort_unless((int) $reviewerRequest->journal_id === (int) $journal->id, 404);

        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $service->reject($reviewerRequest, $request->user(), $data['admin_note'] ?? null);

        return response()->json([
            'data' => [
                'message' => 'Reviewer request declined.',
                'request' => $this->requestPayload($reviewerRequest->fresh(['user', 'reviewer'])),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function requestPayload(JournalReviewerRequest $reviewerRequest): array
    {
        return [
            'id' => $reviewerRequest->id,
            'status' => $reviewerRequest->status,
            'message' => $reviewerRequest->message,
            'admin_note' => $reviewerRequest->admin_note,
            'reviewed_at' => $reviewerRequest->reviewed_at?->toIso8601String(),
            'created_at' => $reviewerRequest->created_at?->toIso8601String(),
            'user' => $reviewerRequest->relationLoaded('user') && $reviewerRequest->user ? [
                'id' => $reviewerRequest->user->id,
                'name' => $reviewerRequest->user->name,
                'email' => $reviewerRequest->user->email,
                'affiliation' => $reviewerRequest->user->affiliation ?? null,
                'orcid' => $reviewerRequest->user->orcid ?? null,
            ] : null,
            'reviewer' => $reviewerRequest->relationLoaded('reviewer') && $reviewerRequest->reviewer ? [
                'id' => $reviewerRequest->reviewer->id,
                'name' => $reviewerRequest->reviewer->name,
            ] : null,
        ];
    }
}
