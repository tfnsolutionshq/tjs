@extends('layouts.public')

@section('title', config('tjs.full_name').' | '.config('tjs.organization'))
@section('meta_description', config('tjs.pitch') ?: config('tjs.tagline') ?: ('Modernize your journal publishing process with '.config('tjs.full_name').'.'))
@section('canonical', route('home'))
@section('seo')
    @include('seo.page-meta', ['meta' => app(\App\Services\Seo\PageMeta::class)->site(
        config('tjs.full_name').' | '.config('tjs.organization'),
        config('tjs.pitch') ?: config('tjs.tagline'),
        route('home')
    )])
@endsection

@section('content')
@include('public.partials.pagination-styles')

{{-- Hero — editorial illustration background --}}
<section class="relative overflow-hidden" style="background:#f7f8fa">
    <div
        class="pointer-events-none absolute inset-0 bg-cover bg-right bg-no-repeat"
        style="background-image: url('{{ asset('images/bg-1.png') }}')"
        aria-hidden="true"
    ></div>
    <div
        class="pointer-events-none absolute inset-0"
        style="background: linear-gradient(90deg, #f7f8fa 0%, rgba(247,248,250,.92) 42%, rgba(247,248,250,.45) 68%, rgba(247,248,250,.15) 100%)"
        aria-hidden="true"
    ></div>
    <div class="container-x relative grid items-center py-14 lg:min-h-[28rem] lg:py-20">
        <div class="max-w-xl">
            <p class="tjs-reveal text-xs font-bold uppercase tracking-[0.18em]" style="color:var(--blue);--reveal-delay:20ms">
                {{ config('tjs.full_name') }}
            </p>
            <h1 class="tjs-reveal tjs-serif mt-3 text-[2.15rem] font-bold leading-[1.15] text-[#1c2430] sm:text-5xl" style="--reveal-delay:40ms">
                A modern publishing solution for anyone who publishes.
            </h1>
            <p class="tjs-reveal mt-5 max-w-xl text-base leading-relaxed sm:text-lg" style="color:var(--muted);--reveal-delay:120ms">
                {{ config('tjs.pitch') ?: ('TJS gives '.config('tjs.organization').' multi-journal websites, peer review, APA citations, and access-controlled full text — with branding themes for every journal.') }}
            </p>
            <div class="tjs-reveal mt-8 flex flex-wrap gap-3" style="--reveal-delay:200ms">
                <a href="{{ route('journals.index') }}" class="btn btn-blue">Browse journals</a>
                @guest
                    <a href="{{ route('register') }}" class="btn btn-outline">Sign up free</a>
                @else
                    <a href="{{ route('author.submissions.create') }}" class="btn btn-outline">Submit a manuscript</a>
                @endguest
            </div>
        </div>
    </div>
</section>

{{-- Major product description (lead-approved) --}}
<section id="platform" class="border-b border-slate-200/80 bg-white">
    <div class="container-x py-14 lg:py-16">
        <div class="tjs-reveal mx-auto max-w-3xl">
            <p class="text-xs font-bold uppercase tracking-[0.18em]" style="color:var(--blue)">About TJS</p>
            <h2 class="tjs-serif mt-2 text-3xl font-bold text-slate-900">Built to simplify, professionalize, and scale publishing</h2>
            <div class="mt-5 space-y-4 text-base leading-relaxed" style="color:var(--muted)">
                @foreach(preg_split("/\n\s*\n/", trim((string) config('tjs.description'))) as $para)
                    @if(filled($para))
                        <p>{{ $para }}</p>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- Features strip --}}
<section id="features" class="border-y border-slate-200/80 bg-white">
    <div class="container-x py-14 lg:py-16">
        <div class="grid gap-10 sm:grid-cols-3 sm:gap-0">
            @foreach([
                ['01', 'Peer review workspace', 'Assign reviewers, request revisions, and publish to issues from one editorial flow.'],
                ['02', 'Themed journal sites', 'Every journal gets its own colors, banner, logo, and typography — built for multi-journal brands.'],
                ['03', 'Discoverable by design', 'Server-rendered abstracts, citation meta, APA cite tools, and sitemap-ready routes.'],
            ] as $i => [$num, $title, $body])
                <div
                    @class([
                        'tjs-feature tjs-reveal sm:px-8',
                        'sm:border-l sm:border-slate-200' => $i > 0,
                        'sm:pl-0' => $i === 0,
                        'sm:pr-8' => $i === 0,
                    ])
                    style="--reveal-delay: {{ 80 + ($i * 90) }}ms"
                >
                    <p class="text-[11px] font-semibold tracking-[0.22em]" style="color:var(--blue)">{{ $num }}</p>
                    <h3 class="mt-3 text-[1.05rem] font-semibold tracking-tight text-[#1c2430] transition-colors duration-200">{{ $title }}</h3>
                    <p class="mt-2.5 max-w-sm text-sm leading-relaxed" style="color:var(--muted)">{{ $body }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Journals --}}
