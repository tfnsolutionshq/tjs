@extends('layouts.public')
@section('title', 'Journals | '.config('tjs.name'))
@section('content')
<style>
    :root {
        --j-text: #0f172a;
        --j-muted: #64748b;
        --j-accent: var(--blue, #2563eb);
        --j-surface: #fff;
        --j-page-bg: #f7f8fa;
    }
    .pj-grid.is-list {
        grid-template-columns: minmax(0, 1fr) !important;
    }
    .pj-grid.is-list .pj-card {
        display: grid;
        grid-template-columns: 11rem minmax(0, 1fr);
        align-items: stretch;
    }
    .pj-grid.is-list .pj-card__banner { min-height: 100%; height: auto; }
    @media (max-width: 640px) {
        .pj-grid.is-list .pj-card { grid-template-columns: 1fr; }
    }
</style>
@include('public.journals.partials.page-styles')

<section style="background: linear-gradient(180deg, #f7f8fa 0%, #ffffff 70%)">
    <div class="container-x py-12 sm:py-16">
        <p class="text-xs font-bold uppercase tracking-[0.18em]" style="color:var(--blue)">Publications</p>
        <h1 class="tjs-serif mt-3 text-4xl font-bold text-slate-900">Journals</h1>
        <p class="mt-3 max-w-2xl text-slate-500">Each journal has a branded website theme — colors, banner, logo, and typography.</p>

        @if($journals->total() > 0)
            @include('public.journals.partials.list-controls', ['paginator' => $journals, 'layout' => $layout])
        @endif

        <div @class(['pj-grid mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3', 'is-list' => ($layout ?? 'grid') === 'list'])>
            @forelse($journals as $journal)
                @php
                    $t = $journal->themeConfig();
                    $banner = $journal->headerImageUrl();
                    $overlay = max(0.35, min(0.75, (float) ($t['hero_overlay'] ?? 0.45)));
                @endphp
                <a href="{{ route('journals.show', $journal) }}" class="pj-card card group block overflow-hidden">
                    <div
                        class="pj-card__banner relative h-40 overflow-hidden"
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
                            <div class="absolute inset-0 opacity-35" style="background: radial-gradient(circle at 25% 20%, #fff, transparent 55%)" aria-hidden="true"></div>
                        @endif
                        <div class="absolute bottom-0 p-5 text-white">
                            <p class="text-[10px] font-bold uppercase tracking-[0.16em] opacity-80">Journal</p>
                            <p class="tjs-serif mt-1 text-xl font-bold leading-snug">{{ $journal->title }}</p>
                        </div>
                    </div>
                    <div class="p-5">
                        @if($journal->issn)<span class="badge">ISSN {{ $journal->issn }}</span>@endif
                        <p class="mt-3 line-clamp-3 text-sm leading-relaxed text-slate-500">{{ $journal->subtitle }}</p>
                        <p class="mt-4 text-sm font-semibold" style="color:var(--blue)">Open journal site →</p>
                    </div>
                </a>
            @empty
                <div class="col-span-full rounded-2xl border border-slate-200 bg-white p-10 text-center">
                    <p class="text-lg font-semibold text-slate-800">No journals published yet</p>
                    <p class="mt-2 text-sm text-slate-500">Active journals will appear here once they are launched.</p>
                </div>
            @endforelse
        </div>

        @if($journals->hasPages())
            {{ $journals->links('vendor.pagination.journal') }}
        @endif
    </div>
</section>
@endsection
