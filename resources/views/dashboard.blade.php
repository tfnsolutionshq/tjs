@extends('layouts.member')

@section('title', 'Dashboard | '.config('tjs.name'))
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Welcome back, '.auth()->user()->name)

@section('page_actions')
    <a href="{{ route('member.journals.create') }}" class="admin-btn admin-btn-primary">Create journal</a>
    <a href="{{ route('author.submissions.create') }}" class="admin-btn admin-btn-ghost">New submission</a>
@endsection

@section('content')
<div class="mj-hero">
    <div class="mj-hero__copy">
        <p class="mj-hero__eyebrow">Publisher workspace</p>
        <h2 class="mj-hero__title admin-serif">Start a journal or continue your work</h2>
        <p class="mj-hero__text">Create a journal site in minutes. You become its manager automatically and can open the manage portal any time.</p>
        <div class="mj-hero__actions">
            <a href="{{ route('member.journals.create') }}" class="mp-btn mp-btn-primary">Create a journal</a>
            <a href="{{ route('journals.index') }}" class="mp-btn mp-btn-secondary" target="_blank" rel="noopener">Browse journals</a>
        </div>
    </div>
    <div class="mj-hero__stats">
        <div>
            <p class="mp-stat__label">Your journals</p>
            <p class="mp-stat__value">{{ number_format($stats['journals']) }}</p>
        </div>
        <div>
            <p class="mp-stat__label">Submissions</p>
            <p class="mp-stat__value">{{ number_format($stats['submissions']) }}</p>
        </div>
        <div>
            <p class="mp-stat__label">In review</p>
            <p class="mp-stat__value">{{ number_format($stats['in_review']) }}</p>
        </div>
        <div>
            <p class="mp-stat__label">Memberships</p>
            <p class="mp-stat__value">{{ number_format($stats['memberships']) }}</p>
        </div>
    </div>
</div>

@if(($managedJournalsPreview ?? collect())->isNotEmpty())
    <section class="mp-card" style="margin-bottom:1.15rem">
        <div class="mp-card__head">
            <div>
                <h2 class="mp-card__title">Journals you manage</h2>
                <p class="mp-card__desc">Open the manage portal or public site.</p>
            </div>
            <a href="{{ route('member.journals.create') }}" class="mp-btn mp-btn-secondary" style="padding:.4rem .7rem;font-size:.75rem">New journal</a>
        </div>
        <div class="mp-card__body mj-managed">
            @foreach($managedJournalsPreview as $managed)
                <div class="mj-managed__row">
                    <div class="min-w-0">
                        <p class="mp-row__title truncate">{{ $managed->title }}</p>
                        <p class="mp-row__meta">/j/{{ $managed->slug }}</p>
                    </div>
                    <div class="mj-managed__actions">
                        <a href="{{ route('journal.manage.dashboard', $managed) }}" class="mp-btn mp-btn-primary" style="padding:.4rem .75rem;font-size:.75rem">Manage</a>
                        <a href="{{ route('journals.show', $managed) }}" class="mp-btn mp-btn-secondary" style="padding:.4rem .75rem;font-size:.75rem" target="_blank" rel="noopener">View</a>
                    </div>
                </div>
            @endforeach
        </div>
        @if(($managedJournalsTotal ?? 0) > 3)
            <div class="mp-card__foot" style="padding:.85rem 1.15rem;border-top:1px solid var(--line)">
                <a href="{{ route('journals.index') }}" class="mp-btn mp-btn-secondary" style="width:100%;justify-content:center;font-size:.8rem">
                    View all journals ({{ number_format($managedJournalsTotal) }})
                </a>
            </div>
        @endif
    </section>
@endif

<div class="mp-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 1.15rem">
    <a href="{{ route('member.journals.create') }}" class="mp-tile">
        <span class="mp-tile__icon" style="background:#eff6ff;color:#1d4ed8">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
        </span>
        <h2 class="mp-tile__title">Create journal</h2>
        <p class="mp-tile__text">Launch a new journal and become its manager.</p>
    </a>
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
        <p class="mp-tile__text">Subscribe to journal memberships and pay membership fees for members-only content.</p>
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
                <a href="{{ route('memberships.index') }}" class="mp-btn mp-btn-secondary" style="margin-top:.75rem;width:fit-content">Browse memberships</a>
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

<style>
    .mj-hero {
        display: grid;
        gap: 1.15rem;
        margin-bottom: 1.25rem;
        padding: 1.35rem 1.25rem;
        border-radius: 1.15rem;
        background:
            radial-gradient(ellipse at 100% 0%, rgba(47,125,225,.18), transparent 45%),
            linear-gradient(135deg, #fff 0%, #f8fbff 100%);
        border: 1px solid var(--line);
        box-shadow: 0 12px 30px rgba(15,23,42,.04);
    }
    @media (min-width: 900px) {
        .mj-hero {
            grid-template-columns: minmax(0, 1.4fr) minmax(14rem, .9fr);
            align-items: center;
            padding: 1.6rem 1.5rem;
        }
    }
    .mj-hero__eyebrow {
        margin: 0;
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .16em;
        text-transform: uppercase;
        color: var(--blue);
    }
    .mj-hero__title {
        margin: .45rem 0 .55rem;
        font-size: clamp(1.35rem, 2.4vw, 1.75rem);
        line-height: 1.25;
        font-weight: 700;
        color: var(--ink);
    }
    .mj-hero__text {
        margin: 0;
        max-width: 36rem;
        color: var(--muted);
        font-size: .92rem;
        line-height: 1.55;
    }
    .mj-hero__actions {
        display: flex;
        flex-wrap: wrap;
        gap: .55rem;
        margin-top: 1rem;
    }
    .mj-hero__stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: .75rem;
    }
    .mj-hero__stats > div {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: .9rem;
        padding: .85rem .95rem;
    }
    .mj-managed { gap: .35rem; }
    .mj-managed__row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .85rem 0;
        border-bottom: 1px solid var(--line);
    }
    .mj-managed__row:last-child { border-bottom: 0; padding-bottom: 0; }
    .mj-managed__actions {
        display: flex;
        flex-wrap: wrap;
        gap: .4rem;
        width: 100%;
    }
    @media (min-width: 520px) {
        .mj-managed__actions { width: auto; }
    }
    .mj-managed__actions .mp-btn { flex: 1; justify-content: center; }
    @media (min-width: 520px) {
        .mj-managed__actions .mp-btn { flex: 0 0 auto; }
    }
</style>
@endsection
