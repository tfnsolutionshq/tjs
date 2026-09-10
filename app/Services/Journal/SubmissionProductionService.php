<?php

namespace App\Services\Journal;

use App\Models\Journal;
use App\Models\Submission;
use App\Models\SubmissionProductionFile;
use App\Models\SubmissionTimeline;
use App\Models\User;
use App\Notifications\ProductionCompleteNotification;
use App\Notifications\ReadyForProductionNotification;
use App\Services\Storage\ArticleStorage;
use App\Support\JournalTeamRoles;
use App\Support\ProductionChecklist;
use App\Support\SubmissionStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

class SubmissionProductionService
{
    public function __construct(
        private ArticleStorage $storage,
    ) {
    }

    public function notifyProductionEditors(Submission $submission): void
    {
        $submission->loadMissing('journal');
        $journal = $submission->journal;
        if (! $journal) {
            return;
        }

        $editors = $this->productionEditorsForJournal($journal);
        if ($editors->isEmpty()) {
            return;
        }

        Notification::send($editors, new ReadyForProductionNotification($submission));
    }

    public function startProduction(Submission $submission, User $actor): Submission
    {
        $this->assertCanWorkOn($submission, $actor);

        if (! in_array($submission->status, [SubmissionStatus::READY_FOR_PRODUCTION, SubmissionStatus::IN_PRODUCTION, SubmissionStatus::READY_TO_PUBLISH], true)) {
            throw new RuntimeException('This submission is not in the production queue.');
        }

        if ($submission->status === SubmissionStatus::READY_TO_PUBLISH) {
            return $submission;
        }

        $submission->update([
            'status' => SubmissionStatus::IN_PRODUCTION,
            'production_assigned_to' => $submission->production_assigned_to ?: $actor->id,
            'production_started_at' => $submission->production_started_at ?: now(),
        ]);

        SubmissionTimeline::query()->create([
            'submission_id' => $submission->id,
            'user_id' => $actor->id,
            'event' => 'production_started',
            'metadata' => [],
        ]);

        return $submission->fresh();
    }

    public function uploadProductionDocument(Submission $submission, User $actor, UploadedFile $file): SubmissionProductionFile
    {
        $this->assertCanWorkOn($submission, $actor);

        if (! in_array($submission->status, SubmissionStatus::productionQueueStatuses(), true)) {
            throw new RuntimeException('This submission is not available for production upload.');
        }

        $submission->loadMissing('journal');
        $journal = $submission->journal;
        if (! $journal) {
            throw new RuntimeException('Submission journal is missing.');
        }

        return DB::transaction(function () use ($submission, $actor, $file, $journal) {
            if ($submission->status === SubmissionStatus::READY_FOR_PRODUCTION) {
                $this->startProduction($submission, $actor);
                $submission->refresh();
            }

            $nextVersion = (int) $submission->productionFiles()->max('version') + 1;
            [$path, $disk] = $this->storage->storeProductionDocument($journal, $submission->id, $nextVersion, $file);

            $submission->productionFiles()->where('is_current', true)->update(['is_current' => false]);

            /** @var SubmissionProductionFile $productionFile */
            $productionFile = $submission->productionFiles()->create([
                'uploaded_by' => $actor->id,
                'version' => $nextVersion,
                'document_path' => $path,
                'document_disk' => $disk,
                'original_filename' => $file->getClientOriginalName(),
                'is_current' => true,
            ]);

            if ($submission->status === SubmissionStatus::READY_TO_PUBLISH) {
                $submission->update([
                    'production_completed_at' => null,
                    'production_completed_by' => null,
                ]);
            }

            $submission->update(['status' => SubmissionStatus::IN_PRODUCTION]);

            SubmissionTimeline::query()->create([
                'submission_id' => $submission->id,
                'user_id' => $actor->id,
                'event' => 'production_document_uploaded',
                'metadata' => [
                    'production_file_id' => $productionFile->id,
                    'version' => $productionFile->version,
                    'original_filename' => $productionFile->original_filename,
                ],
            ]);

            return $productionFile;
        });
    }

    /**
     * @param  array<string, mixed>  $checklistInput
     */
    public function completeProduction(Submission $submission, User $actor, array $checklistInput = []): Submission
    {
        $this->assertCanWorkOn($submission, $actor);

        if (! $submission->hasProductionDocument()) {
            throw new RuntimeException('Upload the final production document before marking production complete.');
        }

        $checklist = ProductionChecklist::normalize($checklistInput);
        $checklist['document_uploaded'] = true;

        $submission->update([
            'status' => SubmissionStatus::READY_TO_PUBLISH,
            'production_completed_at' => now(),
            'production_completed_by' => $actor->id,
            'production_checklist' => $checklist,
        ]);

        SubmissionTimeline::query()->create([
            'submission_id' => $submission->id,
            'user_id' => $actor->id,
            'event' => 'production_completed',
            'metadata' => [
                'production_file_id' => $submission->currentProductionFile()?->id,
            ],
        ]);

        $this->notifyEditorsProductionComplete($submission);

        return $submission->fresh(['productionFiles.uploader', 'journal', 'author']);
    }

    public function assertCanWorkOn(Submission $submission, User $actor): void
    {
        if ($actor->isAdmin()) {
            return;
        }

        $journalId = (int) $submission->journal_id;
        $allowed = $actor->journals()
            ->where('journals.id', $journalId)
            ->wherePivot('role', JournalTeamRoles::PRODUCTION_EDITOR)
            ->exists();

        if (! $allowed) {
            throw new RuntimeException('You are not assigned as a production editor for this journal.');
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function productionEditorsForJournal(Journal $journal)
    {
        return $journal->users()
            ->wherePivot('role', JournalTeamRoles::PRODUCTION_EDITOR)
            ->get();
    }

    private function notifyEditorsProductionComplete(Submission $submission): void
    {
        $submission->loadMissing('journal');
        $journal = $submission->journal;
        if (! $journal) {
            return;
        }

        $recipients = $journal->users()
            ->wherePivotIn('role', JournalTeamRoles::manageRoles())
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new ProductionCompleteNotification($submission));
    }
}
