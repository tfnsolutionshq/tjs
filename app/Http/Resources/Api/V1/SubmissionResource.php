<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Submission */
class SubmissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status,
            'review_type' => $this->review_type,
            'category' => $this->category,
            'keywords' => $this->keywords,
            'updated_at' => $this->updated_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'requires_submission_fee' => $this->requiresFeePayment(),
            'requires_publication_fee' => $this->requiresPublicationFeePayment(),
            'journal' => $this->when($this->relationLoaded('journal') && $this->journal, fn () => [
                'id' => $this->journal->id,
                'slug' => $this->journal->slug,
                'title' => $this->journal->title,
            ]),
            'issue' => $this->when($this->relationLoaded('issue') && $this->issue, fn () => [
                'label' => $this->issue->label(),
            ]),
            'announcement' => $this->when($this->relationLoaded('announcement') && $this->announcement, fn () => [
                'id' => $this->announcement->id,
                'title' => $this->announcement->title,
            ]),
        ];
    }
}
