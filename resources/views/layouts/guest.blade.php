<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('tjs.full_name') }}</title>
    @include('seo.portal-head', [
        'brandJournal' => ($journal ?? null) instanceof \App\Models\Journal ? $journal : null,
        'description' => ($journal ?? null) instanceof \App\Models\Journal
            ? ('Sign in or register for '.$journal->title.'.')
            : (config('tjs.organization').' — sign in to the journals platform.'),
    ])
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=libre-baskerville:400,700|dm-sans:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --ink: #1c2430;
            --muted: #5f6b7a;
            --line: #e6eaef;
            --nav: #2a3038;
            --blue: #2f7de1;
            --green: #1f8a5b;
        }
        [x-cloak] { display: none !important; }
        body {
            margin: 0;
            font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif;
            color: var(--ink);
            background: #f4f6f8;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
        }
        .serif { font-family: 'Libre Baskerville', Georgia, serif; }
        .auth-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr;
        }
        @media (min-width: 960px) {
            .auth-shell { grid-template-columns: 1.05fr .95fr; }
        }
        .auth-brand {
            background: linear-gradient(145deg, #1c2430 0%, #2a4058 55%, #2f7de1 140%);
            color: #fff;
            padding: 2rem;
            display: none;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        @media (min-width: 960px) {
            .auth-brand { display: flex; }
        }
        .auth-brand::after {
            content: '';
            position: absolute;
            inset: auto -20% -30% 20%;
            height: 70%;
            background: radial-gradient(circle, rgba(255,255,255,.18), transparent 60%);
            pointer-events: none;
        }
        .auth-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.25rem;
        }
        .auth-card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 18px 40px rgba(28,36,48,.08);
            animation: auth-card-in .45s cubic-bezier(.22,1,.36,1);
        }
        @keyframes auth-card-in {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: none; }
        }
        .auth-label {
            display: block;
            font-size: .85rem;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: .4rem;
        }
        .auth-input {
            width: 100%;
            border: 1px solid #d7dee7;
            border-radius: .5rem;
            padding: .7rem .85rem;
            font-size: .95rem;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
            background: #fff;
        }
        .auth-input:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(47,125,225,.15);
        }
        .auth-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            background: var(--blue);
            color: #fff;
            font-weight: 700;
            border: 0;
            border-radius: .5rem;
            padding: .8rem 1rem;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 8px 20px rgba(47,125,225,.25);
            transition: filter .18s ease, transform .18s ease, box-shadow .18s ease;
        }
        .auth-btn:hover {
            filter: brightness(1.06);
            transform: translateY(-1px);
            box-shadow: 0 12px 26px rgba(47,125,225,.32);
        }
        .auth-btn:active { transform: translateY(0); }
        .auth-btn:disabled,
        .auth-btn.is-loading {
            cursor: wait;
            opacity: .72;
            filter: none;
            transform: none;
            box-shadow: 0 4px 12px rgba(15,23,42,.12);
            pointer-events: none;
        }
        .auth-btn__spinner {
            width: 1rem; height: 1rem; margin-right: .5rem;
            border: 2px solid rgba(255,255,255,.35);
            border-top-color: #fff;
            border-radius: 999px;
            animation: auth-spin .7s linear infinite;
        }
        @keyframes auth-spin { to { transform: rotate(360deg); } }
        .auth-btn-green { background: var(--green); box-shadow: 0 8px 20px rgba(31,138,91,.25); }
        .auth-btn-green:disabled,
        .auth-btn-green.is-loading { box-shadow: 0 4px 12px rgba(31,138,91,.18); }
        .auth-btn-secondary {
            background: #fff;
            color: var(--ink);
            border: 1px solid #e2e8f0;
            box-shadow: none;
        }
        .auth-btn-secondary:hover {
            filter: none;
            background: #f8fafc;
            border-color: #cbd5e1;
            box-shadow: none;
        }
        .auth-link { color: var(--blue); font-weight: 600; text-decoration: none; transition: opacity .15s ease; }
        .auth-link:hover { text-decoration: underline; opacity: .85; }
        .auth-muted { color: var(--muted); font-size: .9rem; line-height: 1.5; }
        .auth-error {
            color: #b42318;
            font-size: .8rem;
            margin-top: .35rem;
            animation: auth-error-in .28s ease;
        }
        @keyframes auth-error-in {
            from { opacity: 0; transform: translateY(-3px); }
            to { opacity: 1; transform: none; }
        }
        .auth-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }
        .auth-panel__inner { width: 100%; max-width: 440px; }
        .auth-panel__inner.is-wide { max-width: 540px; }
        .auth-card.is-wide { max-width: none; }

        .jp { display: grid; gap: .7rem; }
        .jp-toolbar { display: grid; gap: .45rem; }
        .jp-search {
            display: flex; align-items: center; gap: .55rem;
            border: 1px solid #d7dee7; border-radius: .7rem;
            padding: .55rem .75rem; background: #fff;
            transition: border-color .15s, box-shadow .15s;
        }
        .jp-search:focus-within {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(47,125,225,.15);
        }
        .jp-search__icon { color: var(--muted); flex-shrink: 0; }
        .jp-search__input {
            flex: 1; min-width: 0; border: 0; outline: none; background: transparent;
            font-size: .95rem; color: var(--ink);
        }
        .jp-search__clear {
            border: 0; background: #f1f5f9; color: var(--muted);
            width: 1.5rem; height: 1.5rem; border-radius: 999px;
            display: inline-flex; align-items: center; justify-content: center;
            cursor: pointer; flex-shrink: 0;
        }
        .jp-search__clear:hover { background: #e2e8f0; color: var(--ink); }
        .jp-meta {
            margin: 0; font-size: .75rem; font-weight: 600; color: var(--muted);
        }
        .jp-list {
            max-height: min(52vh, 26rem);
            overflow: auto;
            border: 1px solid var(--line);
            border-radius: .85rem;
            background: #fafbfc;
            scrollbar-width: thin;
        }
        .jp-group__label {
            position: sticky; top: 0; z-index: 1;
            padding: .45rem .85rem;
            font-size: .68rem; font-weight: 800; letter-spacing: .12em;
            text-transform: uppercase; color: var(--muted);
            background: color-mix(in srgb, #fafbfc 92%, #fff);
            border-bottom: 1px solid var(--line);
            backdrop-filter: blur(4px);
        }
        .jp-item {
            display: flex; align-items: center; gap: .75rem;
            padding: .7rem .85rem;
            text-decoration: none; color: inherit;
            border-bottom: 1px solid var(--line);
            background: #fff;
            transition: background .12s ease;
        }
        .jp-item:last-child { border-bottom: 0; }
        .jp-item:hover,
        .jp-item.is-focused {
            background: color-mix(in srgb, var(--blue) 6%, #fff);
        }
        .jp-item.is-focused { outline: 2px solid color-mix(in srgb, var(--blue) 35%, transparent); outline-offset: -2px; }
        .jp-item__logo {
            width: 2.15rem; height: 2.15rem; object-fit: contain;
            border-radius: .45rem; background: #f8fafc; border: 1px solid var(--line); flex-shrink: 0;
        }
        .jp-item__initials {
            width: 2.15rem; height: 2.15rem; border-radius: .45rem;
            display: inline-flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: .68rem; letter-spacing: .02em;
            background: color-mix(in srgb, var(--blue) 12%, #fff);
            color: var(--blue); flex-shrink: 0;
        }
        .jp-item__title {
            display: flex; align-items: center; gap: .4rem; flex-wrap: wrap;
            font-size: .9rem; font-weight: 700; color: var(--ink); line-height: 1.25;
        }
        .jp-item__badge {
            font-size: .62rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase;
            color: #1d4ed8; background: #dbeafe; border-radius: 999px; padding: .12rem .4rem;
        }
        .jp-item__sub {
            display: block; margin-top: .15rem;
            font-size: .75rem; color: var(--muted); line-height: 1.3;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .jp-item__slug {
            display: block; margin-top: .1rem;
            font-size: .68rem; color: #94a3b8; font-weight: 600;
        }
        .jp-item__chevron { color: var(--muted); opacity: .45; flex-shrink: 0; }
        .jp-item:hover .jp-item__chevron,
        .jp-item.is-focused .jp-item__chevron { opacity: 1; color: var(--blue); }
        .jp-no-results {
            padding: 1.5rem 1rem; text-align: center;
            font-size: .88rem; color: var(--muted);
        }
        .jp-no-results p { margin: 0 0 .5rem; }
        .jp-empty-state { margin-bottom: .5rem; }

        .auth-footer {
            margin-top: 1.5rem;
            padding-top: 1.15rem;
            border-top: 1px solid var(--line);
            display: grid;
            gap: .65rem;
        }
        .auth-footer__primary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            padding: .85rem .95rem;
            border-radius: .75rem;
            background: #f8fafc;
            border: 1px solid var(--line);
        }
        .auth-footer__primary-copy {
            display: grid;
            gap: .1rem;
            min-width: 0;
        }
        .auth-footer__primary-copy strong {
            font-size: .88rem;
            font-weight: 700;
            color: var(--ink);
        }
        .auth-footer__primary-copy span {
            font-size: .75rem;
            color: var(--muted);
            line-height: 1.35;
        }
        .auth-footer__cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            padding: .45rem .85rem;
            border-radius: .55rem;
            background: #fff;
            border: 1px solid #d7dee7;
            color: var(--blue);
            font-size: .82rem;
            font-weight: 700;
            text-decoration: none;
            transition: border-color .15s, background .15s, color .15s;
        }
        .auth-footer__cta:hover {
            border-color: color-mix(in srgb, var(--blue) 40%, #d7dee7);
            background: color-mix(in srgb, var(--blue) 6%, #fff);
            text-decoration: none;
        }
        .auth-footer__alt {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            margin: 0;
            font-size: .8rem;
            color: var(--muted);
            text-align: center;
        }
        .auth-footer__alt a {
            color: var(--blue);
            font-weight: 600;
            text-decoration: none;
        }
        .auth-footer__alt a:hover { text-decoration: underline; }
        .auth-footer--compact {
            margin-top: 1.15rem;
            padding-top: 1rem;
            border-top: 1px solid var(--line);
        }
    </style>
    @if($journal ?? null)
        @php $theme = $journal->themeConfig(); @endphp
        <style>
            :root {
                --blue: {{ $theme['accent'] ?? '#2f7de1' }};
                --green: {{ $theme['accent'] ?? '#1f8a5b' }};
            }
            .auth-brand {
                background: linear-gradient(145deg, {{ $theme['nav_bg'] ?? '#1c2430' }} 0%, {{ $theme['accent'] ?? '#2f7de1' }} 140%);
            }
        </style>
    @endif
</head>
<body>
    <x-site-notice />
    <x-flash />
    <div class="auth-shell">
        <aside class="auth-brand">
            <div>
                @if(($journal ?? null) && $journal->logoUrl())
                    <a href="{{ route('journals.show', $journal) }}" class="inline-flex items-center text-white no-underline relative z-10">
                        <img src="{{ $journal->logoUrl() }}" alt="{{ $journal->title }}" class="h-10 w-auto rounded-md bg-white object-contain p-0.5">
                    </a>
                @else
                    <a href="{{ route('home') }}" class="inline-flex items-center text-white no-underline relative z-10">
                        <img src="{{ asset('images/tfns-logo.jpeg') }}" alt="{{ config('tjs.organization') }}" class="h-10 w-auto rounded-md bg-white object-contain p-0.5">
                    </a>
                @endif
            </div>
            <div class="relative z-10 max-w-md pb-8">
                @if($journal ?? null)
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-white/70">{{ $journal->title }}</p>
                    <h1 class="serif mt-4 text-3xl font-bold leading-snug sm:text-4xl">
                        {{ $journal->subtitle ?: 'Scholarly publishing, made clear.' }}
                    </h1>
                    <p class="mt-4 text-white/75 leading-relaxed">
                        {{ \Illuminate\Support\Str::limit(strip_tags((string) $journal->description), 160) ?: 'Log in or enrol to submit manuscripts and follow this journal.' }}
                    </p>
                @else
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-white/70">TFN Journal System</p>
                    <h1 class="serif mt-4 text-3xl font-bold leading-snug sm:text-4xl">
                        Your account for publishing and research.
                    </h1>
                    <p class="mt-4 text-white/75 leading-relaxed">
                        Create an account, launch your own journal, or enrol in one — submissions and peer review for {{ config('tjs.organization') }}.
                    </p>
                @endif
            </div>
            <p class="relative z-10 text-xs text-white/50">
                &copy; {{ date('Y') }} {{ config('tjs.organization') }}
                <span class="mx-1.5 text-white/25">·</span>
                Developed by
                <a href="{{ config('tjs.developer_url') }}" class="text-white/70 hover:text-white underline-offset-2 hover:underline" target="_blank" rel="noopener noreferrer">{{ config('tjs.developer_name') }}</a>
            </p>
        </aside>

        <section class="auth-panel">
            <div class="auth-panel__inner {{ ($wide ?? false) ? 'is-wide' : '' }}">
                <div class="auth-top md:hidden">
                    @if(($journal ?? null) && $journal->logoUrl())
                        <a href="{{ route('journals.show', $journal) }}" class="inline-flex items-center text-[var(--ink)] no-underline">
                            <img src="{{ $journal->logoUrl() }}" alt="{{ $journal->title }}" class="h-9 w-auto rounded-md object-contain">
                        </a>
                    @else
                        <a href="{{ route('home') }}" class="inline-flex items-center text-[var(--ink)] no-underline">
                            <img src="{{ asset('images/tfns-logo.jpeg') }}" alt="{{ config('tjs.organization') }}" class="h-9 w-auto rounded-md object-contain">
                        </a>
                    @endif
                </div>
                <div class="auth-card {{ ($wide ?? false) ? 'is-wide' : '' }}">
                    {{ $slot }}
                </div>
            </div>
        </section>
    </div>
</body>
</html>
