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
    $initial = strtoupper(substr($user->name, 0, 1));
@endphp

<style>
    .pf { max-width: 44rem; display: grid; gap: 1rem; }
    .pf-hero {
        display: flex; flex-wrap: wrap; align-items: center; gap: 1rem;
        background: #fff; border: 1px solid var(--line, #e6eaef); border-radius: 1.05rem;
        padding: 1.15rem 1.25rem; box-shadow: 0 8px 24px rgba(15,23,42,.035);
    }
    .pf-avatar {
        width: 3.5rem; height: 3.5rem; border-radius: 999px; display: inline-flex;
        align-items: center; justify-content: center; font-weight: 800; font-size: 1.25rem;
        color: #fff; background: linear-gradient(145deg, #2f7de1, #1f5fb8);
        box-shadow: 0 8px 18px rgba(47,125,225,.28);
    }
    .pf-hero__name { margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--ink, #1c2430); letter-spacing: -.02em; }
    .pf-hero__meta { margin: .25rem 0 0; font-size: .84rem; color: var(--muted, #5f6b7a); }
    .pf-badge {
        display: inline-flex; align-items: center; margin-top: .45rem;
        font-size: .68rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
        padding: .28rem .55rem; border-radius: 999px; background: #e8f1fc; color: #1d4ed8;
    }
    .pf-badge--admin { background: #ecfdf5; color: #047857; }
    .pf-badge--manager { background: #eff6ff; color: #1d4ed8; }
    .pf-links { display: flex; flex-wrap: wrap; gap: .45rem; margin-left: auto; }
    .pf-card {
        background: #fff; border: 1px solid var(--line, #e6eaef); border-radius: 1.05rem;
        box-shadow: 0 8px 24px rgba(15,23,42,.035); overflow: hidden;
    }
    .pf-card__head { padding: 1.05rem 1.2rem .15rem; }
    .pf-card__title { margin: 0; font-size: .98rem; font-weight: 800; color: var(--ink, #1c2430); letter-spacing: -.01em; }
    .pf-card__desc { margin: .3rem 0 0; font-size: .82rem; color: var(--muted, #5f6b7a); line-height: 1.45; }
    .pf-card__body { padding: 1rem 1.2rem 1.25rem; display: grid; gap: .9rem; }
    .pf-field label {
        display: block; margin-bottom: .4rem; font-size: .78rem; font-weight: 700;
        color: #334155; letter-spacing: .01em;
    }
    .pf-field .pf-hint { margin: .35rem 0 0; font-size: .72rem; color: var(--muted, #5f6b7a); line-height: 1.4; }
    .pf-field .pf-error { margin: .35rem 0 0; font-size: .75rem; font-weight: 600; color: #b91c1c; }
    .pf-input, .pf-textarea {
        width: 100%; border: 1px solid #e2e8f0; background: #fff; border-radius: .7rem;
        padding: .7rem .85rem; font: inherit; font-size: .9rem; color: var(--ink, #1c2430);
        outline: none; transition: border-color .15s ease, box-shadow .15s ease;
    }
    .pf-textarea { min-height: 6.5rem; resize: vertical; line-height: 1.5; }
    .pf-input:focus, .pf-textarea:focus {
        border-color: #93c5fd; box-shadow: 0 0 0 3px rgba(47,125,225,.14);
    }
    .pf-grid { display: grid; gap: .9rem; }
    @media (min-width: 720px) {
        .pf-grid--2 { grid-template-columns: 1fr 1fr; }
    }
    .pf-actions { display: flex; flex-wrap: wrap; align-items: center; gap: .65rem; margin-top: .25rem; }
    .pf-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
        border: 0; border-radius: .65rem; padding: .7rem 1.05rem; font: inherit; font-size: .88rem;
        font-weight: 700; cursor: pointer; text-decoration: none;
        transition: filter .15s ease, opacity .15s ease, box-shadow .15s ease;
    }
    .pf-btn:disabled { opacity: .72; cursor: wait; pointer-events: none; }
    .pf-btn-primary {
        background: #2f7de1; color: #fff;
        box-shadow: 0 8px 18px rgba(47,125,225,.22);
    }
    .pf-btn-primary:hover { filter: brightness(1.05); }
    .pf-btn-danger {
        background: #fff; color: #b91c1c; border: 1px solid #fecaca;
    }
    .pf-btn-danger:hover { background: #fef2f2; }
    .pf-btn-secondary {
        background: #fff; color: var(--ink, #1c2430); border: 1px solid var(--line, #e6eaef);
    }
    .pf-saved { font-size: .82rem; font-weight: 600; color: #047857; }
    .pf-spinner {
        width: .95rem; height: .95rem; border: 2px solid rgba(255,255,255,.35);
        border-top-color: #fff; border-radius: 999px; animation: pf-spin .7s linear infinite;
    }
    @keyframes pf-spin { to { transform: rotate(360deg); } }
    .pf-toggle {
        display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;
        padding: .85rem 1rem; border: 1px solid #e2e8f0; border-radius: .85rem; background: #f8fafc;
    }
    .pf-toggle__label { margin: 0; font-size: .88rem; font-weight: 700; color: var(--ink, #1c2430); }
    .pf-toggle__hint { margin: .25rem 0 0; font-size: .76rem; color: var(--muted, #5f6b7a); line-height: 1.4; }
    .pf-danger { border-color: #fecaca; }
    .pf-danger .pf-card__head { background: #fef2f2; border-bottom: 1px solid #fecaca; padding-bottom: .85rem; }
    .pf-journals { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .55rem; }
    .pf-journals a {
        font-size: .74rem; font-weight: 700; color: #1d4ed8; text-decoration: none;
        background: #eff6ff; padding: .3rem .55rem; border-radius: .5rem;
    }
    .pf-journals a:hover { text-decoration: underline; }
    [x-cloak] { display: none !important; }
</style>

<div class="pf">
    <section class="pf-hero">
        <span class="pf-avatar" aria-hidden="true">{{ $initial }}</span>
        <div class="min-w-0">
            <h2 class="pf-hero__name">{{ $user->name }}</h2>
            <p class="pf-hero__meta">{{ $user->email }}</p>
            <span class="pf-badge {{ $user->isAdmin() ? 'pf-badge--admin' : ($managedJournals->isNotEmpty() ? 'pf-badge--manager' : '') }}">{{ $roleLabel }}</span>
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
                <a href="{{ route('admin.dashboard') }}" class="pf-btn pf-btn-secondary">Admin console</a>
            @elseif($managedJournals->isNotEmpty())
                <a href="{{ route('journal.manage.dashboard', $managedJournals->first()) }}" class="pf-btn pf-btn-secondary">Manage journal</a>
            @else
                <a href="{{ route('dashboard') }}" class="pf-btn pf-btn-secondary">Dashboard</a>
            @endif
        </div>
    </section>

    <section class="pf-card">
        @include('profile.partials.update-profile-information-form')
    </section>

    <section class="pf-card">
        @include('profile.partials.update-password-form')
    </section>

    @unless($user->isAdmin())
        <section class="pf-card pf-danger">
            @include('profile.partials.delete-user-form')
        </section>
    @endunless
</div>
@endsection
