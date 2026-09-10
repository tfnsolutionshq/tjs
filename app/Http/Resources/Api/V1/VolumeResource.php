<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Volume */
class VolumeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'volume_number' => $this->volume_number,
            'year' => $this->year,
            'title' => $this->title,
            'status' => $this->status,
            'issues_count' => $this->when(isset($this->issues_count), fn () => (int) $this->issues_count),
            'issues' => $this->when($this->relationLoaded('issues'), fn () => $this->issues->map(fn ($issue) => [
                'id' => $issue->id,
                'issue_number' => $issue->issue_number,
                'title' => $issue->title,
                'status' => $issue->status,
                'label' => $issue->label(),
            ])->values()),
        ];
    }
}
