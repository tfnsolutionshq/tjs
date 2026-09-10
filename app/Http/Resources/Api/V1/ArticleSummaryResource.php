<?php

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use App\Services\Access\ArticleAccessResolver;
use App\Support\Api\ArticleAccessPayload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Article */
class ArticleSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('journal', 'authors', 'issue.volume');

        /** @var User|null $user */
        $user = $request->user();
        $access = app(ArticleAccessResolver::class);

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'abstract' => $this->abstract ? strip_tags((string) $this->abstract) : null,
            'visibility' => $this->visibility,
            'published_at' => $this->published_at?->toIso8601String(),
            'doi' => $this->doi,
            'journal' => $this->when($this->relationLoaded('journal') && $this->journal, fn () => [
                'id' => $this->journal->id,
                'slug' => $this->journal->slug,
                'title' => $this->journal->title,
            ]),
            'authors' => $this->when($this->relationLoaded('authors'), fn () => $this->authors->map(fn ($author) => [
                'name' => $author->name,
                'is_corresponding' => (bool) $author->is_corresponding,
            ])->values()),
            'issue' => $this->when($this->relationLoaded('issue') && $this->issue, fn () => [
                'label' => $this->issue->label(),
            ]),
            'access' => ArticleAccessPayload::for($user, $this->resource, $access),
            'urls' => [
                'web' => route('journals.articles.show', [$this->journal, $this->resource]),
            ],
        ];
    }
}
