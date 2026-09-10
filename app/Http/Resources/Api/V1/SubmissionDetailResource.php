<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;

/** @mixin \App\Models\Submission */
class SubmissionDetailResource extends SubmissionResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        $data['abstract'] = $this->abstract ? strip_tags((string) $this->abstract) : null;
        $data['review_comment'] = $this->review_comment;
        $data['rejection_reason'] = $this->rejection_reason;
        $data['reviewed_at'] = $this->reviewed_at?->toIso8601String();
        $data['can_resubmit'] = in_array($this->status, ['revision_requested', 'resubmitted'], true);
        $data['submission_fee'] = $this->when($this->relationLoaded('journalFee') && $this->journalFee, fn () => [
            'id' => $this->journalFee->id,
            'name' => $this->journalFee->name,
            'amount' => (int) $this->journalFee->amount,
            'currency' => strtoupper((string) $this->journalFee->currency),
        ]);
        $data['publication_fee'] = $this->when($this->relationLoaded('publicationJournalFee') && $this->publicationJournalFee, fn () => [
            'id' => $this->publicationJournalFee->id,
            'name' => $this->publicationJournalFee->name,
            'amount' => (int) $this->publicationJournalFee->amount,
            'currency' => strtoupper((string) $this->publicationJournalFee->currency),
        ]);
        $data['timeline'] = $this->when($this->relationLoaded('timelines'), fn () => $this->timelines->map(fn ($entry) => [
            'id' => $entry->id,
            'event' => $entry->event,
            'metadata' => $entry->metadata,
            'created_at' => $entry->created_at?->toIso8601String(),
            'user' => $entry->relationLoaded('user') && $entry->user ? [
                'id' => $entry->user->id,
                'name' => $entry->user->name,
            ] : null,
        ])->values());
        $data['revisions'] = $this->when($this->relationLoaded('revisions'), fn () => $this->revisions->map(fn ($revision) => [
            'id' => $revision->id,
            'revision_number' => $revision->revision_number,
            'notes' => $revision->notes,
            'created_at' => $revision->created_at?->toIso8601String(),
        ])->values());

        return $data;
    }
}
