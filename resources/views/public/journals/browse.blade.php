@extends('layouts.journal')

@section('title', 'Browse | '.$journal->title)
@section('meta_description', 'Browse all published articles from '.$journal->title.' by date, title, and author.')
@section('canonical', route('journals.browse', $journal))

@section('seo')
    @include('seo.page-meta', ['meta' => app(\App\Services\Seo\PageMeta::class)->journal(
        $journal,
        'Browse | '.$journal->title,
        'Browse all published articles from '.$journal->title.'.',
        route('journals.browse', $journal)
    )])
@endsection

@section('content')
@include('public.journals.partials.page-styles')

@php
    $year = request('year');
    $q = request('q');
    $grouped = $articles->getCollection()->groupBy(fn ($a) => optional($a->published_at)->format('Y') ?: 'Undated');
@endphp

<style>
    .jb-toolbar {
        display: flex; flex-direction: column; gap: .75rem;
        padding: .9rem 1rem; margin-bottom: 1.25rem;
        background: var(--j-surface);
        border: 1px solid color-mix(in srgb, var(--j-text) 8%, transparent);
        border-radius: .95rem;
    }
    @media (min-width: 800px) {
        .jb-toolbar { flex-direction: row; align-items: center; }
    }
    .jb-search {
        flex: 1; min-width: 0; display: flex; align-items: center; gap: .55rem;
        border: 1px solid color-mix(in srgb, var(--j-text) 12%, transparent);
        border-radius: .7rem; background: color-mix(in srgb, var(--j-page-bg) 70%, white);
        padding: .65rem .85rem;
    }
    .jb-search:focus-within {
        border-color: color-mix(in srgb, var(--j-accent) 55%, transparent);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--j-accent) 16%, transparent);
        background: #fff;
    }
    .jb-search input {
        width: 100%; border: 0; outline: 0; background: transparent;
        font: inherit; font-size: .9rem; color: var(--j-text);
    }
    .jb-select, .jb-submit {
        border: 1px solid color-mix(in srgb, var(--j-text) 12%, transparent);
        border-radius: .7rem; background: #fff; font: inherit; font-size: .84rem; font-weight: 600;
        color: var(--j-text); padding: .65rem .8rem;
    }
    .jb-submit {
        background: var(--j-accent); color: #fff; border-color: transparent; cursor: pointer;
    }
    .jb-submit:hover { filter: brightness(1.05); }
    .jb-year-chip {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .35rem .7rem; border-radius: 999px; font-size: .74rem; font-weight: 700;
        text-decoration: none; color: var(--j-muted);
        border: 1px solid color-mix(in srgb, var(--j-text) 10%, transparent);
        background: var(--j-surface);
    }
    .jb-year-chip.is-active, .jb-year-chip:hover {
        color: var(--j-accent);
        border-color: color-mix(in srgb, var(--j-accent) 35%, transparent);
        background: color-mix(in srgb, var(--j-accent) 10%, white);
    }
    .jb-item {
        display: grid; gap: .85rem; padding: 1.15rem 1.2rem;
        transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
    }
    @media (min-width: 720px) {
        .jb-item { grid-template-columns: 4.5rem minmax(0, 1fr) auto; align-items: start; }
    }
    .jb-item:hover {
        transform: translateY(-1px);
        border-color: color-mix(in srgb, var(--j-accent) 28%, transparent);
        box-shadow: 0 12px 28px color-mix(in srgb, var(--j-text) 6%, transparent);
    }
    .jb-num {
        font-family: var(--j-font-display);
        font-size: 1.35rem; font-weight: 700; line-height: 1;
        color: color-mix(in srgb, var(--j-text) 28%, transparent);
    }
    .jb-actions { display: flex; flex-wrap: wrap; gap: .45rem; }
    @media (min-width: 720px) {
        .jb-actions { flex-direction: column; align-items: stretch; min-width: 7.5rem; }
        .jb-actions .j-btn, .jb-actions .j-btn-ghost { justify-content: center; padding: .55rem .75rem; font-size: .8rem; }
    }
    .jb-grid {
        display: grid;
        gap: 1rem;
        grid-template-columns: minmax(0, 1fr);
    }
    @media (min-width: 640px) {
        .jb-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (min-width: 1024px) {
        .jb-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    .jb-grid.is-list { grid-template-columns: minmax(0, 1fr); }
    .jb-card {
        display: flex;
        flex-direction: column;
        gap: .75rem;
        height: 100%;
        padding: 1.1rem 1.15rem 1.15rem;
    }
    .jb-card__title {
        font-family: var(--j-font-display);
        font-size: 1.05rem;
        font-weight: 700;
        line-height: 1.35;
        color: var(--j-text);
    }
    .jb-card__title a { color: inherit; text-decoration: none; }
    .jb-card__title a:hover { color: var(--j-accent); }
</style>

<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="j-section-title">Browse articles</h1>
            <p class="j-meta mt-2">
                {{ number_format($totalPublished) }} published article{{ $totalPublished === 1 ? '' : 's' }}
                · chronological listing for readers and indexing
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('journals.archive', $journal) }}" class="j-btn-ghost text-sm">Archives</a>
            <a href="{{ route('author.submissions.create') }}" class="j-btn text-sm">Submit manuscript</a>
        </div>
    </div>

    <form method="GET" action="{{ route('journals.browse', $journal) }}" class="jb-toolbar">
        <div class="jb-search">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" style="color:var(--j-muted);flex-shrink:0"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M20 20l-3.5-3.5"/></svg>
            <input type="search" name="q" value="{{ $q }}" placeholder="Search title, author, keywords…">
        </div>
        <select name="year" class="jb-select" onchange="this.form.submit()">
            <option value="">All years</option>
            @foreach($years as $y)
                <option value="{{ $y }}" @selected((string) $year === (string) $y)>{{ $y }}</option>
            @endforeach
        </select>
        <button type="submit" class="jb-submit">Search</button>
    </form>

    @if($years->isNotEmpty())
        <div class="mb-6 flex flex-wrap gap-2">
            <a
                href="{{ route('journals.browse', $journal) }}{{ $q ? '?q='.urlencode($q) : '' }}"
                class="jb-year-chip {{ ! $year ? 'is-active' : '' }}"
            >All</a>
            @foreach($years as $y)
                <a
                    href="{{ route('journals.browse', $journal) }}?{{ http_build_query(array_filter(['q' => $q, 'year' => $y])) }}"
                    class="jb-year-chip {{ (string) $year === (string) $y ? 'is-active' : '' }}"
                >{{ $y }}</a>
            @endforeach
        </div>
    @endif

    @if($articles->total() > 0)
        @include('public.journals.partials.list-controls', ['paginator' => $articles, 'layout' => $layout])
    @endif

    @if($articles->isEmpty())
        <div class="j-card p-8 text-center">
            <p class="j-display text-lg font-semibold" style="color: var(--j-text)">No articles found</p>
            <p class="j-meta mt-2 mx-auto max-w-md">
                @if($q || $year)
                    Try a different search term or clear the year filter.
                @else
                    Published articles will appear here once issues go live.
                @endif
            </p>
            @if($q || $year)
                <a href="{{ route('journals.browse', $journal) }}" class="j-btn-ghost mt-5 inline-flex text-sm">Clear filters</a>
            @endif
        </div>
    @else
        @if(($layout ?? 'list') === 'grid')
            <div @class(['jb-grid', 'is-list' => false])>
                @foreach($articles as $article)
                    <article class="j-card jb-card">
                        <div class="flex flex-wrap gap-2">
                            @foreach($article->categories as $cat)
                                <span class="j-badge">{{ $cat->name }}</span>
                            @endforeach
                            @if($article->categories->isEmpty() && $article->category)
                                <span class="j-badge">{{ $article->category }}</span>
                            @endif
                        </div>
                        <h3 class="jb-card__title">
                            <a href="{{ route('journals.articles.show', [$journal, $article]) }}">{{ $article->title }}</a>
                        </h3>
                        <p class="j-meta">
                            {{ $article->authors->pluck('name')->join(', ') ?: 'Author TBA' }}
                            @if($article->published_at)
                                <span aria-hidden="true"> · </span>
                                <time datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->format('M j, Y') }}</time>
                            @endif
                        </p>
                        @if($article->abstract)
                            <p class="text-sm leading-relaxed line-clamp-3" style="color: var(--j-muted)">
                                {{ Str::limit(strip_tags($article->abstract), 160) }}
                            </p>
                        @endif
                        <div class="mt-auto flex flex-wrap gap-2 pt-1">
                            <a class="j-btn text-sm" href="{{ route('journals.articles.show', [$journal, $article]) }}">Read</a>
                            @if($article->visibility === 'open')
                                <a class="j-btn-ghost text-sm" href="{{ route('journals.articles.pdf', [$journal, $article->slug]) }}">PDF</a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @else
        @php $displayNum = $articles->firstItem() ?? 1; @endphp
        @foreach($grouped as $groupYear => $groupArticles)
            <section class="mb-8">
                <div class="mb-3 flex items-baseline justify-between gap-3 border-b pb-2" style="border-color: color-mix(in srgb, var(--j-text) 10%, transparent)">
                    <h2 class="j-display text-xl font-semibold">{{ $groupYear }}</h2>
                    <span class="j-meta">{{ $groupArticles->count() }} on this page</span>
                </div>

                <div class="space-y-3">
                    @foreach($groupArticles as $article)
                        <article class="j-card jb-item">
                            <div class="jb-num" aria-hidden="true">{{ str_pad((string) $displayNum, 2, '0', STR_PAD_LEFT) }}</div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap gap-2">
                                    @foreach($article->categories as $cat)
                                        <span class="j-badge">{{ $cat->name }}</span>
                                    @endforeach
                                    @if($article->categories->isEmpty() && $article->category)
                                        <span class="j-badge">{{ $article->category }}</span>
                                    @endif
                                    <span class="j-badge">{{ str_replace('_', ' ', $article->visibility) }}</span>
                                    @if($article->issue)
                                        <span class="j-meta">{{ $article->issue->label() }}</span>
                                    @endif
                                </div>

                                <h3 class="mt-2.5">
                                    <a class="j-article-title text-[1.1rem] sm:text-[1.2rem]" href="{{ route('journals.articles.show', [$journal, $article]) }}">
                                        {{ $article->title }}
                                    </a>
                                </h3>

                                <p class="j-meta mt-2">
                                    {{ $article->authors->pluck('name')->join(', ') ?: 'Author TBA' }}
                                    @if($article->published_at)
                                        <span aria-hidden="true"> · </span>
                                        <time datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->format('M j, Y') }}</time>
                                    @endif
                                    @if($article->page_range)
                                        <span aria-hidden="true"> · </span>
                                        <span>pp. {{ $article->page_range }}</span>
                                    @endif
                                </p>

                                @if($article->abstract)
                                    <p class="mt-2.5 text-sm leading-relaxed line-clamp-2" style="color: var(--j-muted)">
                                        {{ Str::limit(strip_tags($article->abstract), 220) }}
                                    </p>
                                @endif
                            </div>

                            <div class="jb-actions">
                                <a class="j-btn" href="{{ route('journals.articles.show', [$journal, $article]) }}">Read</a>
                                @if($article->visibility === 'open')
                                    <a class="j-btn-ghost" href="{{ route('journals.articles.pdf', [$journal, $article->slug]) }}">PDF</a>
                                @endif
                            </div>
                        </article>
                        @php $displayNum++; @endphp
                    @endforeach
                </div>
            </section>
        @endforeach

        @endif

        @if($articles->hasPages())
            {{ $articles->links('vendor.pagination.journal') }}
        @endif
    @endif
</div>
@endsection
