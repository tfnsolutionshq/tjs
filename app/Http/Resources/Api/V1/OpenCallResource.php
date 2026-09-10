<?php

namespace App\Http\Resources\Api\V1;

use App\Support\SafeHtml;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\JournalAnnouncement */
class OpenCallResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'opens_at' => $this->opens_at?->toIso8601String(),
            'closes_at' => $this->closes_at?->toIso8601String(),
            'journal' => $this->when($this->relationLoaded('journal') && $this->journal, fn () => [
                'id' => $this->journal->id,
                'slug' => $this->journal->slug,
                'title' => $this->journal->title,
                'review_type' => $this->journal->review_type,
            ]),
            'issue' => $this->when($this->relationLoaded('issue') && $this->issue, fn () => [
                'id' => $this->issue->id,
                'label' => $this->issue->label(),
            ]),
            'guidelines' => $this->body ? SafeHtml::display($this->body) : null,
        ];
    }
}
