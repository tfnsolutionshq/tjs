<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('tjs.full_name'))</title>
    <meta name="description" content="@yield('meta_description', config('tjs.organization').' — scholarly journals platform.')">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <link rel="sitemap" type="application/xml" title="{{ config('tjs.full_name') }}" href="{{ route('sitemap') }}">
    <meta name="robots" content="@yield('robots', 'index,follow,max-image-preview:large')">
    <meta name="application-name" content="{{ config('tjs.full_name') }}">
    <meta name="apple-mobile-web-app-title" content="{{ config('tjs.name') }}">
    @include('seo.brand-icons')
    @yield('seo')
    @stack('meta')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=libre-baskerville:400,700|dm-sans:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --ink: #1c2430;
            --muted: #5f6b7a;
            --line: #e6eaef;
            --paper: #f4f6f8;
            --white: #ffffff;
            --nav: #2a3038;
            --blue: #2f7de1;
            --green: #1f8a5b;
            --crimson: #b23b3b;
        }
        * { box-sizing: border-box; }
        body.tjs-body {
            margin: 0;
            font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif;
            color: var(--ink);
            background: var(--white);
            -webkit-font-smoothing: antialiased;
        }
        .tjs-serif { font-family: 'Libre Baskerville', Georgia, 'Times New Roman', serif; }
        .site-nav {
            background: var(--nav);
            color: #fff;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .site-nav a {
            color: rgba(255,255,255,.88);
            text-decoration: none;
            font-size: .9rem;
            font-weight: 500;
            position: relative;
            transition: color .2s ease;
        }
        .site-nav a:hover { color: #fff; }
        .site-nav nav a::after {
            content: '';
            position: absolute;
            left: 0; right: 0; bottom: -6px;
            height: 2px;
            border-radius: 2px;
            background: var(--blue);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform .22s ease;
        }
        .site-nav nav a:hover::after { transform: scaleX(1); }
        .site-nav__bar {
            display: flex; align-items: center; justify-content: space-between; gap: .65rem;
            padding: .7rem 0; min-height: 3.4rem;
        }
        .site-nav__menu-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 2.45rem; height: 2.45rem; border-radius: .65rem;
            border: 1px solid rgba(255,255,255,.18); background: rgba(255,255,255,.06);
            color: #fff; cursor: pointer; padding: 0; flex-shrink: 0;
        }
        @media (min-width: 768px) {
            .site-nav__menu-btn { display: none; }
        }
        .site-nav__menu-btn svg { width: 1.15rem; height: 1.15rem; }
        .site-nav__sheet {
            border-top: 1px solid rgba(255,255,255,.1);
            padding: .55rem 0 1rem;
        }
        @media (min-width: 768px) {
            .site-nav__sheet { display: none !important; }
        }
        .site-nav__sheet-list {
            display: grid; gap: .3rem; margin: 0; padding: 0; list-style: none;
        }
        .site-nav__sheet a.site-nav__sheet-link {
            display: flex; align-items: center;
            min-height: 2.75rem; padding: .7rem .85rem; border-radius: .7rem;
            color: #fff; font-size: .92rem; font-weight: 600;
            background: transparent;
        }
        .site-nav__sheet a.site-nav__sheet-link:hover,
        .site-nav__sheet a.site-nav__sheet-link.is-active {
            background: rgba(255,255,255,.08);
        }
        .site-nav__sheet-actions {
            display: flex; flex-wrap: wrap; gap: .5rem;
            margin-top: .65rem; padding-top: .75rem;
            border-top: 1px solid rgba(255,255,255,.1);
        }
        [x-cloak] { display: none !important; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
            border-radius: .45rem; font-weight: 600; font-size: .9rem;
            padding: .7rem 1.15rem; text-decoration: none; border: 0; cursor: pointer;
            transition: filter .18s ease, transform .18s ease, box-shadow .18s ease, border-color .18s ease, background .18s ease;
        }
        .btn:hover { filter: brightness(1.06); transform: translateY(-1px); }
        .btn:active { transform: translateY(0); filter: brightness(.98); }
        .btn-blue {
            background: var(--blue); color: #fff;
            box-shadow: 0 6px 16px rgba(47,125,225,.28);
        }
        .btn-blue:hover { box-shadow: 0 10px 22px rgba(47,125,225,.34); }
        .btn-green {
            background: var(--green); color: #fff;
            box-shadow: 0 6px 16px rgba(31,138,91,.25);
        }
        .btn-crimson { background: var(--crimson); color: #fff; }
        .btn-ghost {
            background: transparent; color: #fff;
            border: 1px solid rgba(255,255,255,.35);
        }
        .btn-ghost:hover {
            background: rgba(255,255,255,.08);
            border-color: rgba(255,255,255,.55);
        }
        .btn-outline {
            background: #fff; color: var(--ink);
            border: 1px solid var(--line);
        }
        .btn-outline:hover {
            border-color: #b8c4d1;
            box-shadow: 0 8px 20px rgba(28,36,48,.06);
        }
        .card {
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: .75rem;
            overflow: hidden;
            transition: box-shadow .28s ease, transform .28s ease, border-color .28s ease;
        }
        .card:hover {
            border-color: #d5dde6;
            box-shadow: 0 18px 40px rgba(28,36,48,.1);
            transform: translateY(-3px);
        }
        .card .card-media,
        .card > .relative {
            transition: transform .45s ease;
        }
        .card:hover .card-media { transform: scale(1.04); }
        .badge {
            display: inline-flex; align-items: center;
            font-size: .68rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
            padding: .28rem .55rem; border-radius: .3rem;
            background: #eef4fc; color: #255ea8;
            transition: transform .15s ease;
        }
        .card:hover .badge { transform: translateY(-1px); }
        .container-x {
            width: min(1120px, calc(100% - 2rem));
            margin-inline: auto;
        }
        .cta-band {
            transition: transform .35s ease, box-shadow .35s ease;
            box-shadow: 0 16px 40px rgba(28,36,48,.12);
        }
        .cta-band:hover {
            transform: translateY(-2px);
            box-shadow: 0 22px 50px rgba(28,36,48,.16);
        }
        /* Back-compat for admin/portal views */
        .tjs-serif { font-family: 'Libre Baskerville', Georgia, serif; }
        .tjs-btn { display:inline-flex; align-items:center; gap:.45rem; background:var(--blue); color:#fff; font-weight:600; padding:.7rem 1.1rem; border-radius:.4rem; text-decoration:none; }
        .tjs-btn-outline { display:inline-flex; align-items:center; gap:.45rem; background:#fff; color:var(--ink); border:1px solid var(--line); font-weight:600; padding:.7rem 1.1rem; border-radius:.4rem; text-decoration:none; }
        .tjs-card { background:#fff; border:1px solid var(--line); border-radius:.75rem; }
        .tjs-badge { display:inline-flex; align-items:center; font-size:.68rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; padding:.28rem .55rem; border-radius:.3rem; background:#eef4fc; color:#255ea8; }
    </style>
</head>
<body class="tjs-body" x-data="{ navOpen: false }" @keydown.escape.window="navOpen = false">
    <x-site-notice />
    <header class="site-nav" @click.outside="navOpen = false">
        <div class="container-x">
            <div class="site-nav__bar">
                <button
                    type="button"
                    class="site-nav__menu-btn"
                    @click.stop="navOpen = !navOpen"
                    :aria-expanded="navOpen.toString()"
                    aria-controls="site-mobile-nav"
                    aria-label="Menu"
                >
                    <svg x-show="!navOpen" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                    <svg x-show="navOpen" x-cloak fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
                </button>

                <a href="{{ route('home') }}" class="flex min-w-0 flex-1 items-center !text-white md:flex-none">
                    <x-platform-logo variant="dark" class="h-9 w-auto object-contain" />
                </a>

                <nav class="hidden items-center gap-6 md:flex">
                    <a href="{{ route('journals.index') }}">Journals</a>
                    <a href="{{ route('home') }}#platform">Platform</a>
                    <a href="{{ route('home') }}#latest">Articles</a>
                    @auth
                        <a href="{{ route('dashboard') }}">Dashboard</a>
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}">Admin</a>
                        @elseif(auth()->user()->managedJournals()->isNotEmpty())
                            <a href="{{ route('journal.manage.dashboard', auth()->user()->managedJournals()->first()) }}">Manage Journal</a>
                        @endif
                    @endauth
                </nav>

                <div class="flex items-center gap-2">
                    @auth
                        <a href="{{ route('author.submissions.index') }}" class="hidden text-sm sm:inline @if(request()->routeIs('author.*')) !text-white @endif">Submissions</a>
                        <a href="{{ route('profile.edit') }}" class="hidden text-sm sm:inline @if(request()->routeIs('profile.*')) !text-white @endif">Profile</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button type="submit" class="btn btn-ghost !py-2 !px-3 text-sm">Log Out</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-green !py-2 !px-3.5 text-sm">Login</a>
                        <a href="{{ route('register') }}" class="btn btn-ghost !py-2 !px-3.5 text-sm hidden sm:inline-flex">Sign Up</a>
                    @endauth
                </div>
            </div>

            <div
                id="site-mobile-nav"
                class="site-nav__sheet"
                x-cloak
                x-show="navOpen"
                x-transition.opacity.duration.150ms
            >
                <ul class="site-nav__sheet-list">
                    <li><a class="site-nav__sheet-link @if(request()->routeIs('journals.*')) is-active @endif" href="{{ route('journals.index') }}" @click="navOpen = false">Journals</a></li>
                    <li><a class="site-nav__sheet-link" href="{{ route('home') }}#platform" @click="navOpen = false">Platform</a></li>
                    <li><a class="site-nav__sheet-link" href="{{ route('home') }}#latest" @click="navOpen = false">Articles</a></li>
                    @auth
                        <li><a class="site-nav__sheet-link @if(request()->routeIs('dashboard')) is-active @endif" href="{{ route('dashboard') }}" @click="navOpen = false">Dashboard</a></li>
                        <li><a class="site-nav__sheet-link @if(request()->routeIs('author.*')) is-active @endif" href="{{ route('author.submissions.index') }}" @click="navOpen = false">Submissions</a></li>
                        @if(auth()->user()->isReviewer() || auth()->user()->isEditor() || auth()->user()->isAdmin())
                            <li><a class="site-nav__sheet-link @if(request()->routeIs('reviewer.*')) is-active @endif" href="{{ route('reviewer.reviews.index') }}" @click="navOpen = false">Reviews</a></li>
                        @endif
                        @if(auth()->user()->isAdmin())
                            <li><a class="site-nav__sheet-link" href="{{ route('admin.dashboard') }}" @click="navOpen = false">Admin</a></li>
                        @elseif(auth()->user()->managedJournals()->isNotEmpty())
                            <li><a class="site-nav__sheet-link" href="{{ route('journal.manage.dashboard', auth()->user()->managedJournals()->first()) }}" @click="navOpen = false">Manage Journal</a></li>
                        @endif
                        <li><a class="site-nav__sheet-link @if(request()->routeIs('profile.*')) is-active @endif" href="{{ route('profile.edit') }}" @click="navOpen = false">Profile</a></li>
                    @endauth
                </ul>
                <div class="site-nav__sheet-actions">
                    @auth
                        <form method="POST" action="{{ route('logout') }}" class="w-full">@csrf
                            <button type="submit" class="btn btn-ghost !py-2.5 w-full text-sm">Log Out</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-green !py-2.5 flex-1 text-sm" @click="navOpen = false">Login</a>
                        <a href="{{ route('register') }}" class="btn btn-ghost !py-2.5 flex-1 text-sm" @click="navOpen = false">Sign Up</a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <x-flash />

    <main>
        @yield('content')
    </main>

    <footer style="background:#1c2430;color:rgba(255,255,255,.72)">
        <div class="container-x grid gap-8 py-12 sm:grid-cols-3">
            <div>
                <a href="{{ route('home') }}" class="inline-flex">
                    <x-platform-logo variant="dark" class="h-10 w-auto object-contain" />
                </a>
                <p class="mt-3 text-sm leading-relaxed">{{ config('tjs.full_name') }} by {{ config('tjs.organization') }}.</p>
            </div>
            <div class="text-sm space-y-2">
                <p class="text-white font-semibold">Explore</p>
                <a class="block hover:text-white" href="{{ route('journals.index') }}">Journals</a>
                <a class="block hover:text-white" href="{{ route('register') }}">Create account</a>
                <a class="block hover:text-white" href="{{ route('sitemap') }}">Sitemap</a>
            </div>
            <div class="text-sm space-y-2">
                <p class="text-white font-semibold">Publishing</p>
                <p>Peer review, themed journal sites, DOI-ready metadata, and controlled full-text access.</p>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="container-x py-4 text-xs text-white/80 [&_.platform-footer-bar_a]:text-white [&_.platform-footer-bar_a:hover]:underline">
                <x-platform-footer />
            </div>
        </div>
    </footer>
</body>
</html>
