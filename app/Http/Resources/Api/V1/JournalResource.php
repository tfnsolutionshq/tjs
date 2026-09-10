<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Journal */
class JournalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'issn' => $this->issn,
            'eissn' => $this->eissn,
            'initials' => $this->displayInitials(),
            'is_featured' => (bool) $this->is_featured,
            'logo_url' => $this->logoUrl(),
            'header_image_url' => $this->headerImageUrl(),
            'urls' => [
                'web' => route('journals.show', $this->resource),
                'browse' => route('journals.browse', $this->resource),
            ],
        ];
    }
}
