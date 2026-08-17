@extends('layouts.journal-manage')

@section('title', 'Reviewer requests | '.$journal->title)
@section('page_title', 'Reviewer requests')
@section('page_subtitle', 'Applications from journal members')

@section('page_actions')
    <a href="{{ route('journals.reviewers', $journal) }}" class="admin-chip admin-topbar__public" target="_blank" rel="noopener">
        Public reviewers
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H18v4.5M18 6l-7 7M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4"/></svg>
    </a>
@endsection

@section('content')
@php
    $statusLabel = fn (string $status) => match ($status) {
        \App\Models\JournalReviewerRequest::STATUS_APPROVED => 'Approved',
        \App\Models\JournalReviewerRequest::STATUS_REJECTED => 'Declined',
        \App\Models\JournalReviewerRequest::STATUS_WITHDRAWN => 'Withdrawn',
        default => ucfirst($status),
    };
@endphp

<style>
    .jrr-stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .75rem;
        margin-bottom: 1rem;
    }
    @media (max-width: 640px) {
        .jrr-stats { grid-template-columns: 1fr; }
    }
    .jrr-stat {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: .95rem;
        padding: .9rem 1rem;
        box-shadow: 0 8px 24px rgba(15,23,42,.035);
    }
    .jrr-stat__label {
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--muted);
    }
    .jrr-stat__value {
        margin-top: .35rem;
        font-size: 1.55rem;
        font-weight: 800;
        letter-spacing: -.03em;
        color: var(--ink);
        line-height: 1;
    }
    .jrr-stat__hint {
        margin-top: .35rem;
        font-size: .72rem;
        color: var(--muted);
        line-height: 1.35;
    }
    .jrr-tip {
        display: flex;
        gap: .85rem;
        align-items: flex-start;
        margin-bottom: 1rem;
        padding: .95rem 1.05rem;
        border-radius: .95rem;
        border: 1px solid #dbeafe;
        background: linear-gradient(135deg, #f8fbff 0%, #eff6ff 100%);
    }
    .jrr-tip__icon {
        flex-shrink: 0;
        width: 2.25rem;
        height: 2.25rem;
        border-radius: .7rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #dbeafe;
        color: #2563eb;
    }
    .jrr-tip__icon svg { width: 1.05rem; height: 1.05rem; }
    .jrr-tip__title {
        margin: 0;
        font-size: .84rem;
        font-weight: 800;
        color: var(--ink);
    }
    .jrr-tip__text {
        margin: .3rem 0 0;
        font-size: .78rem;
        line-height: 1.5;
        color: #475569;
    }
    .jrr-grid {
        display: grid;
        gap: 1rem;
        align-items: start;
    }
    @media (min-width: 960px) {
        .jrr-grid { grid-template-columns: 1.15fr .85fr; }
    }
    .jrr-item {
        padding: 1rem 1.15rem;
        border-top: 1px solid var(--line);
    }
    .jrr-item:first-child { border-top: 0; }
    .jrr-item:hover { background: #f8fafc; }
    .jrr-item__top {
        display: flex;
        flex-wrap: wrap;
        gap: .75rem;
        align-items: flex-start;
        justify-content: space-between;
    }
    .jrr-item__person {
        display: flex;
        gap: .75rem;
        align-items: flex-start;
        min-width: 0;
        flex: 1;
    }
    .jrr-item__avatar {
        flex-shrink: 0;
        width: 2.35rem;
        height: 2.35rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #eff6ff;
        color: #2563eb;
        font-size: .78rem;
        font-weight: 800;
    }
    .jrr-item__name {
        margin: 0;
        font-size: .92rem;
        font-weight: 800;
        color: var(--ink);
        line-height: 1.35;
    }
    .jrr-item__meta {
        margin: .2rem 0 0;
        font-size: .74rem;
        color: var(--muted);
        line-height: 1.45;
        overflow-wrap: anywhere;
    }
    .jrr-item__message {
        margin: .75rem 0 0 3.1rem;
        padding: .75rem .85rem;
        border-radius: .75rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        font-size: .78rem;
        line-height: 1.45;
        color: #475569;
        white-space: pre-wrap;
    }
    @media (max-width: 640px) {
        .jrr-item__message { margin-left: 0; }
    }
    .jrr-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: .22rem .55rem;
        font-size: .68rem;
        font-weight: 800;
        letter-spacing: .02em;
        flex-shrink: 0;
    }
    .jrr-badge--pending { background: #fef3c7; color: #92400e; }
    .jrr-badge--approved { background: #dcfce7; color: #166534; }
    .jrr-badge--rejected { background: #fee2e2; color: #991b1b; }
    .jrr-badge--withdrawn { background: #f1f5f9; color: #64748b; }
    .jrr-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
        margin-top: .85rem;
        margin-left: 3.1rem;
    }
    @media (max-width: 640px) {
        .jrr-actions { margin-left: 0; }
    }
    .jrr-actions .admin-btn { padding: .45rem .8rem; font-size: .78rem; }
    .jrr-decline {
        width: 100%;
        margin-top: .55rem;
        padding: .75rem;
        border: 1px solid #e2e8f0;
        border-radius: .75rem;
        background: #fff;
    }
    .jrr-note {
        width: 100%;
        border: 1px solid #e2e8f0;
        border-radius: .65rem;
        padding: .55rem .7rem;
        font: inherit;
        font-size: .78rem;
        resize: vertical;
        min-height: 2.75rem;
        outline: none;
    }
    .jrr-note:focus {
        border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    }
    .jrr-decline__actions {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
        margin-top: .55rem;
    }
    [x-cloak] { display: none !important; }
</style>

<div class="jrr-stats">
    <div class="jrr-stat">
        <p class="jrr-stat__label">Pending</p>
        <p class="jrr-stat__value">{{ number_format($stats['pending']) }}</p>
        <p class="jrr-stat__hint">Awaiting your decision</p>
    </div>
    <div class="jrr-stat">
        <p class="jrr-stat__label">Approved</p>
        <p class="jrr-stat__value">{{ number_format($stats['approved']) }}</p>
        <p class="jrr-stat__hint">Members added to the review team</p>
    </div>
    <div class="jrr-stat">
        <p class="jrr-stat__label">Declined</p>
        <p class="jrr-stat__value">{{ number_format($stats['declined']) }}</p>
        <p class="jrr-stat__hint">Applications not accepted</p>
    </div>
</div>

<div class="jrr-tip">
    <span class="jrr-tip__icon" aria-hidden="true">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
    </span>
    <div>
        <p class="jrr-tip__title">How reviewer requests work</p>
        <p class="jrr-tip__text">
            Journal members apply from their dashboard. Approving adds them to the peer-review team so you can assign manuscripts.
            Declining keeps them as a member — they can apply again later.
        </p>
    </div>
</div>

<div class="jrr-grid">
    <section class="admin-panel">
        <div class="admin-panel__head">
            <div>
                <h2 style="margin:0;font-size:.95rem;font-weight:800;color:var(--ink)">Pending requests</h2>
                <p style="margin:.25rem 0 0;font-size:.76rem;color:var(--muted)">Approve members who want to join the peer-review team.</p>
            </div>
            @if($pending->isNotEmpty())
                <span class="jrr-badge jrr-badge--pending">{{ $pending->count() }} waiting</span>
            @endif
        </div>

        @forelse($pending as $request)
            <article class="jrr-item" x-data="{ declining: false }">
                <div class="jrr-item__top">
                    <div class="jrr-item__person">
                        <span class="jrr-item__avatar" aria-hidden="true">{{ strtoupper(substr($request->user->name, 0, 1)) }}</span>
                        <div class="min-w-0">
                            <p class="jrr-item__name">{{ $request->user->name }}</p>
                            <p class="jrr-item__meta">
                                {{ $request->user->email }}
                                @if($request->user->affiliation) · {{ $request->user->affiliation }} @endif
                                @if($request->user->orcid) · ORCID {{ $request->user->orcid }} @endif
                                · requested {{ $request->created_at?->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                    <span class="jrr-badge jrr-badge--pending">Pending</span>
                </div>

                @if($request->message)
                    <div class="jrr-item__message">{{ $request->message }}</div>
                @endif

                <div class="jrr-actions">
                    <form method="POST" action="{{ route('journal.manage.reviewer-requests.approve', [$journal, $request]) }}">
                        @csrf
                        <button type="submit" class="admin-btn admin-btn-primary">Approve reviewer</button>
                    </form>
                    <button type="button" class="admin-btn admin-btn-secondary" @click="declining = !declining" x-text="declining ? 'Cancel decline' : 'Decline…'"></button>

                    <div class="jrr-decline" x-show="declining" x-cloak x-transition>
                        <form method="POST" action="{{ route('journal.manage.reviewer-requests.reject', [$journal, $request]) }}">
                            @csrf
                            <textarea name="admin_note" class="jrr-note" placeholder="Optional reason if declining — only shared internally unless you choose to communicate it."></textarea>
                            <div class="jrr-decline__actions">
                                <button type="submit" class="admin-btn admin-btn-secondary" style="color:#b91c1c">Confirm decline</button>
                            </div>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <div class="admin-empty">
                <div class="admin-empty__icon">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="admin-empty__title">No pending requests</p>
                <p class="admin-empty__text">When members apply to review for this journal, their applications will appear here for approval.</p>
            </div>
        @endforelse
    </section>

    <section class="admin-panel">
        <div class="admin-panel__head">
            <div>
                <h2 style="margin:0;font-size:.95rem;font-weight:800;color:var(--ink)">Recent decisions</h2>
                <p style="margin:.25rem 0 0;font-size:.76rem;color:var(--muted)">Latest approved, declined, or withdrawn applications.</p>
            </div>
        </div>

        @forelse($recent as $request)
            <article class="jrr-item">
                <div class="jrr-item__top">
                    <div class="jrr-item__person">
                        <span class="jrr-item__avatar" aria-hidden="true">{{ strtoupper(substr($request->user->name, 0, 1)) }}</span>
                        <div class="min-w-0">
                            <p class="jrr-item__name">{{ $request->user->name }}</p>
                            <p class="jrr-item__meta">
                                {{ $request->user->email }}
                                · {{ $request->reviewed_at?->format('M j, Y g:i A') ?: $request->updated_at?->format('M j, Y g:i A') }}
                                @if($request->reviewer) · by {{ $request->reviewer->name }} @endif
                            </p>
                        </div>
                    </div>
                    <span class="jrr-badge jrr-badge--{{ $request->status }}">{{ $statusLabel($request->status) }}</span>
                </div>
                @if($request->admin_note && $request->status === \App\Models\JournalReviewerRequest::STATUS_REJECTED)
                    <div class="jrr-item__message">{{ $request->admin_note }}</div>
                @endif
            </article>
        @empty
            <div class="admin-empty">
                <div class="admin-empty__icon">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="admin-empty__title">No decisions yet</p>
                <p class="admin-empty__text">Approved and declined applications will show up here once you process pending requests.</p>
            </div>
        @endforelse
    </section>
</div>
@endsection