<section id="journals" class="container-x py-14">
    <div class="tjs-reveal mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em]" style="color:var(--blue)">Publications</p>
            <h2 class="tjs-serif mt-2 text-3xl font-bold text-slate-900">Journals</h2>
        </div>
        <a href="{{ route('journals.index') }}" class="btn btn-outline !py-2 !px-3 text-sm">View all</a>
    </div>

    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($journals as $journal)
            @php
                $t = $journal->themeConfig();
                $banner = $journal->headerImageUrl();
                $overlay = max(0.35, min(0.75, (float) ($t['hero_overlay'] ?? 0.45)));
            @endphp
            <a href="{{ route('journals.show', $journal) }}" class="card tjs-reveal group block" style="--reveal-delay: {{ 60 + ($loop->index * 70) }}ms">
                <div
                    class="card-media relative h-36 overflow-hidden"
                    style="background: linear-gradient(135deg, {{ $t['header_bg'] }}, {{ $t['primary'] }})"
                >
                    @if($banner)
                        <div
                            class="absolute inset-0 bg-cover bg-center transition-transform duration-500 group-hover:scale-[1.03]"
                            style="background-image: url('{{ $banner }}')"
                            aria-hidden="true"
                        ></div>
                        <div
                            class="absolute inset-0"
                            style="background: linear-gradient(180deg, rgba(15,23,42,{{ $overlay * 0.35 }}) 0%, rgba(15,23,42,{{ $overlay }}) 100%)"
                            aria-hidden="true"
                        ></div>
                    @else
                        <div class="absolute inset-0 opacity-40 transition-opacity duration-300 group-hover:opacity-55" style="background: radial-gradient(circle at 30% 20%, #fff, transparent 50%)" aria-hidden="true"></div>
                    @endif
                    <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
                        <p class="text-[10px] font-bold uppercase tracking-[0.16em] opacity-80">Journal</p>
                        <p class="tjs-serif mt-1 text-lg font-bold leading-snug">{{ Str::limit($journal->title, 42) }}</p>
                    </div>
                </div>
                <div class="p-5">
                    <div class="flex flex-wrap gap-2">
                        @if($journal->issn)<span class="badge">ISSN {{ $journal->issn }}</span>@endif
                        @if($journal->is_featured)<span class="badge" style="background:#fdecec;color:#9b2c2c">Featured</span>@endif
                    </div>
                    <p class="mt-3 line-clamp-3 text-sm leading-relaxed text-slate-500">
                        {{ $journal->subtitle ?: Str::limit(strip_tags($journal->description), 130) }}
                    </p>
                    <p class="tjs-link-arrow mt-4 text-sm font-semibold" style="color:var(--blue)">Open journal site <span aria-hidden="true">→</span></p>
                </div>
            </a>
        @empty
            <p class="text-sm text-slate-500">No journals published yet.</p>
        @endforelse
    </div>

    @if($journals->hasPages())
        {{ $journals->links('vendor.pagination.journal') }}
    @endif
</section>

{{-- Latest articles --}}
<section id="latest" class="border-t border-slate-100 bg-[#f7f8fa]">
    <div class="container-x py-14">
        <div class="tjs-reveal mb-8 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em]" style="color:var(--blue)">Reading</p>
                <h2 class="tjs-serif mt-2 text-3xl font-bold text-slate-900">Latest articles</h2>
            </div>
            <a href="{{ route('articles.index') }}" class="btn btn-outline !py-2 !px-3 text-sm">View all</a>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            @forelse($latest as $article)
                @include('public.partials.article-card', [
                    'article' => $article,
                    'revealDelay' => 50 + ($loop->index * 70),
                ])
            @empty
                <p class="text-sm text-slate-500">No public articles yet.</p>
            @endforelse
        </div>

        @if($latest->hasPages())
            {{ $latest->links('vendor.pagination.journal') }}
        @endif
    </div>
</section>

{{-- CTA band --}}
<section class="container-x py-14">
    <div class="cta-band tjs-reveal overflow-hidden rounded-2xl px-6 py-10 text-white sm:px-10" style="background: linear-gradient(120deg, #1c2430 0%, #2a4058 55%, #2f7de1 140%)">
        <div class="max-w-2xl">
            <h2 class="tjs-serif text-3xl font-bold leading-tight">Ready to publish with a modern journal website?</h2>
            <p class="mt-3 text-white/75">Create an account to submit manuscripts, or browse open articles now.</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('register') }}" class="btn btn-blue">Get started</a>
                <a href="{{ route('journals.index') }}" class="btn btn-ghost">Explore journals</a>
            </div>
        </div>
    </div>
</section>
@endsection
