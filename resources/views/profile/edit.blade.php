@extends($profileLayout === 'admin' ? 'layouts.admin' : ($profileLayout === 'journal-manage' ? 'layouts.journal-manage' : ($profileLayout === 'member' ? 'layouts.member' : 'layouts.public')))

@section('title', 'Profile | '.config('tjs.name'))
@section('page_title', 'Profile')
@section('page_subtitle', 'Account details and security')

@section('content')
@php
    $roleLabel = match (true) {
        $user->isAdmin() => 'Platform admin',
        $managedJournals->isNotEmpty() => 'Journal manager',
        $user->role === 'reviewer' => 'Reviewer',
        default => 'Member',
    };
    $avatarUrl = $user->avatarUrl();
    $initial = $user->avatarInitial();
    $badgeClass = $user->isAdmin()
        ? 'pf-badge--admin'
        : ($managedJournals->isNotEmpty() ? 'pf-badge--manager' : '');
@endphp

<style>
    .pf {
        max-width: 72rem;
        display: grid;
        gap: 1rem;
    }
    @media (min-width: 1080px) {
        .pf-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(17rem, .75fr);
            gap: 1.15rem;
            align-items: start;
        }
        .pf-side { position: sticky; top: 1rem; display: grid; gap: 1rem; }
    }
    .pf-main { display: grid; gap: 1rem; min-width: 0; }
    .pf-side { display: grid; gap: 1rem; min-width: 0; }

    .pf-hero {
        position: relative;
        overflow: hidden;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 1.15rem;
        border-radius: 1.15rem;
        padding: 1.35rem 1.4rem;
        color: #fff;
        background: linear-gradient(125deg, #0b1220 0%, #132a52 48%, #1d4ed8 115%);
        box-shadow: 0 18px 40px rgba(15,23,42,.16);
    }
    .pf-hero::after {
        content: '';
        position: absolute;
        right: -6%;
        top: -40%;
        width: 16rem;
        height: 16rem;
        border-radius: 999px;
        background: rgba(147,197,253,.18);
        pointer-events: none;
    }
    .pf-avatar {
        position: relative;
        z-index: 1;
        width: 4.5rem;
        height: 4.5rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.45rem;
        color: #fff;
        background: rgba(255,255,255,.14);
        border: 2px solid rgba(255,255,255,.28);
        overflow: hidden;
        flex-shrink: 0;
    }
    .pf-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .pf-hero__copy { position: relative; z-index: 1; min-width: 0; flex: 1; }
    .pf-hero__eyebrow {
        margin: 0;
        font-size: .68rem;
        font-weight: 800;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: #93c5fd;
    }
    .pf-hero__name {
        margin: .35rem 0 0;
        font-size: clamp(1.25rem, 2vw, 1.55rem);
        font-weight: 800;
        letter-spacing: -.02em;
        line-height: 1.2;
    }
    .pf-hero__meta {
        margin: .35rem 0 0;
        font-size: .86rem;
        color: rgba(255,255,255,.72);
        overflow-wrap: anywhere;
    }
    .pf-hero__row {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
        align-items: center;
        margin-top: .65rem;
    }
    .pf-badge {
        display: inline-flex;
        align-items: center;
        font-size: .68rem;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
        padding: .28rem .6rem;
        border-radius: 999px;
        background: rgba(255,255,255,.14);
        color: #fff;
        border: 1px solid rgba(255,255,255,.16);
    }
    .pf-badge--admin { background: rgba(16,185,129,.22); border-color: rgba(167,243,208,.35); }
    .pf-badge--manager { background: rgba(59,130,246,.28); }
    .pf-links {
        position: relative;
        z-index: 1;
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
        margin-left: auto;
    }
    .pf-journals {
        display: flex;
        flex-wrap: wrap;
        gap: .4rem;
        width: 100%;
        margin-top: .15rem;
    }
    .pf-journals a {
        font-size: .74rem;
        font-weight: 700;
        color: #dbeafe;
        text-decoration: none;
        background: rgba(255,255,255,.1);
        padding: .3rem .55rem;
        border-radius: .5rem;
        border: 1px solid rgba(255,255,255,.12);
    }
    .pf-journals a:hover { background: rgba(255,255,255,.16); }

    .pf-card {
        background: #fff;
        border: 1px solid var(--line, #e6eaef);
        border-radius: 1.05rem;
        box-shadow: 0 8px 24px rgba(15,23,42,.035);
        overflow: visible;
    }
    .pf-card__head {
        padding: 1.05rem 1.2rem .2rem;
    }
    .pf-card__title {
        margin: 0;
        font-size: .98rem;
        font-weight: 800;
        color: var(--ink, #1c2430);
        letter-spacing: -.01em;
    }
    .pf-card__desc {
        margin: .3rem 0 0;
        font-size: .82rem;
        color: var(--muted, #5f6b7a);
        line-height: 1.45;
    }
    .pf-card__body {
        padding: 1rem 1.2rem 1.25rem;
        display: grid;
        gap: 1rem;
    }
    .pf-field label,
    .pf-photo label,
    .pf-field .tjs-label-row,
    .pf-photo .tjs-label-row {
        display: inline-flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .35rem;
        margin-bottom: .4rem;
        font-size: .78rem;
        font-weight: 700;
        color: #334155;
        letter-spacing: .01em;
    }
    .pf-field .pf-hint,
    .pf-photo .pf-hint {
        margin: .35rem 0 0;
        font-size: .72rem;
        color: var(--muted, #5f6b7a);
        line-height: 1.4;
    }
    .pf-field .pf-error,
    .pf-photo .pf-error {
        margin: .35rem 0 0;
        font-size: .75rem;
        font-weight: 600;
        color: #b91c1c;
    }
    .pf-photo {
        display: flex;
        flex-wrap: wrap;
        gap: 1.1rem;
        align-items: center;
        padding: 1.05rem 1.1rem;
        border: 1px solid #dbeafe;
        border-radius: 1rem;
        background: linear-gradient(135deg, #f8fbff 0%, #eff6ff 100%);
    }
    .pf-photo__preview {
        width: 5.25rem;
        height: 5.25rem;
        border-radius: 999px;
        overflow: hidden;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(145deg, #2f7de1, #1f5fb8);
        color: #fff;
        font-weight: 800;
        font-size: 1.55rem;
        box-shadow: 0 10px 22px rgba(37,99,235,.25);
        border: 3px solid #fff;
    }
    .pf-photo__preview img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .pf-photo__body { min-width: 0; flex: 1; }
    .pf-photo__title {
        margin: 0 0 .15rem;
        font-size: .9rem;
        font-weight: 800;
        color: var(--ink, #1c2430);
    }
    .pf-photo__actions {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
        margin-top: .55rem;
    }
    .pf-chips {
        display: flex;
        flex-wrap: wrap;
        gap: .35rem;
        margin-top: .55rem;
    }
    .pf-chip {
        display: inline-flex;
        align-items: center;
        padding: .22rem .5rem;
        border-radius: 999px;
        background: rgba(255,255,255,.85);
        border: 1px solid #bfdbfe;
        font-size: .68rem;
        font-weight: 700;
        color: #1e40af;
    }
    .pf-input,
    .pf-textarea {
        width: 100%;
        border: 1px solid #e2e8f0;
        background: #fff;
        border-radius: .7rem;
        padding: .7rem .85rem;
        font: inherit;
        font-size: .9rem;
        color: var(--ink, #1c2430);
        outline: none;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .pf-textarea { min-height: 7.5rem; resize: vertical; line-height: 1.5; }
    .pf-input:hover,
    .pf-textarea:hover { border-color: #cbd5e1; }
    .pf-input:focus,
    .pf-textarea:focus {
        border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(47,125,225,.14);
    }
    .pf-grid { display: grid; gap: .95rem; }
    @media (min-width: 720px) {
        .pf-grid--2 { grid-template-columns: 1fr 1fr; }
    }
    .pf-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .65rem;
        margin-top: .15rem;
        padding-top: .25rem;
    }
    .pf-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        border: 0;
        border-radius: .65rem;
        padding: .7rem 1.05rem;
        font: inherit;
        font-size: .88rem;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        transition: filter .15s ease, opacity .15s ease, box-shadow .15s ease, background .15s ease;
    }
    .pf-btn:disabled { opacity: .72; cursor: wait; pointer-events: none; }
    .pf-btn.is-busy { opacity: .72; pointer-events: none; }
    .pf-btn-primary {
        background: #2563eb;
        color: #fff;
        box-shadow: 0 8px 18px rgba(37,99,235,.24);
    }
    .pf-btn-primary:hover { filter: brightness(1.05); }
    .pf-btn-danger {
        background: #fff;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }
    .pf-btn-danger:hover { background: #fef2f2; }
    .pf-btn-secondary {
        background: #fff;
        color: var(--ink, #1c2430);
        border: 1px solid var(--line, #e6eaef);
    }
    .pf-btn-secondary:hover { border-color: #cbd5e1; }
    .pf-btn--light {
        background: rgba(255,255,255,.12);
        color: #fff;
        border: 1px solid rgba(255,255,255,.2);
    }
    .pf-btn--light:hover { background: rgba(255,255,255,.18); }
    .pf-saved { font-size: .82rem; font-weight: 600; color: #047857; }
    .pf-spinner {
        width: .95rem;
        height: .95rem;
        border: 2px solid rgba(255,255,255,.35);
        border-top-color: #fff;
        border-radius: 999px;
        animation: pf-spin .7s linear infinite;
    }
    @keyframes pf-spin { to { transform: rotate(360deg); } }
    .pf-toggle {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        padding: .95rem 1rem;
        border: 1px solid #e2e8f0;
        border-radius: .95rem;
        background: #f8fafc;
    }
    .pf-toggle__label {
        margin: 0;
        font-size: .88rem;
        font-weight: 700;
        color: var(--ink, #1c2430);
    }
    .pf-toggle__hint {
        margin: .25rem 0 0;
        font-size: .76rem;
        color: var(--muted, #5f6b7a);
        line-height: 1.4;
    }
    .pf-note {
        margin: 0;
        font-size: .78rem;
        color: var(--muted, #5f6b7a);
        line-height: 1.5;
    }
    .pf-note strong { color: var(--ink, #1c2430); }
    .pf-note + .pf-note { margin-top: .65rem; }
    .pf-danger { border-color: #fecaca; }
    .pf-danger .pf-card__head {
        background: #fef2f2;
        border-bottom: 1px solid #fecaca;
        padding-bottom: .85rem;
        border-radius: 1.05rem 1.05rem 0 0;
    }
    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0,0,0,0);
        white-space: nowrap;
        border: 0;
    }
    [x-cloak] { display: none !important; }
</style>

<div class="pf">
    <section class="pf-hero">
        <span class="pf-avatar" aria-hidden="true">
            @if($avatarUrl)
                <img src="{{ $avatarUrl }}" alt="">
            @else
                {{ $initial }}
            @endif
        </span>
        <div class="pf-hero__copy">
            <p class="pf-hero__eyebrow">Your account</p>
            <h2 class="pf-hero__name">{{ $user->name }}</h2>
            <p class="pf-hero__meta">{{ $user->email }}</p>
            <div class="pf-hero__row">
                <span class="pf-badge {{ $badgeClass }}">{{ $roleLabel }}</span>
                @if($user->affiliation)
                    <span class="pf-badge">{{ $user->affiliation }}</span>
                @endif
            </div>
            @if($managedJournals->isNotEmpty() && ! $user->isAdmin())
                <div class="pf-journals">
                    @foreach($managedJournals->take(4) as $j)
                        <a href="{{ route('journal.manage.dashboard', $j) }}">{{ $j->title }}</a>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="pf-links">
            @if($user->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="pf-btn pf-btn--light">Admin console</a>
            @elseif($managedJournals->isNotEmpty())
                <a href="{{ route('journal.manage.dashboard', $managedJournals->first()) }}" class="pf-btn pf-btn--light">Manage journal</a>
            @else
                <a href="{{ route('dashboard') }}" class="pf-btn pf-btn--light">Dashboard</a>
            @endif
        </div>
    </section>

    <div class="pf-layout">
        <div class="pf-main">
            <section class="pf-card">
                @include('profile.partials.update-profile-information-form')
            </section>
        </div>

        <aside class="pf-side">
            <section class="pf-card">
                <div class="pf-card__head">
                    <h2 class="pf-card__title">Tips</h2>
                    <p class="pf-card__desc">Keep your public details accurate for editors and co-authors.</p>
                </div>
                <div class="pf-card__body">
                    <p class="pf-note"><strong>Photo.</strong> A clear headshot helps reviewers and journal pages recognize you.</p>
                    <p class="pf-note"><strong>ORCID.</strong> Optional, but useful for citation and researcher identity.</p>
                    <p class="pf-note"><strong>Email changes.</strong> Updating your email requires re-verification before full access continues.</p>
                </div>
            </section>

            <section class="pf-card">
                @include('profile.partials.update-password-form')
            </section>

            @unless($user->isAdmin())
                <section class="pf-card pf-danger">
                    @include('profile.partials.delete-user-form')
                </section>
            @endunless
        </aside>
    </div>
</div>
@endsection
