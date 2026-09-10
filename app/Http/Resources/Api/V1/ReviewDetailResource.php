<?php

namespace App\Http\Resources\Api\V1;

use App\Support\ReviewType;
use Illuminate\Http\Request;

/** @mixin \App\Models\Submission */
class ReviewDetailResource extends SubmissionDetailResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        $reviewType = ReviewType::forSubmission($this->resource);

        if (ReviewType::isOpen($reviewType) && $this->relationLoaded('author') && $this->author) {
            $data['author'] = [
                'id' => $this->author->id,
                'name' => $this->author->name,
            ];
        }

        $assignment = null;
        $canDecide = false;

        if ($this->relationLoaded('assignments') && $request->user()) {
            $assignment = $this->assignments
                ->where('reviewer_id', $request->user()->id)
                ->sortByDesc('id')
                ->first();

            $canDecide = $assignment && in_array($assignment->status, ['assigned', 'in_progress'], true);
        }

        $data['review_type'] = $reviewType;
        $data['review_type_label'] = ReviewType::label($reviewType);
        $data['can_decide'] = $canDecide;
        $data['has_document'] = (bool) $this->document_path;
        $data['assignment'] = $assignment ? [
            'id' => $assignment->id,
            'status' => $assignment->status,
            'priority' => $assignment->priority,
            'due_at' => $assignment->due_at?->toIso8601String(),
        ] : null;

        if ($this->relationLoaded('revisions')) {
            $data['revisions'] = $this->revisions->map(fn ($revision) => [
                'id' => $revision->id,
                'revision_number' => $revision->revision_number,
                'notes' => $revision->notes,
                'created_at' => $revision->created_at?->toIso8601String(),
                'uploader' => $revision->relationLoaded('uploader') && $revision->uploader ? [
                    'id' => $revision->uploader->id,
                    'name' => $revision->uploader->name,
                ] : null,
            ])->values();
        }

        return $data;
    }
}
