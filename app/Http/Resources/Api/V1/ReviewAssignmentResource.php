<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ReviewerAssignment */
class ReviewAssignmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_at' => $this->due_at?->toIso8601String(),
            'submission' => $this->when($this->relationLoaded('submission') && $this->submission, fn () => [
                'id' => $this->submission->id,
                'title' => $this->submission->title,
                'status' => $this->submission->status,
                'category' => $this->submission->category,
                'updated_at' => $this->submission->updated_at?->toIso8601String(),
                'journal' => $this->submission->relationLoaded('journal') && $this->submission->journal ? [
                    'id' => $this->submission->journal->id,
                    'slug' => $this->submission->journal->slug,
                    'title' => $this->submission->journal->title,
                ] : null,
            ]),
        ];
    }
}
