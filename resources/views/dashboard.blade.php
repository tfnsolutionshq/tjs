@extends('layouts.member')

@section('title', 'Dashboard | '.config('tjs.name'))
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Welcome back, '.auth()->user()->name)

@section('page_actions')
    <a href="{{ route('author.submissions.create') }}" class="admin-btn admin-btn-primary">New submission</a>
@endsection

@section('content')
<div class="mp-stats">
    <div class="mp-stat">
        <p class="mp-stat__label">Submissions</p>
        <p class="mp-stat__value">{{ number_format($stats['submissions']) }}</p>
    </div>
    <div class="mp-stat">
        <p class="mp-stat__label">In review</p>
        <p class="mp-stat__value">{{ number_format($stats['in_review']) }}</p>
    </div>
    <div class="mp-stat">
        <p class="mp-stat__label">Accepted</p>
        <p class="mp-stat__value">{{ number_format($stats['accepted']) }}</p>
    </div>
    <div class="mp-stat">
        <p class="mp-stat__label">Active memberships</p>
        <p class="mp-stat__value">{{ number_format($stats['memberships']) }}</p>
    </div>
</div>

<div class="mp-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 1.15rem">
    <a href="{{ route('author.submissions.index') }}" class="mp-tile">
        <span class="mp-tile__icon">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3.5h7.5L19 8v12.5a1 1 0 01-1 1H7a1 1 0 01-1-1V4.5a1 1 0 011-1z"/><path stroke-linecap="round" d="M14.5 3.5V8H19M9 12h6M9 16h6"/></svg>
        </span>
        <h2 class="mp-tile__title">My submissions</h2>
        <p class="mp-tile__text">Submit manuscripts and track peer review.</p>
    </a>
    @if(auth()->user()->canAccessReviewQueue())
        <a href="{{ route('reviewer.reviews.index') }}" class="mp-tile">
            <span class="mp-tile__icon" style="background:#ecfdf5;color:#047857">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
            </span>
            <h2 class="mp-tile__title">Review queue</h2>
            <p class="mp-tile__text">Open assignments waiting for your decision.</p>
        </a>
    @endif
    <a href="{{ route('memberships.index') }}" class="mp-tile">
        <span class="mp-tile__icon" style="background:#fef3c7;color:#b45309">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M12 12a4 4 0 100-8 4 4 0 000 8z"/><path d="M4.5 20.2a7.5 7.5 0 0115 0"/></svg>
        </span>
        <h2 class="mp-tile__title">Memberships</h2>
        <p class="mp-tile__text">Manage access plans for members-only content.</p>
    </a>
    <a href="{{ route('profile.edit') }}" class="mp-tile">
        <span class="mp-tile__icon" style="background:#f1f5f9;color:#475569">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="10" r="3"/><path stroke-linecap="round" d="M6.8 18.2a5.5 5.5 0 0110.4 0"/><circle cx="12" cy="12" r="9"/></svg>
        </span>
        <h2 class="mp-tile__title">Profile</h2>
        <p class="mp-tile__text">Update your details and password.</p>
    </a>
</div>

<div class="mp-grid mp-grid--2">
    <section class="mp-card">
        <div class="mp-card__head">
            <div>
                <h2 class="mp-card__title">Recent submissions</h2>
                <p class="mp-card__desc">Your latest manuscript activity.</p>
            </div>
            <a href="{{ route('author.submissions.index') }}" class="mp-btn mp-btn-secondary" style="padding:.4rem .7rem;font-size:.75rem">View all</a>
        </div>
        <div class="mp-card__body">
            @forelse($recentSubmissions as $submission)
                <a class="mp-row" href="{{ route('author.submissions.show', $submission) }}">
                    <div class="min-w-0">
                        <p class="mp-row__title truncate">{{ $submission->title }}</p>
                        <p class="mp-row__meta">{{ $submission->journal?->title }} · {{ optional($submission->updated_at)->diffForHumans() }}</p>
                    </div>
                    <span class="mp-badge mp-badge--{{ $submission->status }}">{{ str_replace('_', ' ', $submission->status) }}</span>
                </a>
            @empty
                <p class="mp-empty">No submissions yet.</p>
                <a href="{{ route('author.submissions.create') }}" class="mp-btn mp-btn-primary" style="margin-top:.75rem;width:fit-content">New submission</a>
            @endforelse
        </div>
    </section>

    <section class="mp-card">
        <div class="mp-card__head">
            <div>
                <h2 class="mp-card__title">Your memberships</h2>
                <p class="mp-card__desc">Active access to members-only content.</p>
            </div>
            <a href="{{ route('memberships.index') }}" class="mp-btn mp-btn-secondary" style="padding:.4rem .7rem;font-size:.75rem">Manage</a>
        </div>
        <div class="mp-card__body">
            @forelse($activeMemberships as $membership)
                <div class="mp-row">
                    <div>
                        <p class="mp-row__title">{{ $membership->plan?->name ?? 'Membership' }}</p>
                        <p class="mp-row__meta">
                            Ends {{ optional($membership->ends_at)->format('M j, Y') }}
                            @if($membership->journal) · {{ $membership->journal->title }} @endif
                        </p>
                    </div>
                    <span class="mp-badge mp-badge--{{ $membership->scope === 'platform' ? 'platform' : 'journal' }}">{{ $membership->scope }}</span>
                </div>
            @empty
                <p class="mp-empty">No active memberships yet.</p>
                <a href="{{ route('memberships.index') }}" class="mp-btn mp-btn-secondary" style="margin-top:.75rem;width:fit-content">View plans</a>
            @endforelse
        </div>
    </section>
</div>

@if(($reviewerRequestJournals ?? collect())->isNotEmpty())
    <section class="mp-card" style="margin-top:1.15rem">
        <div class="mp-card__head">
            <div>
                <h2 class="mp-card__title">Become a reviewer</h2>
                <p class="mp-card__desc">Request to join the peer-review team for journals you belong to.</p>
            </div>
        </div>
        <div class="mp-card__body" style="display:grid;gap:1rem">
            @foreach($reviewerRequestJournals as $row)
                <x-reviewer-request-panel
                    :journal="$row['journal']"
                    :can-request="$row['canRequest']"
                    :reason="$row['reason']"
                    :pending="$row['pending']"
                    :latest="$row['latest']"
                />
            @endforeach
        </div>
    </section>
@endif
@endsection
