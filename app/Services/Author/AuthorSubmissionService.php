<?php

namespace App\Services\Author;

use App\Models\ReviewerAssignment;
use App\Models\Submission;
use App\Models\SubmissionRevision;
use App\Models\SubmissionTimeline;
use App\Models\User;
use App\Services\Journal\CallForSubmissionService;
use App\Services\Journal\JournalFeeResolver;
use App\Services\Storage\ArticleStorage;
use App\Support\ReviewType;
use App\Support\SafeHtml;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthorSubmissionService
{
    public function __construct(
        private ArticleStorage $storage,
        private CallForSubmissionService $calls,
        private JournalFeeResolver $fees,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $author, array $data, UploadedFile $document): Submission
    {
        $announcement = $this->calls->findOpenCall((int) $data['announcement_id']);
        $journal = $announcement->journal;
        abort_unless($journal && $journal->isListed(), 422, 'Journal is not accepting submissions.');

        $fee = $this->fees->requiredSubmissionFeeForJournal((int) $journal->id);

        return DB::transaction(function () use ($author, $data, $document, $announcement, $journal, $fee) {
            $status = 'submitted';
            if ($fee && (int) $fee->amount > 0) {
                $status = 'fee_pending';
            }

            $submission = new Submission([
                'journal_id' => $journal->id,
                'issue_id' => $announcement->issue_id,
                'announcement_id' => $announcement->id,
                'journal_fee_id' => $fee?->id,
                'author_id' => $author->id,
                'title' => $data['title'],
                'abstract' => SafeHtml::clean($data['abstract'] ?? null),
                'category' => $data['category'] ?? null,
                'keywords' => $data['keywords'] ?? null,
                'status' => $status,
                'review_type' => ReviewType::forJournal($journal),
            ]);
            $submission->id = (string) Str::uuid();

            [$path, $disk] = $this->storage->storeSubmissionDocument(
                $journal,
                $submission->id,
                $document
            );
            $submission->document_path = $path;
            $submission->document_disk = $disk;
            $submission->save();

            if ($status === 'submitted') {
                SubmissionTimeline::query()->create([
                    'submission_id' => $submission->id,
                    'user_id' => $author->id,
                    'event' => 'submitted',
                    'metadata' => [
                        'announcement_id' => $announcement->id,
                        'issue_id' => $announcement->issue_id,
                        'journal_fee_id' => $fee?->id,
                    ],
                ]);
            }

            return $submission->fresh(['journal', 'issue.volume', 'announcement', 'journalFee']);
        });
    }

    public function resubmit(User $author, Submission $submission, UploadedFile $document, ?string $notes = null): Submission
    {
        $this->assertAuthorOwns($author, $submission);

        if (! in_array($submission->status, ['revision_requested', 'resubmitted'], true)) {
            throw ValidationException::withMessages([
                'document' => 'Revisions are only allowed when revision is requested.',
            ]);
        }

        $submission->loadMissing('journal');

        DB::transaction(function () use ($author, $submission, $document, $notes) {
            $previousPath = $submission->document_path;
            $previousDisk = $submission->document_disk;

            [$path, $disk] = $this->storage->storeSubmissionDocument(
                $submission->journal,
                $submission->id,
                $document
            );

            $revisionNumber = (int) $submission->revisions()->max('revision_number');

            if (
                $previousPath
                && ! $submission->revisions()->where('document_path', $previousPath)->exists()
            ) {
                if ($revisionNumber < 1) {
                    SubmissionRevision::query()->create([
                        'submission_id' => $submission->id,
                        'uploaded_by' => $submission->author_id,
                        'revision_number' => 0,
                        'document_path' => $previousPath,
                        'document_disk' => $previousDisk,
                        'notes' => 'Original submission',
                    ]);
                    $revisionNumber = 0;
                }
            }

            $revisionNumber++;

            SubmissionRevision::query()->create([
                'submission_id' => $submission->id,
                'uploaded_by' => $author->id,
                'revision_number' => $revisionNumber,
                'document_path' => $path,
                'document_disk' => $disk,
                'notes' => $notes,
            ]);

            $reviewerId = $submission->reviewer_id;
            if (! $reviewerId) {
                $reviewerId = ReviewerAssignment::query()
                    ->where('submission_id', $submission->id)
                    ->orderByDesc('id')
                    ->value('reviewer_id');
            }

            $submission->update([
                'document_path' => $path,
                'document_disk' => $disk,
                'status' => 'resubmitted',
                'reviewer_id' => $reviewerId,
            ]);

            if ($reviewerId) {
                $this->reopenReviewerAssignment($submission, (int) $reviewerId);
            }

            SubmissionTimeline::query()->create([
                'submission_id' => $submission->id,
                'user_id' => $author->id,
                'event' => 'resubmitted',
                'metadata' => [
                    'revision_number' => $revisionNumber,
                    'reviewer_id' => $reviewerId ? (int) $reviewerId : null,
                ],
            ]);
        });

        return $submission->fresh(['journal', 'issue.volume', 'announcement', 'journalFee', 'timelines.user', 'revisions']);
    }

    public function assertAuthorOwns(User $author, Submission $submission): void
    {
        abort_unless((int) $submission->author_id === (int) $author->id, 403);
    }

    private function reopenReviewerAssignment(Submission $submission, int $reviewerId): void
    {
        $assignment = ReviewerAssignment::query()
            ->where('submission_id', $submission->id)
            ->where('reviewer_id', $reviewerId)
            ->orderByDesc('id')
            ->first();

        if ($assignment) {
            $assignment->update(['status' => 'assigned']);

            return;
        }

        ReviewerAssignment::query()->create([
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewerId,
            'status' => 'assigned',
            'priority' => 3,
        ]);
    }
}
