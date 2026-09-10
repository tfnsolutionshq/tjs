<?php

namespace App\Http\Resources\Api\V1;

use App\Support\ReviewType;
use App\Support\SubmissionStatus;
use Illuminate\Http\Request;

/** @mixin \App\Models\Submission */
class JournalManageSubmissionDetailResource extends JournalManageSubmissionResource
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
        $data['review_type'] = ReviewType::forSubmission($this->resource);
        $data['has_production_document'] = $this->hasProductionDocument();
        $data['can_assign_reviewer'] = ! $this->blocksEditorialProgress();
        $data['can_update_review_type'] = ! $this->blocksEditorialProgress();
        $data['can_publish'] = in_array($this->status, [SubmissionStatus::READY_TO_PUBLISH, SubmissionStatus::APPROVED], true)
            && $this->hasProductionDocument();
        $data['assignments'] = $this->when($this->relationLoaded('assignments'), fn () => $this->assignments->map(fn ($assignment) => [
            'id' => $assignment->id,
            'status' => $assignment->status,
            'priority' => $assignment->priority,
            'due_at' => $assignment->due_at?->toIso8601String(),
            'reviewer' => $assignment->relationLoaded('reviewer') && $assignment->reviewer ? [
                'id' => $assignment->reviewer->id,
                'name' => $assignment->reviewer->name,
                'email' => $assignment->reviewer->email,
            ] : null,
        ])->values());
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
