<?php

namespace App\Services\Journal;

use App\Models\Submission;
use App\Models\SubmissionTimeline;
use App\Notifications\PublicationFeeDueNotification;
use App\Support\SubmissionStatus;

class SubmissionAcceptanceService
{
    public function __construct(
        private JournalFeeResolver $fees,
        private SubmissionProductionService $production,
    ) {
    }

    public function recordAcceptance(Submission $submission, ?int $actorUserId = null): void
    {
        $submission->loadMissing(['author', 'issue.journalFee']);

        $publicationFee = $this->fees->requiredPublicationFeeForIssue($submission->issue_id);

        if ($publicationFee) {
            $submission->update([
                'status' => SubmissionStatus::PUBLICATION_FEE_PENDING,
                'publication_journal_fee_id' => $publicationFee->id,
            ]);

            SubmissionTimeline::query()->create([
                'submission_id' => $submission->id,
                'user_id' => $actorUserId,
                'event' => 'accepted',
                'metadata' => [
                    'publication_journal_fee_id' => $publicationFee->id,
                    'requires_publication_payment' => true,
                ],
            ]);

            if ($submission->author) {
                $submission->author->notify(new PublicationFeeDueNotification($submission->fresh([
                    'journal',
                    'issue.volume',
                    'publicationJournalFee',
                ])));
            }

            return;
        }

        $submission->update(['status' => SubmissionStatus::READY_FOR_PRODUCTION]);

        SubmissionTimeline::query()->create([
            'submission_id' => $submission->id,
            'user_id' => $actorUserId,
            'event' => 'accepted',
            'metadata' => [
                'requires_publication_payment' => false,
            ],
        ]);

        $this->production->notifyProductionEditors($submission->fresh(['journal', 'author']));
    }
}
