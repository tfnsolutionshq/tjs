<?php

namespace App\Http\Controllers\JournalManage;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\JournalReviewerRequest;
use App\Services\Journal\ReviewerRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewerRequestController extends Controller
{
    public function index(Journal $journal): View
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

        return view('journal-manage.reviewer-requests.index', compact('journal', 'pending', 'recent', 'stats'));
    }

    public function approve(
        Request $request,
        Journal $journal,
        JournalReviewerRequest $reviewerRequest,
        ReviewerRequestService $service,
    ): RedirectResponse {
        abort_unless((int) $reviewerRequest->journal_id === (int) $journal->id, 404);

        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $service->approve($reviewerRequest, $request->user(), $data['admin_note'] ?? null);

        return back()->with('status', $reviewerRequest->user->name.' is now a reviewer for this journal.');
    }

    public function reject(
        Request $request,
        Journal $journal,
        JournalReviewerRequest $reviewerRequest,
        ReviewerRequestService $service,
    ): RedirectResponse {
        abort_unless((int) $reviewerRequest->journal_id === (int) $journal->id, 404);

        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $service->reject($reviewerRequest, $request->user(), $data['admin_note'] ?? null);

        return back()->with('status', 'Reviewer request declined.');
    }
}
