<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('tjs.full_name') }}</title>
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
    </style>
</head>
<body>
    <x-flash />
    <div class="auth-shell">
        <aside class="auth-brand">
            <div>
                <a href="{{ route('home') }}" class="inline-flex items-center text-white no-underline relative z-10">
                    <img src="{{ asset('images/tfns-logo.jpeg') }}" alt="{{ config('tjs.organization') }}" class="h-10 w-auto rounded-md bg-white object-contain p-0.5">
                </a>
            </div>
            <div class="relative z-10 max-w-md pb-8">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-white/70">TFN Journal System</p>
                <h1 class="serif mt-4 text-3xl font-bold leading-snug sm:text-4xl">
                    Modernize your journal publishing process.
                </h1>
                <p class="mt-4 text-white/75 leading-relaxed">
                    Access themed journal sites, submit manuscripts, and manage peer review — built for {{ config('tjs.organization') }}.
                </p>
            </div>
            <p class="relative z-10 text-xs text-white/50">&copy; {{ date('Y') }} {{ config('tjs.organization') }}</p>
        </aside>

        <section class="auth-panel">
            <div class="w-full max-w-[420px]">
                <div class="auth-top md:hidden">
                    <a href="{{ route('home') }}" class="inline-flex items-center text-[var(--ink)] no-underline">
                        <img src="{{ asset('images/tfns-logo.jpeg') }}" alt="{{ config('tjs.organization') }}" class="h-9 w-auto rounded-md object-contain">
                    </a>
                </div>
                <div class="auth-card">
                    {{ $slot }}
                </div>
            </div>
        </section>
    </div>
</body>
</html>
