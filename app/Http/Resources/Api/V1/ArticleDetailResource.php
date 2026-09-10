<?php

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use App\Services\Access\ArticleAccessResolver;
use App\Services\Seo\ApaCitation;
use App\Support\Api\ArticleAccessPayload;
use Illuminate\Http\Request;

/** @mixin \App\Models\Article */
class ArticleDetailResource extends ArticleSummaryResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();
        $access = app(ArticleAccessResolver::class);

        return array_merge(parent::toArray($request), [
            'keywords' => $this->keywords,
            'license' => $this->license,
            'page_range' => $this->page_range,
            'categories' => $this->when($this->relationLoaded('categories'), fn () => $this->categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])->values()),
            'issue' => $this->when($this->relationLoaded('issue') && $this->issue, fn () => [
                'id' => $this->issue->id,
                'label' => $this->issue->label(),
                'volume_number' => $this->issue->volume?->volume_number,
                'year' => $this->issue->volume?->year,
            ]),
            'authors' => $this->when($this->relationLoaded('authors'), fn () => $this->authors->map(fn ($author) => [
                'name' => $author->name,
                'affiliation' => $author->affiliation,
                'orcid' => $author->orcid,
                'is_corresponding' => (bool) $author->is_corresponding,
            ])->values()),
            'citation' => [
                'apa' => app(ApaCitation::class)->build($this->resource),
            ],
            'access' => ArticleAccessPayload::for($user, $this->resource, $access),
            'urls' => [
                'web' => route('journals.articles.show', [$this->journal, $this->resource]),
                'pdf' => route('journals.articles.pdf', [$this->journal, $this->slug]),
            ],
        ]);
    }
}
