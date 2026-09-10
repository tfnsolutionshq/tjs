<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Submission */
class ProductionQueueResource extends JsonResource
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
            'category' => $this->category,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'has_production_document' => $this->hasProductionDocument(),
            'journal' => $this->when($this->relationLoaded('journal') && $this->journal, fn () => [
                'id' => $this->journal->id,
                'slug' => $this->journal->slug,
                'title' => $this->journal->title,
            ]),
            'author' => $this->when($this->relationLoaded('author') && $this->author, fn () => [
                'id' => $this->author->id,
                'name' => $this->author->name,
            ]),
            'issue' => $this->when($this->relationLoaded('issue') && $this->issue, fn () => [
                'label' => $this->issue->label(),
            ]),
            'production_assignee' => $this->when(
                $this->relationLoaded('productionAssignee') && $this->productionAssignee,
                fn () => [
                    'id' => $this->productionAssignee->id,
                    'name' => $this->productionAssignee->name,
                ]
            ),
        ];
    }
}
