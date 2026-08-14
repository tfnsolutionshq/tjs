<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $journal->title)</title>
    <meta name="description" content="@yield('meta_description', $journal->subtitle ?: Str::limit(strip_tags($journal->description), 155))">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    @if($journal->logoUrl())
        <link rel="icon" href="{{ $journal->logoUrl() }}" sizes="any">
        <link rel="apple-touch-icon" href="{{ $journal->logoUrl() }}">
        <link rel="shortcut icon" href="{{ $journal->logoUrl() }}">
    @else
        <link rel="icon" href="{{ asset('favicon.ico') }}">
    @endif
    @yield('seo')
    @stack('meta')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=libre-baskerville:400,700|dm-sans:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php $theme = $journal->themeConfig(); @endphp
    <style>
        :root { {!! $journal->themeCss() !!} }
        body.journal-site {
            margin: 0;
            font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif;
            background: var(--j-page-bg);
            color: var(--j-text);
            -webkit-font-smoothing: antialiased;
        }
        .j-display { font-family: 'Libre Baskerville', Georgia, 'Times New Roman', serif; }
        .j-nav {
            background: var(--j-nav-bg);
            color: var(--j-nav-text);
            position: sticky;
            top: 0;
            z-index: 40;
        }
        .j-nav a { color: color-mix(in srgb, var(--j-nav-text) 82%, transparent); text-decoration: none; font-size: .92rem; font-weight: 550; }
        .j-nav a:hover, .j-nav a.is-active { color: var(--j-nav-text); }
        .j-nav__bar {
            display: flex; align-items: center; justify-content: space-between; gap: .65rem;
            max-width: 72rem; margin: 0 auto;
            padding: 1rem 1rem;
            min-height: 4.25rem;
        }
        @media (min-width: 640px) {
            .j-nav__bar { padding: 1.1rem 1.5rem; min-height: 4.5rem; }
        }
        .j-nav__brand {
            display: flex; align-items: center; gap: .55rem; min-width: 0; flex: 1;
            color: var(--j-nav-text) !important; font-weight: 650; font-size: .95rem;
            letter-spacing: -.01em;
        }
        .j-nav__brand-mark {
            flex-shrink: 0; width: 2.15rem; height: 2.15rem; border-radius: .5rem;
            object-fit: contain; background: rgba(255,255,255,.12); padding: .15rem;
        }
        .j-nav__brand-text {
            min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .j-nav__desktop { display: none; align-items: center; gap: 1.25rem; }
        @media (min-width: 900px) {
            .j-nav__desktop { display: flex; }
            .j-nav__menu-btn { display: none !important; }
        }
        .j-nav__actions {
            display: flex; align-items: center; gap: .35rem; flex-shrink: 0;
        }
        .j-nav__menu-btn,
        .j-nav__icon-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 2.65rem; height: 2.65rem; border-radius: .7rem;
            border: 1px solid color-mix(in srgb, var(--j-nav-text) 18%, transparent);
            background: color-mix(in srgb, var(--j-nav-text) 8%, transparent);
            color: var(--j-nav-text); cursor: pointer; padding: 0;
        }
        .j-nav__menu-btn:hover,
        .j-nav__icon-btn:hover {
            background: color-mix(in srgb, var(--j-nav-text) 14%, transparent);
        }
        .j-nav__menu-btn svg,
        .j-nav__icon-btn svg { width: 1.15rem; height: 1.15rem; }
        .j-nav__account {
            display: none; align-items: center; gap: .45rem;
            padding: .55rem .9rem; border-radius: .6rem;
            color: var(--j-nav-text) !important; font-size: .84rem; font-weight: 650;
            border: 1px solid color-mix(in srgb, var(--j-nav-text) 18%, transparent);
        }
        @media (min-width: 480px) {
            .j-nav__account { display: inline-flex; }
        }
        .j-nav__sheet {
            border-top: 1px solid color-mix(in srgb, var(--j-nav-text) 12%, transparent);
            background: color-mix(in srgb, var(--j-nav-bg) 92%, #000);
            padding: .55rem .75rem 1rem;
        }
        @media (min-width: 900px) {
            .j-nav__sheet { display: none !important; }
        }
        [x-cloak] { display: none !important; }
        .j-nav__sheet-list {
            display: grid; gap: .3rem; margin: 0; padding: 0; list-style: none;
        }
        .j-nav__sheet a {
            display: flex; align-items: center; justify-content: space-between;
            min-height: 2.75rem; padding: .7rem .85rem; border-radius: .7rem;
            color: var(--j-nav-text) !important; font-size: .92rem; font-weight: 600;
            background: transparent;
        }
        .j-nav__sheet a.is-active,
        .j-nav__sheet a:hover {
            background: color-mix(in srgb, var(--j-nav-text) 12%, transparent);
        }
        .j-nav__sheet-meta {
            display: flex; align-items: center; justify-content: space-between; gap: .75rem;
            margin-top: .55rem; padding: .75rem .85rem 0;
            border-top: 1px solid color-mix(in srgb, var(--j-nav-text) 12%, transparent);
        }
        .j-nav__sheet-meta a {
            color: var(--j-nav-text) !important; font-size: .82rem; font-weight: 650;
            min-height: auto; padding: .35rem 0; background: transparent !important;
        }
        .j-hero {
            position: relative;
            min-height: var(--j-header-height);
            display: flex;
            align-items: flex-end;
            background: var(--j-header-bg);
            color: var(--j-header-text);
            overflow: hidden;
        }
        @media (max-width: 639px) {
            .j-hero { min-height: min(var(--j-header-height), 220px); }
        }
        /* Secondary pages: keep brand, reduce hero dominance */
        .j-hero.j-hero--compact {
            min-height: clamp(180px, 28vw, 260px);
        }
        .j-hero.j-hero--compact .j-hero__inner {
            padding: 1.75rem 1rem 2rem;
        }
        @media (min-width: 640px) {
            .j-hero.j-hero--compact .j-hero__inner {
                padding: 2.1rem 1.5rem 2.35rem;
            }
        }
        .j-hero.j-hero--compact .j-hero__logo {
            height: 44px;
            max-width: 130px;
        }
        .j-hero.j-hero--compact h1 {
            font-size: clamp(1.35rem, 3.2vw, 2rem);
        }
        .j-hero.j-hero--compact .j-hero__sub {
            display: block;
            margin-top: .45rem;
            font-size: .95rem;
            max-width: 36rem;
            opacity: .9;
        }
        .j-hero.j-hero--compact .j-hero__eyebrow {
            font-size: .7rem;
            letter-spacing: .16em;
        }
        .j-hero__media {
            position: absolute; inset: 0;
            background-size: cover; background-position: center;
        }
        .j-hero__shade {
            position: absolute; inset: 0;
            background: color-mix(in srgb, #0b1220 calc(var(--j-hero-overlay) * 100%), transparent);
        }
        .j-hero__inner {
            position: relative; z-index: 1;
            width: 100%;
            max-width: 72rem;
            margin: 0 auto;
            padding: 1.75rem 1rem 2rem;
        }
        @media (min-width: 640px) {
            .j-hero__inner { padding: 2.5rem 1.25rem 2.75rem; }
        }
        .j-hero__inner.is-center { text-align: center; }
        .j-hero__inner.is-center .j-hero__brand { justify-content: center; }
        .j-hero__brand { display: flex; align-items: center; gap: .85rem; flex-wrap: wrap; }
        .j-hero__logo {
            height: 44px; width: auto; max-width: 140px;
            object-fit: contain; background: rgba(255,255,255,.12);
            border-radius: .5rem; padding: .35rem .5rem;
        }
        @media (min-width: 640px) {
            .j-hero__logo { height: 56px; max-width: 160px; }
        }
        .j-hero h1 {
            font-size: clamp(1.45rem, 5vw, 3rem);
        }
        .j-btn {
            display: inline-flex; align-items: center; gap: .5rem;
            background: var(--j-accent); color: #fff; font-weight: 600;
            padding: .7rem 1.1rem; border-radius: .55rem; text-decoration: none;
        }
        .j-btn:hover { filter: brightness(1.05); color: #fff; }
        .j-btn-ghost {
            display: inline-flex; align-items: center;
            border: 1px solid color-mix(in srgb, var(--j-text) 14%, transparent);
            background: var(--j-surface); color: var(--j-text);
            font-weight: 600; padding: .7rem 1.1rem; border-radius: .55rem; text-decoration: none;
        }
        .j-card {
            background: var(--j-surface);
            border: 1px solid color-mix(in srgb, var(--j-text) 8%, transparent);
            border-radius: .85rem;
        }
        .j-badge {
            display: inline-flex; align-items: center;
            border-radius: 999px; padding: .15rem .55rem;
            font-size: 11px; font-weight: 700; letter-spacing: .02em;
            background: color-mix(in srgb, var(--j-accent) 14%, white);
            color: var(--j-accent);
        }
        .j-article-title {
            font-family: var(--j-font-display);
            font-size: 1.25rem; font-weight: 600; line-height: 1.35;
            color: var(--j-text); text-decoration: none;
        }
        .j-article-title:hover { color: var(--j-primary); }
        .j-meta { color: var(--j-muted); font-size: .875rem; }
        .j-link { color: var(--j-accent); font-weight: 600; text-decoration: none; }
        .j-link:hover { text-decoration: underline; }
        .j-section-title {
            font-family: var(--j-font-display);
            font-size: 1.5rem; font-weight: 700; color: var(--j-text);
        }
        .j-footer {
            border-top: 1px solid color-mix(in srgb, var(--j-text) 10%, transparent);
            background: var(--j-surface);
            color: var(--j-muted);
            font-size: .875rem;
        }
    </style>
</head>
<body class="journal-site antialiased" x-data="{ navOpen: false }" @keydown.escape.window="navOpen = false">
    @php
        $nav = [
            ['label' => 'Current', 'route' => 'journals.show', 'params' => [$journal]],
            ['label' => 'Archives', 'route' => 'journals.archive', 'params' => [$journal]],
            ['label' => 'About', 'route' => 'journals.about', 'params' => [$journal]],
            ['label' => 'Editorial Board', 'route' => 'journals.editorial-board', 'params' => [$journal]],
            ['label' => 'Reviewers', 'route' => 'journals.reviewers', 'params' => [$journal]],
            ['label' => 'Browse', 'route' => 'journals.browse', 'params' => [$journal]],
        ];
        $currentRoute = request()->route()?->getName();
    @endphp

    <header class="j-nav shadow-sm" @click.outside="navOpen = false">
        <div class="j-nav__bar">
            <button
                type="button"
                class="j-nav__menu-btn"
                @click.stop="navOpen = !navOpen"
                :aria-expanded="navOpen.toString()"
                aria-controls="journal-mobile-nav"
                aria-label="{{ __('Menu') }}"
            >
                <svg x-show="!navOpen" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                <svg x-show="navOpen" x-cloak fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>

            <a href="{{ route('journals.show', $journal) }}" class="j-nav__brand">
                @if($journal->logoUrl())
                    <img src="{{ $journal->logoUrl() }}" alt="" class="j-nav__brand-mark">
                @endif
                <span class="j-nav__brand-text">{{ $journal->title }}</span>
            </a>

            <nav class="j-nav__desktop" aria-label="Journal">
                @foreach($nav as $item)
                    <a href="{{ route($item['route'], $item['params']) }}" @class(['is-active' => $currentRoute === $item['route']])>{{ $item['label'] }}</a>
                @endforeach
            </nav>

            <div class="j-nav__actions">
                <a href="{{ route('home') }}" class="j-nav__icon-btn" title="{{ config('tjs.name') }}" aria-label="{{ config('tjs.name') }} home">
                    <img src="{{ asset('images/tfns-logo.jpeg') }}" alt="" style="width:1.35rem;height:1.35rem;object-fit:contain;border-radius:.25rem;background:#fff">
                </a>
                @auth
                    <a href="{{ route('dashboard') }}" class="j-nav__account">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="j-nav__account">Log in</a>
                @endauth
            </div>
        </div>

        <div
            id="journal-mobile-nav"
            class="j-nav__sheet"
            x-cloak
            x-show="navOpen"
            x-transition.opacity.duration.150ms
        >
            <ul class="j-nav__sheet-list">
                @foreach($nav as $item)
                    <li>
                        <a
                            href="{{ route($item['route'], $item['params']) }}"
                            @class(['is-active' => $currentRoute === $item['route']])
                            @click="navOpen = false"
                        >
                            <span>{{ $item['label'] }}</span>
                            @if($currentRoute === $item['route'])
                                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="j-nav__sheet-meta">
                <a href="{{ route('home') }}" @click="navOpen = false">{{ config('tjs.name') }} home</a>
                @auth
                    <a href="{{ route('dashboard') }}" @click="navOpen = false">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" @click="navOpen = false">Log in</a>
                @endauth
            </div>
        </div>
    </header>

    @hasSection('hero')
        @yield('hero')
    @else
        @php $compactHero = $currentRoute !== 'journals.show'; @endphp
        <section class="j-hero {{ $compactHero ? 'j-hero--compact' : '' }}">
            @if($journal->headerImageUrl())
                <div class="j-hero__media" style="background-image: url('{{ $journal->headerImageUrl() }}')"></div>
                <div class="j-hero__shade"></div>
            @endif
            <div class="j-hero__inner {{ ($theme['header_align'] ?? 'left') === 'center' ? 'is-center' : '' }}">
                <div class="j-hero__brand">
                    @if($journal->logoUrl())
                        <img src="{{ $journal->logoUrl() }}" alt="{{ $journal->title }} logo" class="j-hero__logo">
                    @endif
                    <div>
                        <p class="j-hero__eyebrow text-xs font-semibold uppercase tracking-[0.18em] opacity-80">
                            {{ $compactHero ? (collect($nav)->firstWhere('route', $currentRoute)['label'] ?? 'Journal') : 'Journal' }}
                        </p>
                        <h1 class="j-display mt-1 text-3xl font-semibold leading-tight sm:text-4xl lg:text-5xl">{{ $journal->title }}</h1>
                        @if(($theme['show_subtitle'] ?? true) && $journal->subtitle)
                            <p class="j-hero__sub mt-2 max-w-2xl text-base opacity-90 sm:text-lg">{{ $journal->subtitle }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    @endif

    <x-flash />

    <main>
        @yield('content')
    </main>

    <footer class="j-footer mt-16">
        <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-8 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <p>{{ $journal->title }} · {{ $journal->publisher ?: config('tjs.organization') }}</p>
            <p>
                @if($journal->issn)ISSN {{ $journal->issn }} · @endif
                <a class="j-link" href="{{ route('home') }}">{{ config('tjs.name') }}</a>
            </p>
        </div>
    </footer>
</body>
</html>
