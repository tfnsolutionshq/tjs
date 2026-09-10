<?php

namespace App\Http\Resources\Api\V1;

use App\Support\AnnouncementType;
use App\Support\SafeHtml;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\JournalAnnouncement */
class JournalAnnouncementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => AnnouncementType::label($this->type),
            'title' => $this->title,
            'summary' => $this->summary,
            'body' => $this->body ? SafeHtml::display($this->body) : null,
            'is_published' => (bool) $this->is_published,
            'opens_at' => $this->opens_at?->toIso8601String(),
            'closes_at' => $this->closes_at?->toIso8601String(),
            'submissions_count' => $this->when(isset($this->submissions_count), fn () => (int) $this->submissions_count),
            'issue' => $this->when($this->relationLoaded('issue') && $this->issue, fn () => [
                'id' => $this->issue->id,
                'label' => $this->issue->label(),
            ]),
            'creator' => $this->when($this->relationLoaded('creator') && $this->creator, fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
