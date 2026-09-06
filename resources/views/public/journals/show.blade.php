@extends('layouts.journal')

@section('title', $journal->title.' | '.config('tjs.name'))
@section('meta_description', $journal->subtitle ?: Str::limit(strip_tags($journal->description), 155))
@section('canonical', route('journals.show', $journal))

@section('seo')
    @include('seo.page-meta', ['meta' => app(\App\Services\Seo\PageMeta::class)->journal(
        $journal,
        $journal->title.' | '.config('tjs.name'),
        $journal->subtitle ?: \Illuminate\Support\Str::limit(strip_tags($journal->description), 155),
        route('journals.show', $journal),
        $journal->headerImageUrl() ?: ($currentIssue?->coverUrl() ?: $journal->logoUrl())
    )])
@endsection

@section('content')
@php
    $articles = $currentIssue?->articles ?? collect();
    $featured = $articles->first();
    $rest = $articles->skip(1);
@endphp

<div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
    @if($featured)
        {{-- Featured / Original Research block --}}
        <section class="overflow-hidden rounded-2xl border" style="border-color: color-mix(in srgb, var(--j-text) 10%, transparent); background: var(--j-surface)">
            <div class="grid lg:grid-cols-[1.15fr_1fr]">
                <div class="relative min-h-[220px] lg:min-h-[320px]" style="background: linear-gradient(145deg, var(--j-primary), var(--j-accent))">
                    <div class="absolute inset-0 opacity-30" style="background: radial-gradient(circle at 20% 15%, #fff, transparent 50%), radial-gradient(circle at 90% 80%, #000, transparent 45%)"></div>
                    <div class="relative flex h-full flex-col justify-end p-6 text-white sm:p-8">
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] opacity-85">Original research</p>
                        <h2 class="j-display mt-3 text-2xl font-bold leading-snug sm:text-3xl">
                            <a href="{{ route('journals.articles.show', [$journal, $featured]) }}" class="hover:opacity-90">{{ $featured->title }}</a>
                        </h2>
                    </div>
                </div>
                <div class="flex flex-col justify-center p-6 sm:p-8">
                    <div class="flex flex-wrap gap-2">
                        @foreach($featured->categories as $cat)
                            <span class="j-badge">{{ $cat->name }}</span>
                        @endforeach
                        @if($featured->categories->isEmpty() && $featured->category)
                            <span class="j-badge">{{ $featured->category }}</span>
                        @endif
                        <span class="j-badge">{{ str_replace('_', ' ', $featured->visibility) }}</span>
                        @if($currentIssue)<span class="j-meta">{{ $currentIssue->label() }}</span>@endif
                    </div>
                    <p class="j-meta mt-4">{{ $featured->authors->pluck('name')->join(', ') }}</p>
                    @if($featured->abstract)
                        <p class="mt-3 text-sm leading-relaxed" style="color: var(--j-muted)">{{ Str::limit(strip_tags($featured->abstract), 220) }}</p>
                    @endif
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a class="j-btn" href="{{ route('journals.articles.show', [$journal, $featured]) }}">Read article</a>
                        @if($featured->visibility === 'open')
                            <a class="j-btn-ghost" href="{{ route('journals.articles.pdf', [$journal, $featured->slug]) }}">PDF</a>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    @endif

    <div class="mt-12 grid gap-8 lg:grid-cols-[1.7fr_.9fr]">
        <section>
            <div class="mb-5 flex items-end justify-between gap-3">
                <h2 class="j-section-title">{{ $featured ? 'More in this issue' : 'Latest articles' }}</h2>
                <a href="{{ route('journals.browse', $journal) }}" class="j-link text-sm">Browse all</a>
            </div>

            @if($currentIssue)
                <p class="j-meta mb-6">
                    Current issue ·
                    <a class="j-link" href="{{ route('journals.issues.show', [$journal, $currentIssue]) }}">{{ $currentIssue->label() }}</a>
                    @if($currentIssue->title) — {{ $currentIssue->title }} @endif
                </p>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                @forelse(($featured ? $rest : $articles) as $article)
                    <article class="j-card overflow-hidden transition hover:-translate-y-0.5 hover:shadow-lg">
                        <div class="h-28" style="background: linear-gradient(135deg, color-mix(in srgb, var(--j-primary) 85%, white), color-mix(in srgb, var(--j-accent) 80%, black))"></div>
                        <div class="p-4">
                            <div class="flex flex-wrap gap-2">
                                @foreach($article->categories as $cat)
                                    <span class="j-badge">{{ $cat->name }}</span>
                                @endforeach
                                @if($article->categories->isEmpty() && $article->category)
                                    <span class="j-badge">{{ $article->category }}</span>
                                @endif
                                <span class="j-badge">{{ str_replace('_', ' ', $article->visibility) }}</span>
                            </div>
                            <h3 class="mt-3">
                                <a class="j-article-title text-[1.05rem]" href="{{ route('journals.articles.show', [$journal, $article]) }}">{{ $article->title }}</a>
                            </h3>
                            <p class="j-meta mt-2 line-clamp-1">{{ $article->authors->pluck('name')->join(', ') ?: 'Author TBA' }}</p>
                            <a class="j-link mt-3 inline-block text-sm" href="{{ route('journals.articles.show', [$journal, $article]) }}">View article →</a>
                        </div>
                    </article>
                @empty
                    @unless($featured)
                        <div class="j-card col-span-full p-6 text-sm" style="color: var(--j-muted)">No articles in the current issue yet.</div>
                    @endunless
                @endforelse
            </div>
        </section>

        <aside class="space-y-4">
            <div class="j-card p-5">
                <h3 class="text-xs font-bold uppercase tracking-[0.16em]" style="color: var(--j-muted)">About</h3>
                <p class="mt-3 text-sm leading-relaxed">{{ Str::limit(strip_tags($journal->description), 200) }}</p>
                <a href="{{ route('journals.about', $journal) }}" class="j-link mt-4 inline-block text-sm">Read more</a>
            </div>
            <div class="j-card space-y-2 p-5 text-sm">
                @if($journal->issn)<p><span class="font-semibold">ISSN</span> · {{ $journal->issn }}</p>@endif
                @if($journal->eissn)<p><span class="font-semibold">eISSN</span> · {{ $journal->eissn }}</p>@endif
                @if($journal->publisher)<p><span class="font-semibold">Publisher</span> · {{ $journal->publisher }}</p>@endif
                @if($journal->default_license)
                    <p>
                        <span class="font-semibold">License</span> ·
                        @php($licenseUrl = \App\Support\Licenses::url($journal->default_license))
                        @if($licenseUrl)
                            <a class="j-link" href="{{ $licenseUrl }}" target="_blank" rel="noopener license">{{ \App\Support\Licenses::label($journal->default_license) ?? $journal->default_license }}</a>
                        @else
                            {{ $journal->default_license }}
                        @endif
                    </p>
                @endif
            </div>
            <div class="j-card p-5">
                <h3 class="text-sm font-semibold">For readers & authors</h3>
                <div class="mt-3 flex flex-col gap-2">
                    <a class="j-btn justify-center text-sm" href="{{ route('journals.archive', $journal) }}">Browse archives</a>
                    <a class="j-btn-ghost justify-center text-sm" href="{{ route('author.submissions.create') }}">Submit manuscript</a>
                    <a class="j-btn-ghost justify-center text-sm" href="{{ route('journals.editorial-board', $journal) }}">Editorial board</a>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection
