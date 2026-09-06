<?php

namespace App\Services\Journal;

use App\Models\Journal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class JournalPickerService
{
    public const PER_PAGE = 12;

    /**
     * @return Collection<int, Journal>
     */
    public function featured(): Collection
    {
        return $this->baseQuery()
            ->where('is_featured', true)
            ->get();
    }

    public function otherCount(): int
    {
        return $this->baseQuery()
            ->where('is_featured', false)
            ->count();
    }

    public function totalCount(): int
    {
        return $this->baseQuery()->count();
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int|bool|null>}
     */
    public function search(
        string $action,
        ?string $redirect,
        ?string $query,
        int $page,
        bool $featuredOnly = false,
        bool $nonFeaturedOnly = false,
    ): array {
        $builder = $this->baseQuery();

        if ($featuredOnly) {
            $builder->where('is_featured', true);
        } elseif ($nonFeaturedOnly) {
            $builder->where('is_featured', false);
        }

        $needle = trim($query ?? '');
        if ($needle !== '') {
            $builder->where(function (Builder $inner) use ($needle) {
                $like = '%'.$needle.'%';
                $inner->where('title', 'like', $like)
                    ->orWhere('subtitle', 'like', $like)
                    ->orWhere('slug', 'like', $like)
                    ->orWhere('initials', 'like', $like);
            });
        }

        /** @var LengthAwarePaginator<int, Journal> $paginator */
        $paginator = $builder->paginate(self::PER_PAGE, ['*'], 'page', max(1, $page));

        return [
            'data' => $paginator->getCollection()
                ->map(fn (Journal $journal) => $this->toPickerItem($journal, $action, $redirect))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toPickerItem(Journal $journal, string $action, ?string $redirect): array
    {
        return [
            'slug' => $journal->slug,
            'title' => $journal->title,
            'subtitle' => (string) ($journal->subtitle ?? ''),
            'initials' => $journal->displayInitials(),
            'letter' => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $journal->title) ?: '#', 0, 1)),
            'featured' => (bool) $journal->is_featured,
            'logo' => $journal->logoUrl(),
            'href' => $action === 'register'
                ? route('journals.register', array_filter(['journal' => $journal, 'redirect' => $redirect]))
                : route('journals.login', array_filter(['journal' => $journal, 'redirect' => $redirect])),
        ];
    }

    /**
     * @return Builder<Journal>
     */
    private function baseQuery(): Builder
    {
        return Journal::query()
            ->listed()
            ->orderByDesc('is_featured')
            ->orderBy('title');
    }
}
