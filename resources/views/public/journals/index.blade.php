@extends('layouts.public')
@section('title', 'Journals | '.config('tjs.name'))
@section('content')
<section style="background: linear-gradient(180deg, #f7f8fa 0%, #ffffff 70%)">
    <div class="container-x py-12 sm:py-16">
        <p class="text-xs font-bold uppercase tracking-[0.18em]" style="color:var(--blue)">Publications</p>
        <h1 class="tjs-serif mt-3 text-4xl font-bold text-slate-900">Journals</h1>
        <p class="mt-3 max-w-2xl text-slate-500">Each journal has a branded website theme — colors, banner, logo, and typography.</p>

        <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($journals as $journal)
                @php
                    $t = $journal->themeConfig();
                    $banner = $journal->headerImageUrl();
                    $overlay = max(0.35, min(0.75, (float) ($t['hero_overlay'] ?? 0.45)));
                @endphp
                <a href="{{ route('journals.show', $journal) }}" class="card group block">
                    <div
                        class="relative h-40 overflow-hidden"
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
            @endforeach
        </div>
    </div>
</section>
@endsection
