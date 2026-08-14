@extends('layouts.journal')

@section('title', $issue->label().' | '.$journal->title)
@section('meta_description', ($issue->title ?: $issue->label()).' — '.$journal->title)
@section('canonical', route('journals.issues.show', [$journal, $issue]))

@php
    $cover = $issue->coverUrl();
@endphp

@section('seo')
    <meta property="og:title" content="{{ $issue->label() }} | {{ $journal->title }}">
    <meta property="og:description" content="{{ $issue->title ?: ('Articles in '.$issue->label().' of '.$journal->title) }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ route('journals.issues.show', [$journal, $issue]) }}">
    <meta property="og:site_name" content="{{ $journal->title }}">
    @if($cover)
        <meta property="og:image" content="{{ $cover }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="{{ $cover }}">
    @endif
    <meta name="twitter:title" content="{{ $issue->label() }}">
    <script type="application/ld+json">
    {!! json_encode(array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'PublicationIssue',
        'name' => $issue->label(),
        'issueNumber' => (string) $issue->issue_number,
        'image' => $cover,
        'url' => route('journals.issues.show', [$journal, $issue]),
        'isPartOf' => [
            '@type' => 'PublicationVolume',
            'volumeNumber' => (string) $issue->volume?->volume_number,
            'image' => $issue->volume?->coverUrl(),
            'isPartOf' => [
                '@type' => 'Periodical',
                'name' => $journal->title,
                'issn' => $issue->volume?->issn ?: $journal->issn,
            ],
        ],
        'hasPart' => $issue->articles->map(fn ($a) => [
            '@type' => 'ScholarlyArticle',
            'headline' => $a->title,
            'url' => route('journals.articles.show', [$journal, $a]),
        ])->values()->all(),
    ]), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) !!}
    </script>
@endsection

@section('content')
<style>
    .iss-grid { display:grid; gap:1.25rem; }
    @media (min-width: 960px) {
        .iss-grid { grid-template-columns: 14rem minmax(0,1fr); align-items:start; gap:1.75rem; }
        .iss-cover { position:sticky; top:5.5rem; }
    }
    .iss-item {
        display:grid; gap:.85rem; padding:1.15rem 1.2rem;
        transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
    }
    @media (min-width: 720px) {
        .iss-item { grid-template-columns: 3.5rem minmax(0,1fr) auto; align-items:start; }
    }
    .iss-item:hover {
        transform: translateY(-1px);
        border-color: color-mix(in srgb, var(--j-accent) 28%, transparent);
        box-shadow: 0 12px 28px color-mix(in srgb, var(--j-text) 6%, transparent);
    }
    .iss-num {
        font-family: var(--j-font-display);
        font-size: 1.25rem; font-weight: 700; line-height: 1;
        color: color-mix(in srgb, var(--j-text) 28%, transparent);
    }
    .iss-actions { display:flex; flex-wrap:wrap; gap:.45rem; }
    @media (min-width: 720px) {
        .iss-actions { flex-direction:column; align-items:stretch; min-width:7.5rem; }
        .iss-actions .j-btn, .iss-actions .j-btn-ghost { justify-content:center; padding:.55rem .75rem; font-size:.8rem; }
    }
</style>

<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('journals.archive', $journal) }}" class="j-link text-sm">&larr; Archives</a>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('journals.browse', $journal) }}" class="j-btn-ghost text-sm">Browse all</a>
            <a href="{{ route('journals.show', $journal) }}" class="j-btn-ghost text-sm">Current issue</a>
        </div>
    </div>

    <div class="iss-grid">
        <aside class="iss-cover">
            @if($cover)
                <div class="overflow-hidden rounded-xl border" style="border-color: color-mix(in srgb, var(--j-text) 12%, transparent); background: var(--j-surface)">
                    <img src="{{ $cover }}" alt="Cover for {{ $issue->label() }}" style="display:block;width:100%;aspect-ratio:3/4;object-fit:cover">
                </div>
            @else
                <div class="j-card flex aspect-[3/4] items-end p-4" style="background: linear-gradient(145deg, var(--j-primary), var(--j-accent)); color:#fff">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] opacity-80">Issue</p>
                        <p class="j-display mt-1 text-lg font-semibold leading-snug">{{ $issue->label() }}</p>
                    </div>
                </div>
            @endif
            <div class="j-card mt-3 space-y-2 p-4 text-sm">
                <p><span class="font-semibold">Volume</span> · {{ $issue->volume?->volume_number }} ({{ $issue->volume?->year }})</p>
                <p><span class="font-semibold">Issue</span> · {{ $issue->issue_number }}</p>
                <p><span class="font-semibold">Articles</span> · {{ $issue->articles->count() }}</p>
                @if($journal->issn)<p><span class="font-semibold">ISSN</span> · {{ $journal->issn }}</p>@endif
            </div>
        </aside>

        <div class="min-w-0">
            <h1 class="j-section-title">{{ $issue->label() }}</h1>
            @if($issue->title)
                <p class="j-meta mt-2 text-base">{{ $issue->title }}</p>
            @endif
            <p class="j-meta mt-2">
                {{ $issue->articles->count() }} article{{ $issue->articles->count() === 1 ? '' : 's' }} in this issue
            </p>

            <div class="mt-7 space-y-3">
                @forelse($issue->articles as $article)
                    <article class="j-card iss-item">
                        <div class="iss-num" aria-hidden="true">{{ str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT) }}</div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap gap-2">
                                @foreach($article->categories as $cat)
                                    <span class="j-badge">{{ $cat->name }}</span>
                                @endforeach
                                @if($article->categories->isEmpty() && $article->category)
                                    <span class="j-badge">{{ $article->category }}</span>
                                @endif
                                <span class="j-badge">{{ str_replace('_', ' ', $article->visibility) }}</span>
                            </div>
                            <h2 class="mt-2.5">
                                <a class="j-article-title text-[1.1rem] sm:text-[1.2rem]" href="{{ route('journals.articles.show', [$journal, $article]) }}">
                                    {{ $article->title }}
                                </a>
                            </h2>
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
                                    {{ Str::limit(strip_tags($article->abstract), 200) }}
                                </p>
                            @endif
                        </div>
                        <div class="iss-actions">
                            <a class="j-btn" href="{{ route('journals.articles.show', [$journal, $article]) }}">Read</a>
                            @if($article->visibility === 'open')
                                <a class="j-btn-ghost" href="{{ route('journals.articles.pdf', [$journal, $article->slug]) }}">PDF</a>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="j-card p-6 text-sm" style="color: var(--j-muted)">No articles in this issue yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
