<?php

namespace App\Http\Controllers\Reviewer;

use App\Http\Controllers\Controller;
use App\Models\ReviewerAssignment;
use App\Models\Submission;
use App\Models\SubmissionTimeline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $assignments = ReviewerAssignment::query()
            ->with(['submission.journal', 'submission.author'])
            ->where('reviewer_id', $request->user()->id)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->orderBy('priority')
            ->orderBy('due_at')
            ->get();

        return view('reviewer.reviews.index', compact('assignments'));
    }

    public function show(Request $request, Submission $submission): View
    {
        $this->assertAssigned($request, $submission);

        $submission->load(['journal', 'author', 'assignments', 'timelines.user', 'revisions']);

        $assignment = $submission->assignments
            ->where('reviewer_id', $request->user()->id)
            ->sortByDesc('id')
            ->first();

        return view('reviewer.reviews.show', compact('submission', 'assignment'));
    }

    public function decide(Request $request, Submission $submission): RedirectResponse
    {
        $this->assertAssigned($request, $submission);

        $data = $request->validate([
            'decision' => ['required', 'in:accept,reject,revision_requested'],
            'comment' => ['nullable', 'string'],
            'rejection_reason' => ['nullable', 'required_if:decision,reject', 'string'],
        ]);

        DB::transaction(function () use ($request, $submission, $data) {
            $updates = [
                'reviewed_at' => now(),
                'review_comment' => $data['comment'] ?? null,
            ];

            if ($data['decision'] === 'accept') {
                $updates['status'] = 'approved';
                $updates['rejection_reason'] = null;
            } elseif ($data['decision'] === 'reject') {
                $updates['status'] = 'rejected';
                $updates['rejection_reason'] = $data['rejection_reason'] ?? $data['comment'] ?? null;
            } else {
                $updates['status'] = 'revision_requested';
                $updates['review_comment'] = $data['comment'] ?? null;
            }

            $submission->update($updates);

            ReviewerAssignment::query()
                ->where('submission_id', $submission->id)
                ->where('reviewer_id', $request->user()->id)
                ->whereIn('status', ['assigned', 'in_progress'])
                ->update(['status' => 'completed']);

            SubmissionTimeline::query()->create([
                'submission_id' => $submission->id,
                'user_id' => $request->user()->id,
                'event' => 'review_decision',
                'metadata' => [
                    'decision' => $data['decision'],
                ],
            ]);
        });

        return redirect()
            ->route('reviewer.reviews.index')
            ->with('status', 'Review decision recorded.');
    }

    private function assertAssigned(Request $request, Submission $submission): void
    {
        $assigned = ReviewerAssignment::query()
            ->where('submission_id', $submission->id)
            ->where('reviewer_id', $request->user()->id)
            ->exists();

        abort_unless(
            $assigned || (int) $submission->reviewer_id === (int) $request->user()->id,
            403
        );
    }
}
