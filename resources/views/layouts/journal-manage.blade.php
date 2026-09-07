<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $seoJournal = ($journal ?? null) instanceof \App\Models\Journal
            ? $journal
            : (request()->route('journal') instanceof \App\Models\Journal ? request()->route('journal') : null);
    @endphp
    <title>@yield('title', ($seoJournal?->title ? $seoJournal->title.' | Manage' : 'Manage | '.config('tjs.name')))</title>
    @include('seo.portal-head', [
        'brandJournal' => $seoJournal,
        'description' => $seoJournal
            ? ('Journal management for '.$seoJournal->title.'.')
            : (config('tjs.full_name').' journal management.'),
    ])
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|libre-baskerville:400,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --ink: #0f172a;
            --muted: #64748b;
            --line: #e8edf3;
            --paper: #f4f6f9;
            --sidebar: #0b1220;
            --blue: #2f7de1;
            --blue-strong: #2563eb;
            --green: #16a34a;
            --amber: #d97706;
            --violet: #7c3aed;
            --rose: #dc2626;
        }
        * { box-sizing: border-box; }
        body.admin-body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
            color: var(--ink);
            background: var(--paper);
            -webkit-font-smoothing: antialiased;
        }
        .admin-serif { font-family: 'Libre Baskerville', Georgia, serif; }
        .admin-shell { min-height: 100vh; }
        @media (min-width: 1024px) {
            .admin-shell { display: grid; grid-template-columns: 15.5rem minmax(0, 1fr); }
        }
        .admin-sidebar {
            position: fixed; inset: 0 auto 0 0; z-index: 60;
            width: min(17.5rem, 88vw);
            background: var(--sidebar);
            color: rgba(255,255,255,.88);
            transform: translateX(-105%);
            transition: transform .28s cubic-bezier(.22,1,.36,1);
            display: flex; flex-direction: column;
            box-shadow: 16px 0 40px rgba(0,0,0,.28);
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .admin-sidebar::-webkit-scrollbar { display: none; }
        .admin-sidebar.is-open { transform: translateX(0); }
        @media (min-width: 1024px) {
            .admin-sidebar { position: sticky; top: 0; height: 100vh; width: auto; transform: none; box-shadow: none; }
        }
        .admin-overlay {
            position: fixed; inset: 0; z-index: 50;
            background: rgba(8,12,20,.55); opacity: 0; pointer-events: none;
            transition: opacity .25s ease; backdrop-filter: blur(2px);
        }
        .admin-overlay.is-open { opacity: 1; pointer-events: auto; }
        @media (min-width: 1024px) { .admin-overlay { display: none; } }

        .admin-brand {
            display: flex; align-items: center; gap: .75rem;
            padding: 1.15rem 1.1rem 1rem;
            text-decoration: none; color: #fff;
        }
        .admin-brand img {
            height: 2.15rem; width: auto; border-radius: .45rem;
            background: #fff; object-fit: contain; padding: 2px;
        }
        .admin-brand__text {
            font-size: .95rem; font-weight: 800; letter-spacing: .04em;
        }
        .admin-brand__text span { color: rgba(255,255,255,.45); font-weight: 700; margin-left: .2rem; }

        .admin-nav {
            flex: 1;
            overflow-y: auto;
            padding: .35rem .7rem 1rem;
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE/Edge */
        }
        .admin-nav::-webkit-scrollbar { display: none; } /* Chrome/Safari */
        .admin-nav__label {
            margin: .95rem .55rem .35rem;
            font-size: .62rem; font-weight: 700; letter-spacing: .16em;
            text-transform: uppercase; color: rgba(255,255,255,.34);
        }
        .admin-nav a {
            display: flex; align-items: center; gap: .65rem;
            padding: .62rem .7rem; border-radius: .65rem;
            color: rgba(255,255,255,.72); text-decoration: none;
            font-size: .9rem; font-weight: 500;
            transition: background .15s ease, color .15s ease, transform .15s ease;
        }
        .admin-nav a:hover { background: rgba(255,255,255,.06); color: #fff; }
        .admin-nav a.is-active {
            background: var(--blue-strong);
            color: #fff;
            box-shadow: 0 8px 18px rgba(37,99,235,.35);
        }
        .admin-nav a svg {
            width: 1.1rem; height: 1.1rem; flex-shrink: 0; opacity: .85;
            stroke-width: 1.6;
            stroke-linecap: round; stroke-linejoin: round;
        }
        .admin-nav__badge {
            margin-left: auto;
            min-width: 1.35rem; height: 1.35rem; padding: 0 .4rem;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 999px; background: var(--blue-strong);
            font-size: .65rem; font-weight: 800; color: #fff;
        }
        .admin-nav a.is-active .admin-nav__badge { background: rgba(255,255,255,.22); }
        .admin-nav__disabled {
            display: flex; align-items: center; gap: .65rem;
            padding: .62rem .7rem; border-radius: .65rem;
            color: rgba(255,255,255,.32); font-size: .9rem; font-weight: 500;
            cursor: not-allowed; user-select: none;
        }
        .admin-nav__disabled svg {
            width: 1.1rem; height: 1.1rem; flex-shrink: 0; opacity: .45;
            stroke-width: 1.6; stroke-linecap: round; stroke-linejoin: round;
        }

        .admin-sidebar-select {
            width: 100%;
            border: 1px solid rgba(255,255,255,.18);
            background-color: rgba(11,18,32,.92);
            color: #fff;
            border-radius: .65rem;
            padding: .5rem .65rem;
            font: inherit;
            font-size: .8rem;
            font-weight: 600;
        }
        .admin-sidebar-select option,
        .admin-sidebar-select optgroup {
            background-color: #0b1220;
            color: #fff;
        }

        .admin-nav__footer { padding: .75rem .7rem 1rem; border-top: 1px solid rgba(255,255,255,.07); }
        .admin-user {
            display: flex; align-items: center; gap: .65rem;
            padding: .5rem .55rem; border-radius: .65rem;
            background: rgba(255,255,255,.04);
        }
        .admin-user__avatar {
            width: 2rem; height: 2rem; border-radius: 999px;
            display: inline-flex; align-items: center; justify-content: center;
            background: var(--blue-strong); color: #fff;
            font-size: .75rem; font-weight: 800;
            overflow: hidden; flex-shrink: 0;
        }
        .admin-user__name { font-size: .84rem; font-weight: 700; color: #fff; line-height: 1.15; }
        .admin-user__role { font-size: .7rem; color: rgba(255,255,255,.42); text-transform: capitalize; }
        .admin-logout {
            margin-top: .45rem; width: 100%;
            display: flex; align-items: center; gap: .55rem;
            padding: .55rem .7rem; border: 0; border-radius: .65rem;
            background: transparent; color: rgba(255,255,255,.62);
            font: inherit; font-size: .86rem; cursor: pointer; text-align: left;
            transition: background .15s ease, color .15s ease;
        }
        .admin-logout:hover { background: rgba(255,255,255,.06); color: #fff; }
        .admin-logout svg { width: 1rem; height: 1rem; }

        .admin-main { min-width: 0; min-height: 100vh; display: flex; flex-direction: column; }
        .admin-topbar {
            position: sticky; top: 0; z-index: 40;
            display: flex; align-items: center; justify-content: space-between; gap: .75rem;
            padding: .9rem 1rem;
            background: rgba(244,246,249,.88);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid transparent;
        }
        @media (min-width: 1024px) { .admin-topbar { padding: 1.1rem 1.5rem .35rem; } }
        .admin-menu-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 2.5rem; height: 2.5rem; border: 1px solid var(--line);
            border-radius: .65rem; background: #fff; color: var(--ink); cursor: pointer;
        }
        @media (min-width: 1024px) { .admin-menu-btn { display: none; } }
        .admin-topbar__title {
            margin: 0; font-size: 1.2rem; font-weight: 800; letter-spacing: -.03em; color: var(--ink);
        }
        @media (min-width: 640px) {
            .admin-topbar__title { font-size: 1.55rem; }
        }
        .admin-topbar__sub { margin: .15rem 0 0; font-size: .78rem; color: var(--muted); }
        @media (min-width: 640px) {
            .admin-topbar__sub { font-size: .84rem; }
        }
        .admin-topbar__actions {
            display: flex; align-items: center; justify-content: flex-end; gap: .4rem;
            flex-shrink: 0; flex-wrap: wrap; max-width: 58%;
        }
        @media (min-width: 640px) {
            .admin-topbar__actions { max-width: none; gap: .5rem; }
        }
        .admin-topbar__actions .admin-chip,
        .admin-topbar__actions .admin-btn {
            padding: .5rem .7rem; font-size: .78rem;
        }
        @media (min-width: 640px) {
            .admin-topbar__actions .admin-chip,
            .admin-topbar__actions .admin-btn {
                padding: .62rem .95rem; font-size: .84rem;
            }
        }
        .admin-topbar__public { display: none; }
        @media (min-width: 640px) {
            .admin-topbar__public { display: inline-flex; }
        }

        .admin-chip, .admin-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .4rem;
            border-radius: .65rem; font-weight: 650; font-size: .84rem;
            padding: .62rem .95rem; text-decoration: none; border: 0; cursor: pointer;
            transition: transform .15s ease, box-shadow .15s ease, filter .15s ease, border-color .15s ease;
        }
        .admin-chip, .admin-btn:hover { transform: translateY(-1px); }
        .admin-chip {
            background: #fff; color: var(--ink); border: 1px solid var(--line);
            box-shadow: 0 1px 2px rgba(15,23,42,.04);
        }
        .admin-btn-primary {
            background: var(--blue-strong); color: #fff;
            box-shadow: 0 10px 20px rgba(37,99,235,.28);
        }
        .admin-btn-primary:hover { filter: brightness(1.05); }
        .admin-btn-secondary {
            background: #fff; color: var(--ink); border: 1px solid var(--line);
        }

        .admin-content { flex: 1; padding: .35rem 1rem 1.75rem; }
        @media (min-width: 640px) { .admin-content { padding: .25rem 1.5rem 2rem; } }
        @media (min-width: 1100px) { .admin-content { padding: .15rem 1.75rem 2.25rem; } }

        .admin-panel {
            background: #fff; border: 1px solid var(--line); border-radius: 1.05rem;
            overflow: hidden; box-shadow: 0 8px 24px rgba(15,23,42,.035);
        }
        .admin-panel__head {
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem;
            padding: 1rem 1.15rem; border-bottom: 1px solid var(--line);
        }
        .admin-panel__body { padding: 1rem 1.15rem; }
        .admin-panel__foot {
            padding: .85rem 1.15rem 1.05rem; text-align: center; border-top: 1px solid var(--line);
        }
        .admin-link {
            color: var(--blue-strong); font-size: .82rem; font-weight: 700; text-decoration: none;
        }
        .admin-link:hover { text-decoration: underline; }

        .admin-stat {
            position: relative; overflow: hidden;
            background: #fff; border: 1px solid var(--line); border-radius: 1.05rem;
            padding: 1.05rem 1.1rem 1.15rem;
            box-shadow: 0 8px 24px rgba(15,23,42,.035);
            transition: transform .2s ease, box-shadow .2s ease;
            text-decoration: none; color: inherit; display: block;
        }
        .admin-stat::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px;
            background: var(--stat-accent, var(--blue));
            border-radius: 1.05rem 0 0 1.05rem;
        }
        .admin-stat:hover { transform: translateY(-2px); box-shadow: 0 14px 30px rgba(15,23,42,.07); }
        .admin-stat__row { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; }
        .admin-stat__label {
            font-size: .72rem; font-weight: 700; letter-spacing: .06em;
            text-transform: uppercase; color: var(--muted);
        }
        .admin-stat__value {
            margin-top: .45rem; font-size: 1.85rem; font-weight: 800;
            letter-spacing: -.04em; line-height: 1; color: var(--ink);
        }
        .admin-stat__meta { margin-top: .45rem; font-size: .78rem; color: var(--muted); }
        .admin-stat__icon {
            width: 2.5rem; height: 2.5rem; border-radius: 999px;
            display: inline-flex; align-items: center; justify-content: center;
            background: var(--stat-soft, #e8f1fc); color: var(--stat-accent, var(--blue));
            flex-shrink: 0;
        }
        .admin-stat__icon svg { width: 1.15rem; height: 1.15rem; }

        .admin-badge {
            display: inline-flex; align-items: center;
            font-size: .68rem; font-weight: 700; letter-spacing: .02em;
            padding: .28rem .55rem; border-radius: 999px;
            background: #e8f1fc; color: #1d4ed8;
        }
        .admin-badge--review { background: #e8f1fc; color: #1d4ed8; }
        .admin-badge--revision { background: #fff4df; color: #b45309; }
        .admin-badge--new { background: #e7f8ee; color: #15803d; }
        .admin-badge--rejected { background: #fde8e8; color: #b91c1c; }
        .admin-badge--approved { background: #e7f8ee; color: #15803d; }

        .admin-action-tile {
            display: flex; flex-direction: column; gap: .15rem;
            padding: .95rem .85rem; border-radius: .95rem;
            border: 1px solid var(--line); background: #fff;
            text-decoration: none; color: inherit;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }
        .admin-action-tile:hover {
            transform: translateY(-2px); border-color: #d5deea;
            box-shadow: 0 12px 24px rgba(15,23,42,.06);
        }
        .admin-action-tile__icon {
            width: 2.15rem; height: 2.15rem; border-radius: 999px; margin-bottom: .45rem;
            display: inline-flex; align-items: center; justify-content: center;
            background: var(--tile-soft, #e8f1fc); color: var(--tile-accent, var(--blue));
        }
        .admin-action-tile__icon svg { width: 1.05rem; height: 1.05rem; }
        .admin-action-tile__title { font-size: .84rem; font-weight: 750; color: var(--ink); }
        .admin-action-tile__sub { font-size: .72rem; color: var(--muted); line-height: 1.35; }

        .admin-hero {
            position: relative; overflow: hidden; border-radius: 1.15rem;
            padding: 1.55rem 1.45rem; color: #fff;
            background: linear-gradient(105deg, #070b14 0%, #0f1b33 38%, #132a52 68%, #1a4b9c 100%);
            box-shadow: 0 18px 40px rgba(15,23,42,.2);
        }
        .admin-hero__wave {
            position: absolute; inset: 0; pointer-events: none; overflow: hidden;
        }
        .admin-hero__wave svg {
            position: absolute;
            right: -8%;
            top: 50%;
            width: 78%;
            height: 160%;
            transform: translateY(-50%);
            opacity: .95;
        }
        .admin-hero__glow {
            position: absolute; inset: 0; pointer-events: none;
            background:
                radial-gradient(ellipse 55% 80% at 78% 45%, rgba(59,130,246,.45), transparent 60%),
                radial-gradient(ellipse 40% 60% at 92% 70%, rgba(96,165,250,.28), transparent 55%);
        }
        .admin-hero__inner {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }
        @media (min-width: 900px) {
            .admin-hero__inner {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                gap: 1.5rem;
            }
        }
        .admin-hero__copy { max-width: 34rem; flex: 1 1 auto; }
        .admin-hero__eyebrow {
            margin: 0;
            font-size: .7rem; font-weight: 800; letter-spacing: .2em;
            text-transform: uppercase; color: #93c5fd;
        }
        .admin-hero__title {
            margin: .55rem 0 0;
            font-family: 'Libre Baskerville', Georgia, serif;
            font-size: clamp(1.55rem, 2.4vw, 2rem);
            font-weight: 700; line-height: 1.15;
        }
        .admin-hero__text {
            margin: .65rem 0 0; max-width: 28rem;
            font-size: .92rem; line-height: 1.55; color: rgba(255,255,255,.72);
        }
        .admin-hero__actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .7rem;
            width: 100%;
            flex: 0 0 auto;
        }
        @media (min-width: 900px) {
            .admin-hero__actions { width: 20.5rem; }
        }
        .admin-hero-card {
            display: flex; flex-direction: column;
            min-height: 8.25rem;
            padding: 1rem;
            border-radius: 1rem;
            text-decoration: none; color: #fff;
            transition: transform .15s ease, filter .15s ease, background .15s ease;
        }
        .admin-hero-card:hover { transform: translateY(-1px); }
        .admin-hero-card--queue {
            border: 1px solid rgba(255,255,255,.16);
            background: rgba(15, 23, 42, .35);
            backdrop-filter: blur(8px);
        }
        .admin-hero-card--queue:hover { background: rgba(15, 23, 42, .48); }
        .admin-hero-card--create {
            background: #2563eb;
            box-shadow: 0 14px 28px rgba(37,99,235,.42);
            justify-content: space-between;
        }
        .admin-hero-card--create:hover { filter: brightness(1.06); }
        .admin-hero-card__icon {
            width: 2.15rem; height: 2.15rem; border-radius: 999px;
            display: inline-flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.12);
        }
        .admin-hero-card__icon svg { width: 1.05rem; height: 1.05rem; }
        .admin-hero-card__label {
            margin-top: .85rem;
            font-size: .68rem; font-weight: 700; letter-spacing: .14em;
            text-transform: uppercase; color: rgba(255,255,255,.55);
        }
        .admin-hero-card__value {
            margin-top: .2rem; display: flex; align-items: baseline; gap: .4rem;
        }
        .admin-hero-card__value strong {
            font-size: 1.65rem; font-weight: 800; letter-spacing: -.03em;
        }
        .admin-hero-card__value span { font-size: .78rem; color: rgba(255,255,255,.6); }
        .admin-hero-card__cta {
            margin-top: auto; padding-top: .65rem;
            font-size: .8rem; font-weight: 700; color: rgba(255,255,255,.92);
        }

        .admin-stack { display: flex; flex-direction: column; gap: 1.15rem; }
        .admin-stats {
            display: grid;
            grid-template-columns: 1fr;
            gap: .85rem;
        }
        @media (min-width: 640px) {
            .admin-stats { grid-template-columns: 1fr 1fr; }
        }
        @media (min-width: 1100px) {
            .admin-stats { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }

        .admin-lower {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        @media (min-width: 1100px) {
            .admin-lower { grid-template-columns: 1.55fr 1fr; align-items: start; }
        }
        .admin-lower__side { display: flex; flex-direction: column; gap: 1rem; }

        .admin-actions-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .7rem;
        }

        .admin-sub-row {
            display: flex; align-items: flex-start; gap: .85rem;
            padding: .95rem 1.15rem;
            text-decoration: none; color: inherit;
            border-top: 1px solid var(--line);
            transition: background .15s ease;
        }
        .admin-sub-row:first-child { border-top: 0; }
        .admin-sub-row:hover { background: #f8fafc; }
        .admin-sub-row__icon {
            width: 2.25rem; height: 2.25rem; border-radius: .7rem; flex-shrink: 0;
            display: inline-flex; align-items: center; justify-content: center;
            background: #eff6ff; color: #2563eb;
        }
        .admin-sub-row__icon svg { width: 1rem; height: 1rem; }
        .admin-sub-row__body { min-width: 0; flex: 1; }
        .admin-sub-row__title {
            margin: 0; font-size: .9rem; font-weight: 750; color: var(--ink);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .admin-sub-row__meta {
            margin: .2rem 0 0; font-size: .75rem; color: var(--muted);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .admin-sub-row__aside {
            display: flex; flex-direction: column; align-items: flex-end; gap: .35rem;
            flex-shrink: 0;
        }
        @media (min-width: 640px) {
            .admin-sub-row { align-items: center; }
            .admin-sub-row__aside { flex-direction: row; align-items: center; gap: .75rem; }
        }
        .admin-sub-row__time { font-size: .75rem; color: var(--muted); white-space: nowrap; }

        .admin-journal-row {
            display: flex; align-items: center; justify-content: space-between; gap: .75rem;
            padding: .85rem 1.15rem; border-top: 1px solid var(--line);
        }
        .admin-journal-row:first-child { border-top: 0; }
        .admin-journal-row__title { margin: 0; font-size: .88rem; font-weight: 750; color: var(--ink); }
        .admin-journal-row__meta { margin: .15rem 0 0; font-size: .74rem; color: var(--muted); }

        .admin-empty {
            padding: 2.5rem 1.25rem; text-align: center;
        }
        .admin-empty__icon {
            width: 3rem; height: 3rem; margin: 0 auto;
            border-radius: 1rem; display: flex; align-items: center; justify-content: center;
            background: #dbeafe; color: #2563eb;
        }
        .admin-empty__icon svg { width: 1.35rem; height: 1.35rem; }
        .admin-empty__title { margin: .85rem 0 0; font-size: .92rem; font-weight: 700; color: var(--ink); }
        .admin-empty__text { margin: .3rem 0 0; font-size: .78rem; color: var(--muted); }

        /* Compat */
        .tjs-serif { font-family: 'Libre Baskerville', Georgia, serif; }
        .tjs-btn { display:inline-flex; align-items:center; gap:.45rem; background:var(--blue-strong); color:#fff; font-weight:600; padding:.65rem 1rem; border-radius:.65rem; text-decoration:none; border:0; cursor:pointer; }
        .tjs-btn-outline { display:inline-flex; align-items:center; gap:.45rem; background:#fff; color:var(--ink); border:1px solid var(--line); font-weight:600; padding:.65rem 1rem; border-radius:.65rem; text-decoration:none; cursor:pointer; }
        .tjs-card { background:#fff; border:1px solid var(--line); border-radius:1rem; }
        .tjs-badge { display:inline-flex; align-items:center; font-size:.68rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; padding:.28rem .55rem; border-radius:999px; background:#e8f1fc; color:#1d4ed8; }
    </style>
</head>
<body class="admin-body" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
    <div class="admin-shell">
        <div class="admin-overlay" :class="{ 'is-open': sidebarOpen }" @click="sidebarOpen = false"></div>

        <aside class="admin-sidebar" :class="{ 'is-open': sidebarOpen }">
            @php
                $jmJournal = $journal ?? request()->route('journal');
                $jmUser = auth()->user();
                $jmManaged = $jmUser?->managedJournals() ?? collect();
                $jmRole = ($jmUser && $jmJournal instanceof \App\Models\Journal)
                    ? $jmUser->journalTeamRole($jmJournal)
                    : null;
                $jmRoleLabel = $jmRole
                    ? \App\Support\JournalTeamRoles::label($jmRole)
                    : 'Journal manager';
                $isPlatformAdminHere = (bool) $jmUser?->canAccessPlatformAdmin();
            @endphp
            <a href="{{ $jmJournal ? route('journal.manage.dashboard', $jmJournal) : route('home') }}" class="admin-brand" @click="sidebarOpen = false">
                @if($jmJournal?->logoUrl())
                    <img src="{{ $jmJournal->logoUrl() }}" alt="{{ $jmJournal->title }}">
                @else
                    <x-platform-logo variant="dark" type="icon" class="h-9 w-auto object-contain" />
                @endif
                <span class="admin-brand__text">Journal <span>MANAGE</span></span>
            </a>

            @if($jmJournal)
                <div style="padding:.15rem .7rem .7rem">
                    <div style="border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.05);border-radius:.85rem;padding:.7rem .75rem">
                        <p style="margin:0;font-size:.62rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.4)">Working in</p>
                        <p style="margin:.3rem 0 0;font-size:.88rem;font-weight:750;color:#fff;line-height:1.3">{{ $jmJournal->title }}</p>
                        <p style="margin:.25rem 0 0;font-size:.72rem;color:rgba(255,255,255,.55)">
                            {{ $jmRoleLabel }}@if($isPlatformAdminHere) · platform admin @endif
                        </p>

                        @if($jmManaged->count() > 1)
                            <label for="jm-switch" style="display:block;margin:.7rem 0 .3rem;font-size:.62rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.4)">Switch journal</label>
                            <select
                                id="jm-switch"
                                class="admin-sidebar-select"
                                onchange="if(this.value) window.location.href=this.value"
                            >
                                @foreach($jmManaged as $managed)
                                    <option
                                        value="{{ $jmUser->journalManageSwitchUrl($managed) }}"
                                        @selected((int) $jmJournal->id === (int) $managed->id)
                                    >{{ $managed->title }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                </div>
            @endif

            <nav class="admin-nav" aria-label="Journal management">
                @if($jmJournal)
                    @php $mgmtLocked = $jmJournal->activationLocked(); @endphp
                    <p class="admin-nav__label">Overview</p>
                    <a href="{{ route('journal.manage.dashboard', $jmJournal) }}" @class(['is-active' => request()->routeIs('journal.manage.dashboard')]) @click="sidebarOpen = false">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z"/></svg>
                        Dashboard
                    </a>
                    <a href="{{ route('journal.manage.activation.show', $jmJournal) }}" @class(['is-active' => request()->routeIs('journal.manage.activation.*')]) @click="sidebarOpen = false">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 3v18M5 10h14M5 14h14"/><path d="M8.5 6.5h7v11h-7z"/></svg>
                        Activation
                        @if($mgmtLocked)
                            <span class="admin-nav__badge">Pay</span>
                        @endif
                    </a>
                    <a href="{{ route('journal.manage.billing.index', $jmJournal) }}" @class(['is-active' => request()->routeIs('journal.manage.billing.*')]) @click="sidebarOpen = false">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M4 7.5h16v11a1 1 0 01-1 1H5a1 1 0 01-1-1v-11z"/><path d="M8 7.5V6a2 2 0 012-2h4a2 2 0 012 2v1.5M8 12h.01M12 12h.01M16 12h.01"/></svg>
                        Payments &amp; Income
                    </a>
                    <a href="{{ route('journal.manage.fees.index', $jmJournal) }}" @class(['is-active' => request()->routeIs('journal.manage.fees.*')]) @click="sidebarOpen = false">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 3v18M5 10h14M5 14h14"/><circle cx="12" cy="12" r="3"/></svg>
                        Fee Catalog
                    </a>
                    <a href="{{ route('journal.manage.payments.gateway', $jmJournal) }}" @class(['is-active' => request()->routeIs('journal.manage.payments.*')]) @click="sidebarOpen = false">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 3v3M12 18v3M3 12h3M18 12h3"/><circle cx="12" cy="12" r="4"/><path d="M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M5.6 18.4l2.1-2.1M16.3 7.7l2.1-2.1"/></svg>
                        Payment Gateway
                    </a>
                    <a href="{{ route('journal.manage.doi.index', $jmJournal) }}" @class(['is-active' => request()->routeIs('journal.manage.doi.*')]) @click="sidebarOpen = false">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M7 7h10v10H7z"/><path d="M9.5 12h5M12 9.5v5"/></svg>
                        DOI
                        @if(($jmJournal->doi_mode ?? null) === 'platform' && (int) $jmJournal->doi_credits_balance <= 0)
                            <span class="admin-nav__badge">0</span>
                        @endif
                    </a>

                    <p class="admin-nav__label">Catalog</p>
                    @if($mgmtLocked)
                        <span class="admin-nav__disabled" title="Pay activation fee to unlock">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M7 3.5h7.5L19 8v12.5a1 1 0 01-1 1H7a1 1 0 01-1-1V4.5a1 1 0 011-1z"/><path d="M14.5 3.5V8H19M9 12h6M9 16h6"/></svg>
                            Articles
                        </span>
                        <span class="admin-nav__disabled" title="Pay activation fee to unlock">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M4 5.5C4 4.67 4.67 4 5.5 4H11v16H5.5A1.5 1.5 0 014 18.5v-13zM20 5.5c0-.83-.67-1.5-1.5-1.5H13v16h5.5a1.5 1.5 0 001.5-1.5v-13z"/><path d="M12 4v16"/></svg>
                            Volumes &amp; Issues
                        </span>
                    @else
                        <a href="{{ route('journal.manage.articles.index', $jmJournal) }}" @class(['is-active' => request()->routeIs('journal.manage.articles.*')]) @click="sidebarOpen = false">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M7 3.5h7.5L19 8v12.5a1 1 0 01-1 1H7a1 1 0 01-1-1V4.5a1 1 0 011-1z"/><path d="M14.5 3.5V8H19M9 12h6M9 16h6"/></svg>
                            Articles
                        </a>
                        <a href="{{ route('journal.manage.volumes.index', $jmJournal) }}" @class(['is-active' => request()->routeIs('journal.manage.volumes.*') || request()->routeIs('journal.manage.issues.*')]) @click="sidebarOpen = false">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M4 5.5C4 4.67 4.67 4 5.5 4H11v16H5.5A1.5 1.5 0 014 18.5v-13zM20 5.5c0-.83-.67-1.5-1.5-1.5H13v16h5.5a1.5 1.5 0 001.5-1.5v-13z"/><path d="M12 4v16"/></svg>
                            Volumes &amp; Issues
                        </a>
                    @endif

                    <p class="admin-nav__label">Editorial</p>
                    @if($mgmtLocked)
                        <span class="admin-nav__disabled" title="Pay activation fee to unlock">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M4 14l2.5-7.5A1 1 0 017.45 6h9.1a1 1 0 01.95.5L20 14"/><path d="M4 14h4.2a2 2 0 011.8 1.1l.4.8a1 1 0 00.9.6h1.4a1 1 0 00.9-.6l.4-.8A2 2 0 0115.8 14H20v4.5a1 1 0 01-1 1H5a1 1 0 01-1-1V14z"/></svg>
                            Submissions
                        </span>
                        <span class="admin-nav__disabled" title="Pay activation fee to unlock">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" d="M12 7.5v9M7.5 12h9"/><path d="M6.5 4.5h11A2 2 0 0119.5 6.5v11a2 2 0 01-2 2h-11a2 2 0 01-2-2v-11a2 2 0 012-2z"/></svg>
                            Announcements
                        </span>
                        <span class="admin-nav__disabled" title="Pay activation fee to unlock">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                            Reviewer Requests
                        </span>
                    @else
                        <a href="{{ route('journal.manage.submissions.index', $jmJournal) }}" @class(['is-active' => request()->routeIs('journal.manage.submissions.*')]) @click="sidebarOpen = false">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M4 14l2.5-7.5A1 1 0 017.45 6h9.1a1 1 0 01.95.5L20 14"/><path d="M4 14h4.2a2 2 0 011.8 1.1l.4.8a1 1 0 00.9.6h1.4a1 1 0 00.9-.6l.4-.8A2 2 0 0115.8 14H20v4.5a1 1 0 01-1 1H5a1 1 0 01-1-1V14z"/></svg>
                            Submissions
                            @if(($journalPendingSubmissions ?? 0) > 0)
                                <span class="admin-nav__badge">{{ $journalPendingSubmissions > 99 ? '99+' : $journalPendingSubmissions }}</span>
                            @endif
                        </a>
                        <a href="{{ route('journal.manage.announcements.index', $jmJournal) }}" @class(['is-active' => request()->routeIs('journal.manage.announcements.*')]) @click="sidebarOpen = false">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" d="M12 7.5v9M7.5 12h9"/><path d="M6.5 4.5h11A2 2 0 0119.5 6.5v11a2 2 0 01-2 2h-11a2 2 0 01-2-2v-11a2 2 0 012-2z"/></svg>
                            Announcements
                        </a>
                        <a href="{{ route('journal.manage.reviewer-requests.index', $jmJournal) }}" @class(['is-active' => request()->routeIs('journal.manage.reviewer-requests.*')]) @click="sidebarOpen = false">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                            Reviewer Requests
                            @if(($journalPendingReviewerRequests ?? 0) > 0)
                                <span class="admin-nav__badge">{{ $journalPendingReviewerRequests > 99 ? '99+' : $journalPendingReviewerRequests }}</span>
                            @endif
                        </a>
                    @endif

                    <p class="admin-nav__label">Access</p>
                    @if($mgmtLocked)
                        <span class="admin-nav__disabled" title="Pay activation fee to unlock">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 12a4 4 0 100-8 4 4 0 000 8z"/><path d="M4.5 20.2a7.5 7.5 0 0115 0"/></svg>
                            Membership Plans
                        </span>
                    @else
                        <a href="{{ route('journal.manage.membership-plans.index', $jmJournal) }}" @class(['is-active' => request()->routeIs('journal.manage.membership-plans.*')]) @click="sidebarOpen = false">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 12a4 4 0 100-8 4 4 0 000 8z"/><path d="M4.5 20.2a7.5 7.5 0 0115 0"/></svg>
                            Membership Plans
                        </a>
                    @endif

                    <p class="admin-nav__label">Journal</p>
                    <a href="{{ route('journal.manage.settings.edit', $jmJournal) }}" @class(['is-active' => request()->routeIs('journal.manage.settings.*') || request()->routeIs('journal.manage.editorial-board.*')]) @click="sidebarOpen = false">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M12 15.5a3.5 3.5 0 100-7 3.5 3.5 0 000 7z"/><path d="M19.4 13a7.8 7.8 0 000-2l2-1.2-2-3.4-2.3.6a7.7 7.7 0 00-1.7-1L15 3h-4l-.4 2.9a7.7 7.7 0 00-1.7 1L6.6 6.4l-2 3.4 2 1.2a7.8 7.8 0 000 2l-2 1.2 2 3.4 2.3-.6a7.7 7.7 0 001.7 1L11 21h4l.4-2.9a7.7 7.7 0 001.7-1l2.3.6 2-3.4-2-1.2z"/></svg>
                        Settings
                    </a>

                    <p class="admin-nav__label">Context</p>
                    <a href="{{ route('journals.show', $jmJournal) }}" target="_blank" rel="noopener" @click="sidebarOpen = false">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M14 5h5v5"/><path d="M10 14L19 5"/><path d="M19 13.5V18a1 1 0 01-1 1H6a1 1 0 01-1-1V6a1 1 0 011-1h4.5"/></svg>
                        Public Journal
                    </a>
                    @if($isPlatformAdminHere)
                        <a href="{{ route('admin.dashboard') }}" @click="sidebarOpen = false" style="background:rgba(47,125,225,.16);color:#93c5fd">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z"/></svg>
                            Exit to Platform Admin
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" @click="sidebarOpen = false">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z"/></svg>
                            Member Portal
                        </a>
                    @endif
                    <a href="{{ route('profile.edit') }}" @click="sidebarOpen = false">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="10" r="3"/><path d="M6.8 18.2a5.5 5.5 0 0110.4 0"/></svg>
                        Profile
                    </a>
                @endif
            </nav>

            <div class="admin-nav__footer">
                <div class="admin-user">
                    <span class="admin-user__avatar">
                        @if(auth()->user()?->avatarUrl())
                            <img src="{{ auth()->user()->avatarUrl() }}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:999px;display:block">
                        @else
                            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                        @endif
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="admin-user__name truncate">{{ auth()->user()->name }}</p>
                        <p class="admin-user__role">{{ $jmRoleLabel }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="admin-logout">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l3 3m0 0l-3 3m3-3H9"/></svg>
                        Log Out
                    </button>
                </form>
            </div>
        </aside>

        <div class="admin-main">
            <header class="admin-topbar">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" class="admin-menu-btn" @click="sidebarOpen = true" aria-label="Open menu">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                    </button>
                    <div class="min-w-0">
                        <h1 class="admin-topbar__title truncate">@yield('page_title', 'Admin')</h1>
                        @hasSection('page_subtitle')
                            <p class="admin-topbar__sub truncate">@yield('page_subtitle')</p>
                        @endif
                    </div>
                </div>
                <div class="admin-topbar__actions">
                    @if(auth()->user()?->canAccessPlatformAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="admin-btn admin-btn-secondary">Exit to platform admin</a>
                    @endif
                    <a href="{{ isset($journal) ? route('journals.show', $journal) : route('home') }}" class="admin-chip admin-topbar__public" target="_blank" rel="noopener">
                        Public Journal
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H18v4.5M18 6l-7 7M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4"/></svg>
                    </a>
                    @yield('page_actions')
                </div>
            </header>

            <x-flash />

            @if(($jmJournal ?? null) instanceof \App\Models\Journal && $jmJournal->activationLocked())
                <div style="margin:0 1rem 0;padding:.9rem 1.05rem;border:1px solid #fde68a;border-radius:.9rem;background:#fffbeb;display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;justify-content:space-between">
                    <div style="min-width:12rem">
                        <p style="margin:0;font-weight:800;color:#92400e;font-size:.9rem">
                            {{ $jmJournal->activation_status === \App\Support\JournalActivation::STATUS_EXPIRED ? 'Activation expired' : 'Activation fee unpaid' }}
                        </p>
                        <p style="margin:.25rem 0 0;font-size:.78rem;color:#a16207;line-height:1.4">
                            This journal is not listed publicly. Major management menus are locked until activation is current.
                            @unless(\App\Support\JournalActivation::enabled())
                                Platform activation payments are currently off — contact a platform admin if you need this unlocked.
                            @endunless
                        </p>
                    </div>
                    @if(\App\Support\JournalActivation::enabled())
                        <a href="{{ route('journal.manage.activation.show', $jmJournal) }}" class="admin-btn admin-btn-primary" style="white-space:nowrap">Pay / renew</a>
                    @else
                        <a href="{{ route('journal.manage.activation.show', $jmJournal) }}" class="admin-btn admin-btn-secondary" style="white-space:nowrap">Activation status</a>
                    @endif
                </div>
            @endif

            <main class="admin-content">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
