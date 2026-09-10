<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\MembershipPlan */
class MembershipPlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'scope' => $this->scope,
            'price' => [
                'amount' => (int) $this->price_amount,
                'currency' => strtoupper((string) $this->currency),
            ],
            'duration_days' => (int) $this->duration_days,
            'journal' => $this->when(
                $this->scope === 'journal' && $this->relationLoaded('journal') && $this->journal,
                fn () => [
                    'id' => $this->journal->id,
                    'slug' => $this->journal->slug,
                    'title' => $this->journal->title,
                    'is_featured' => (bool) $this->journal->is_featured,
                ]
            ),
        ];
    }
}
