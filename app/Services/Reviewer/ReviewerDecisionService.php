<?php

namespace App\Services\Reviewer;

use App\Models\ReviewerAssignment;
use App\Models\Submission;
use App\Models\SubmissionTimeline;
use App\Models\User;
use App\Services\Journal\SubmissionAcceptanceService;
use Illuminate\Support\Facades\DB;

class ReviewerDecisionService
{
    public function __construct(
        private SubmissionAcceptanceService $acceptance,
    ) {
    }

    public function assertAssigned(User $user, Submission $submission): void
    {
        abort_if(
            $submission->blocksEditorialProgress(),
            422,
            'This submission is awaiting payment and is not available for review yet.'
        );

        $assigned = ReviewerAssignment::query()
            ->where('submission_id', $submission->id)
            ->where('reviewer_id', $user->id)
            ->exists();

        abort_unless(
            $assigned || (int) $submission->reviewer_id === (int) $user->id,
            403
        );
    }

    public function assertActiveAssignment(User $user, Submission $submission): void
    {
        $active = ReviewerAssignment::query()
            ->where('submission_id', $submission->id)
            ->where('reviewer_id', $user->id)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->exists();

        abort_unless($active, 422, 'This assignment is not awaiting a decision.');
    }

    /**
     * @param  array{decision: string, comment?: string|null, rejection_reason?: string|null}  $data
     */
    public function decide(User $reviewer, Submission $submission, array $data): Submission
    {
        $this->assertAssigned($reviewer, $submission);
        $this->assertActiveAssignment($reviewer, $submission);

        DB::transaction(function () use ($reviewer, $submission, $data) {
            $updates = [
                'reviewed_at' => now(),
                'review_comment' => $data['comment'] ?? null,
            ];

            if ($data['decision'] === 'accept') {
                $updates['rejection_reason'] = null;
            } elseif ($data['decision'] === 'reject') {
                $updates['status'] = 'rejected';
                $updates['rejection_reason'] = $data['rejection_reason'] ?? $data['comment'] ?? null;
            } else {
                $updates['status'] = 'revision_requested';
                $updates['review_comment'] = $data['comment'] ?? null;
            }

            $submission->update($updates);

            if ($data['decision'] === 'accept') {
                $this->acceptance->recordAcceptance($submission, $reviewer->id);
            }

            ReviewerAssignment::query()
                ->where('submission_id', $submission->id)
                ->where('reviewer_id', $reviewer->id)
                ->whereIn('status', ['assigned', 'in_progress'])
                ->update(['status' => 'completed']);

            SubmissionTimeline::query()->create([
                'submission_id' => $submission->id,
                'user_id' => $reviewer->id,
                'event' => 'review_decision',
                'metadata' => [
                    'decision' => $data['decision'],
                    'comment' => $data['comment'] ?? null,
                    'rejection_reason' => $updates['rejection_reason'] ?? null,
                ],
            ]);
        });

        return $submission->fresh([
            'journal',
            'issue.volume',
            'announcement',
            'assignments.reviewer',
            'timelines.user',
            'revisions.uploader',
        ]);
    }
}
