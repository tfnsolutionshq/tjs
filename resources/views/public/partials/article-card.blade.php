@props(['article', 'revealDelay' => 50])

@php $jt = $article->journal->themeConfig(); @endphp

<article {{ $attributes->class(['card tjs-reveal flex flex-col sm:flex-row']) }} style="--reveal-delay: {{ $revealDelay }}ms">
    <div class="card-media h-36 w-full shrink-0 sm:h-auto sm:w-40" style="background: linear-gradient(160deg, {{ $jt['primary'] }}, {{ $jt['accent'] }})"></div>
    <div class="flex flex-1 flex-col p-5">
        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-400">
            <a href="{{ route('journals.show', $article->journal) }}" class="font-semibold transition-opacity hover:opacity-70" style="color:var(--blue)">{{ $article->journal->title }}</a>
            @if($article->issue)<span>{{ $article->issue->label() }}</span>@endif
            <span class="badge">{{ str_replace('_', ' ', $article->visibility) }}</span>
        </div>
        <h3 class="tjs-serif mt-2 text-xl font-bold leading-snug">
            <a href="{{ route('journals.articles.show', [$article->journal, $article]) }}" class="transition-opacity hover:opacity-75">{{ $article->title }}</a>
        </h3>
        <p class="mt-2 text-sm text-slate-500">
            {{ $article->authors->pluck('name')->join(', ') ?: 'Author TBA' }}
            @if($article->published_at) · {{ $article->published_at->format('M j, Y') }} @endif
        </p>
        @if($article->abstract)
            <p class="mt-3 line-clamp-2 text-sm leading-relaxed text-slate-500">{{ Str::limit(strip_tags($article->abstract), 140) }}</p>
        @endif
        <a href="{{ route('journals.articles.show', [$article->journal, $article]) }}" class="tjs-link-arrow mt-4 text-sm font-semibold" style="color:var(--blue)">Read article <span aria-hidden="true">→</span></a>
    </div>
</article>
