@extends('layouts.admin')

@section('title', 'Admin dashboard | '.config('tjs.name'))
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Platform overview')

@section('page_actions')
    <a href="{{ route('admin.journals.create') }}" class="admin-btn admin-btn-primary">
        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 8v8M8 12h8"/></svg>
        New journal
    </a>
@endsection

@section('content')
@php
    $firstName = explode(' ', auth()->user()->name)[0];
    $pending = (int) ($counts['pending_submissions'] ?? 0);

    $statusBadge = function (string $status): array {
        return match ($status) {
            'under_review' => ['Under review', 'admin-badge--review'],
            'revision_requested', 'resubmitted' => ['Revision', 'admin-badge--revision'],
            'submitted' => ['New', 'admin-badge--new'],
            'approved' => ['Approved', 'admin-badge--approved'],
            'rejected' => ['Rejected', 'admin-badge--rejected'],
            default => [ucfirst(str_replace('_', ' ', $status)), 'admin-badge--review'],
        };
    };
@endphp

<div class="admin-stack">
    <section class="admin-hero">
        <div class="admin-hero__wave" aria-hidden="true">
            <div class="admin-hero__glow"></div>
            <svg viewBox="0 0 800 400" preserveAspectRatio="xMidYMid slice" fill="none">
                <defs>
                    <linearGradient id="adminWaveA" x1="0" y1="200" x2="800" y2="180" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#60a5fa" stop-opacity="0"/>
                        <stop offset="35%" stop-color="#3b82f6" stop-opacity=".55"/>
                        <stop offset="65%" stop-color="#93c5fd" stop-opacity=".85"/>
                        <stop offset="100%" stop-color="#2563eb" stop-opacity=".15"/>
                    </linearGradient>
                    <linearGradient id="adminWaveB" x1="100" y1="0" x2="700" y2="400" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#1d4ed8" stop-opacity="0"/>
                        <stop offset="50%" stop-color="#60a5fa" stop-opacity=".45"/>
                        <stop offset="100%" stop-color="#93c5fd" stop-opacity="0"/>
                    </linearGradient>
                    <filter id="adminWaveBlur" x="-20%" y="-20%" width="140%" height="140%">
                        <feGaussianBlur stdDeviation="18"/>
                    </filter>
                </defs>
                <path filter="url(#adminWaveBlur)" stroke="url(#adminWaveA)" stroke-width="56" stroke-linecap="round"
                      d="M40 270 C 180 240, 260 120, 390 150 C 520 180, 580 300, 760 210"/>
                <path filter="url(#adminWaveBlur)" stroke="url(#adminWaveB)" stroke-width="34" stroke-linecap="round"
                      d="M80 300 C 220 280, 300 160, 430 190 C 560 220, 620 310, 780 250"/>
                <path stroke="rgba(147,197,253,.35)" stroke-width="2.5" stroke-linecap="round"
                      d="M60 255 C 190 225, 270 130, 400 155 C 530 180, 590 285, 760 220"/>
            </svg>
        </div>
        <div class="admin-hero__inner">
            <div class="admin-hero__copy">
                <p class="admin-hero__eyebrow">TFN Journal System</p>
                <h2 class="admin-hero__title">Welcome back, {{ $firstName }}.</h2>
                <p class="admin-hero__text">
                    Manage journals, peer review, articles, and membership access from one console.
                </p>
            </div>

            <div class="admin-hero__actions">
                <a href="{{ route('admin.submissions.index') }}" class="admin-hero-card admin-hero-card--queue">
                    <span class="admin-hero-card__icon">
                        {{-- stacked layers --}}
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5l8 4-8 4-8-4 8-4z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 12.5l8 4 8-4"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 16.5l8 4 8-4"/></svg>
                    </span>
                    <span class="admin-hero-card__label">Queue</span>
                    <span class="admin-hero-card__value">
                        <strong>{{ $pending }}</strong>
                        <span>pending</span>
                    </span>
                    <span class="admin-hero-card__cta">Review queue →</span>
                </a>

                <a href="{{ route('admin.articles.create') }}" class="admin-hero-card admin-hero-card--create">
                    <span class="admin-hero-card__icon">
                        {{-- pencil in circle --}}
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M14.8 8.2l1 1-5.3 5.3H9.5v-1.5l5.3-5.3z"/></svg>
                    </span>
                    <span class="admin-hero-card__cta" style="margin-top:auto">New article →</span>
                </a>
            </div>
        </div>
    </section>

    <section class="admin-stats">
        <a href="{{ route('admin.journals.index') }}" class="admin-stat" style="--stat-accent:#2563eb;--stat-soft:#dbeafe">
            <div class="admin-stat__row">
                <div>
                    <p class="admin-stat__label">Journals</p>
                    <p class="admin-stat__value">{{ number_format($counts['journals'] ?? 0) }}</p>
                </div>
                <span class="admin-stat__icon">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5C4 4.67 4.67 4 5.5 4H11v16H5.5A1.5 1.5 0 014 18.5v-13zM20 5.5c0-.83-.67-1.5-1.5-1.5H13v16h5.5a1.5 1.5 0 001.5-1.5v-13z"/><path stroke-linecap="round" d="M12 4v16"/></svg>
                </span>
            </div>
            <p class="admin-stat__meta">{{ $counts['volumes'] ?? 0 }} volumes · {{ $counts['issues'] ?? 0 }} issues</p>
        </a>

        <a href="{{ route('admin.articles.index') }}" class="admin-stat" style="--stat-accent:#16a34a;--stat-soft:#dcfce7">
            <div class="admin-stat__row">
                <div>
                    <p class="admin-stat__label">Articles</p>
                    <p class="admin-stat__value">{{ number_format($counts['articles'] ?? 0) }}</p>
                </div>
                <span class="admin-stat__icon">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3.5h7.5L19 8v12.5a1 1 0 01-1 1H7a1 1 0 01-1-1V4.5a1 1 0 011-1z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14.5 3.5V8H19M9 12h6M9 16h6"/></svg>
                </span>
            </div>
            <p class="admin-stat__meta">{{ $counts['published_articles'] ?? 0 }} published</p>
        </a>

        <a href="{{ route('admin.submissions.index') }}" class="admin-stat" style="--stat-accent:#d97706;--stat-soft:#fef3c7">
            <div class="admin-stat__row">
                <div>
                    <p class="admin-stat__label">Submissions</p>
                    <p class="admin-stat__value">{{ number_format($counts['submissions'] ?? 0) }}</p>
                </div>
                <span class="admin-stat__icon">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M4 14l2.5-7.5A1 1 0 017.45 6h9.1a1 1 0 01.95.5L20 14"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 14h4.2a2 2 0 011.8 1.1l.4.8a1 1 0 00.9.6h1.4a1 1 0 00.9-.6l.4-.8A2 2 0 0115.8 14H20v4.5a1 1 0 01-1 1H5a1 1 0 01-1-1V14z"/></svg>
                </span>
            </div>
            <p class="admin-stat__meta">{{ $pending }} under review</p>
        </a>

        <a href="{{ route('admin.users.index') }}" class="admin-stat" style="--stat-accent:#64748b;--stat-soft:#e2e8f0">
            <div class="admin-stat__row">
                <div>
                    <p class="admin-stat__label">Users</p>
                    <p class="admin-stat__value">{{ number_format($counts['users'] ?? 0) }}</p>
                </div>
                <span class="admin-stat__icon">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11a3.5 3.5 0 100-7 3.5 3.5 0 000 7zM8 12a3.5 3.5 0 100-7 3.5 3.5 0 000 7z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.8 19.5a5.7 5.7 0 0110.4 0M10.8 19.5a5.7 5.7 0 0110.4 0"/></svg>
                </span>
            </div>
            <p class="admin-stat__meta">{{ $counts['active_memberships'] ?? 0 }} active memberships</p>
        </a>
    </section>

    <section class="admin-lower">
        <div class="admin-panel">
            <div class="admin-panel__head">
                <h3 style="margin:0;font-size:.95rem;font-weight:800;color:var(--ink)">Recent submissions</h3>
                <a href="{{ route('admin.submissions.index') }}" class="admin-link">View all</a>
            </div>

            @if($recentSubmissions->isEmpty())
                <div class="admin-empty">
                    <div class="admin-empty__icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M7 4h7l5 5v11a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>
                    </div>
                    <p class="admin-empty__title">No submissions yet</p>
                    <p class="admin-empty__text">New manuscripts will appear here for review.</p>
                </div>
            @else
                <div>
                    @foreach($recentSubmissions as $submission)
                        @php [$label, $badgeClass] = $statusBadge($submission->status); @endphp
                        <a href="{{ route('admin.submissions.show', $submission) }}" class="admin-sub-row">
                            <span class="admin-sub-row__icon">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M7 4h7l5 5v11a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>
                            </span>
                            <div class="admin-sub-row__body">
                                <p class="admin-sub-row__title">{{ $submission->title }}</p>
                                <p class="admin-sub-row__meta">
                                    {{ $submission->author?->name ?? 'Author' }}
                                    @if($submission->journal) · {{ $submission->journal->title }} @endif
                                </p>
                            </div>
                            <div class="admin-sub-row__aside">
                                <span class="admin-badge {{ $badgeClass }}">{{ $label }}</span>
                                <span class="admin-sub-row__time">{{ $submission->created_at?->diffForHumans(short: true) }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
                <div class="admin-panel__foot">
                    <a href="{{ route('admin.submissions.index') }}" class="admin-link">View all submissions →</a>
                </div>
            @endif
        </div>

        <div class="admin-lower__side">
            <div class="admin-panel">
                <div class="admin-panel__head">
                    <h3 style="margin:0;font-size:.95rem;font-weight:800;color:var(--ink)">Quick actions</h3>
                </div>
                <div class="admin-panel__body">
                    <div class="admin-actions-grid">
                        <a href="{{ route('admin.journals.create') }}" class="admin-action-tile" style="--tile-accent:#2563eb;--tile-soft:#dbeafe">
                            <span class="admin-action-tile__icon">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5C4 4.67 4.67 4 5.5 4H11v16H5.5A1.5 1.5 0 014 18.5v-13zM20 5.5c0-.83-.67-1.5-1.5-1.5H13v16h5.5a1.5 1.5 0 001.5-1.5v-13z"/><path stroke-linecap="round" d="M12 4v16"/></svg>
                            </span>
                            <span class="admin-action-tile__title">Create journal</span>
                            <span class="admin-action-tile__sub">New title & theme</span>
                        </a>
                        <a href="{{ route('admin.articles.create') }}" class="admin-action-tile" style="--tile-accent:#16a34a;--tile-soft:#dcfce7">
                            <span class="admin-action-tile__icon">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3.5h7.5L19 8v12.5a1 1 0 01-1 1H7a1 1 0 01-1-1V4.5a1 1 0 011-1z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14.5 3.5V8H19M12 13v5M9.5 15.5H14.5"/></svg>
                            </span>
                            <span class="admin-action-tile__title">Add article</span>
                            <span class="admin-action-tile__sub">Publish to catalog</span>
                        </a>
                        <a href="{{ route('admin.membership-plans.index') }}" class="admin-action-tile" style="--tile-accent:#7c3aed;--tile-soft:#ede9fe">
                            <span class="admin-action-tile__icon">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4l2.2 4.5 5 .7-3.6 3.5.9 5L12 15.8 7.5 17.7l.9-5L4.8 9.2l5-.7L12 4z"/></svg>
                            </span>
                            <span class="admin-action-tile__title">Membership</span>
                            <span class="admin-action-tile__sub">Plans & access</span>
                        </a>
                        <a href="{{ route('admin.journals.index') }}" class="admin-action-tile" style="--tile-accent:#d97706;--tile-soft:#fef3c7">
                            <span class="admin-action-tile__icon">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5l8 4-8 4-8-4 8-4z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 12.5l8 4 8-4"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 16.5l8 4 8-4"/></svg>
                            </span>
                            <span class="admin-action-tile__title">Volumes</span>
                            <span class="admin-action-tile__sub">Issues & archive</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="admin-panel">
                <div class="admin-panel__head">
                    <h3 style="margin:0;font-size:.95rem;font-weight:800;color:var(--ink)">Journals</h3>
                    <a href="{{ route('admin.journals.index') }}" class="admin-link">View all</a>
                </div>
                <div>
                    @forelse($journals as $journal)
                        <div class="admin-journal-row">
                            <div style="min-width:0">
                                <p class="admin-journal-row__title">{{ $journal->title }}</p>
                                <p class="admin-journal-row__meta">{{ $journal->articles_count }} articles · {{ $journal->volumes_count }} volumes</p>
                            </div>
                            <a href="{{ route('admin.journals.edit', $journal) }}" class="admin-link">Edit</a>
                        </div>
                    @empty
                        <div class="admin-empty">
                            <p class="admin-empty__text">No journals yet.</p>
                            <a href="{{ route('admin.journals.create') }}" class="admin-btn admin-btn-primary" style="margin-top:1rem">Create journal</a>
                        </div>
                    @endforelse
                </div>
                @if($journals->isNotEmpty())
                    <div class="admin-panel__foot">
                        <a href="{{ route('admin.journals.index') }}" class="admin-link">Manage journals →</a>
                    </div>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection
