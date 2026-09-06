@extends('layouts.member')

@section('title', 'Memberships | '.config('tjs.name'))
@section('page_title', 'Memberships')
@section('page_subtitle', 'Subscribe to journal memberships and manage your access to members-only content')

@section('content')
@if (session('status'))
    <div class="mp-flash">{{ session('status') }}</div>
@endif

@php
    $activeCount = $activeMemberships->count();
    $hasPlatform = $activeMemberships->contains(fn ($m) => $m->scope === 'platform');
@endphp

<style>
    .ms-stats {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .75rem;
        margin-bottom: 1.15rem;
    }
    @media (min-width: 760px) {
        .ms-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    .ms-stat {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: .95rem;
        padding: .95rem 1rem;
        box-shadow: 0 8px 24px rgba(15,23,42,.035);
    }
    .ms-stat__label {
        margin: 0;
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--muted);
    }
    .ms-stat__value {
        margin: .35rem 0 0;
        font-size: 1.45rem;
        font-weight: 800;
        letter-spacing: -.03em;
        color: var(--ink);
        line-height: 1;
    }
    .ms-section { margin-bottom: 1.15rem; }
    .ms-section__head { margin-bottom: .75rem; }
    .ms-section__title {
        margin: 0;
        font-size: 1rem;
        font-weight: 800;
        color: var(--ink);
        letter-spacing: -.01em;
    }
    .ms-section__desc {
        margin: .3rem 0 0;
        font-size: .82rem;
        color: var(--muted);
        line-height: 1.45;
    }
    .ms-grid {
        display: grid;
        gap: .85rem;
    }
    @media (min-width: 860px) {
        .ms-grid--2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    .ms-access {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 1rem;
        padding: 1rem 1.05rem;
        box-shadow: 0 8px 24px rgba(15,23,42,.035);
    }
    .ms-access__top {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: .65rem;
    }
    .ms-access__title {
        margin: 0;
        font-size: .95rem;
        font-weight: 800;
        color: var(--ink);
    }
    .ms-access__meta {
        margin: .25rem 0 0;
        font-size: .76rem;
        color: var(--muted);
        line-height: 1.45;
    }
    .ms-access__badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: .24rem .58rem;
        font-size: .66rem;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
        background: #ecfdf5;
        color: #047857;
    }
    .ms-access__badge--platform { background: #ecfdf5; color: #047857; }
    .ms-access__badge--journal { background: #eef4fc; color: #255ea8; }
    .ms-progress {
        margin-top: .85rem;
    }
    .ms-progress__bar {
        height: .45rem;
        border-radius: 999px;
        background: #e2e8f0;
        overflow: hidden;
    }
    .ms-progress__fill {
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #2563eb, #38bdf8);
    }
    .ms-progress__fill.is-low { background: linear-gradient(90deg, #d97706, #fbbf24); }
    .ms-progress__label {
        display: flex;
        justify-content: space-between;
        gap: .5rem;
        margin-top: .4rem;
        font-size: .72rem;
        color: var(--muted);
    }
    .ms-access__actions {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
        margin-top: .85rem;
    }
    .ms-plan {
        display: flex;
        flex-direction: column;
        gap: .85rem;
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 1rem;
        padding: 1rem 1.05rem;
        box-shadow: 0 8px 24px rgba(15,23,42,.035);
    }
    @media (min-width: 640px) {
        .ms-plan { flex-direction: row; align-items: center; justify-content: space-between; }
    }
    .ms-plan__main {
        display: flex;
        align-items: flex-start;
        gap: .85rem;
        flex: 1;
        min-width: 0;
    }
    .ms-plan__logo {
        width: 2.75rem;
        height: 2.75rem;
        object-fit: contain;
        border-radius: .55rem;
        background: #f8fafc;
        border: 1px solid var(--line);
        flex-shrink: 0;
    }
    .ms-plan__initials {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: .55rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: .72rem;
        letter-spacing: .02em;
        background: #eef4fc;
        color: #255ea8;
        flex-shrink: 0;
    }
    .ms-plan__body {
        min-width: 0;
        flex: 1;
    }
    .ms-plan__price {
        margin: .45rem 0 0;
        font-size: 1.15rem;
        font-weight: 800;
        color: var(--ink);
        letter-spacing: -.02em;
    }
    .ms-plan__price span {
        font-size: .78rem;
        font-weight: 650;
        color: var(--muted);
    }
    .ms-plan__features {
        display: flex;
        flex-wrap: wrap;
        gap: .35rem;
        margin-top: .55rem;
    }
    .ms-chip {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: .22rem .55rem;
        font-size: .68rem;
        font-weight: 700;
        background: #f8fafc;
        color: #475569;
        border: 1px solid #e2e8f0;
    }
    .ms-covered {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: .95rem;
        padding: .85rem 1rem;
    }
    .ms-covered__title {
        margin: 0;
        font-size: .88rem;
        font-weight: 750;
        color: #334155;
    }
    .ms-covered__meta {
        margin: .2rem 0 0;
        font-size: .74rem;
        color: var(--muted);
    }
    .ms-empty {
        background: #fff;
        border: 1px dashed #cbd5e1;
        border-radius: 1rem;
        padding: 1.35rem 1.1rem;
        text-align: center;
    }
    .ms-empty__title {
        margin: 0;
        font-size: .92rem;
        font-weight: 800;
        color: var(--ink);
    }
    .ms-empty__text {
        margin: .4rem auto 0;
        max-width: 28rem;
        font-size: .82rem;
        color: var(--muted);
        line-height: 1.5;
    }
    .ms-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        margin-bottom: .85rem;
    }
    .ms-search {
        position: relative;
        flex: 1 1 14rem;
        max-width: 24rem;
    }
    .ms-search__input {
        width: 100%;
        border: 1px solid var(--line);
        border-radius: .75rem;
        padding: .62rem .85rem .62rem 2.35rem;
        font: inherit;
        font-size: .84rem;
        background: #fff;
    }
    .ms-search__input:focus {
        outline: none;
        border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(59,130,246,.12);
    }
    .ms-search__icon {
        position: absolute;
        left: .75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        pointer-events: none;
    }
    .ms-browse {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
        margin-bottom: .85rem;
    }
    .ms-browse__btn {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        border: 1px solid #dbeafe;
        background: #eff6ff;
        color: #1d4ed8;
        border-radius: 999px;
        padding: .45rem .8rem;
        font: inherit;
        font-size: .78rem;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
    }
    .ms-browse__btn:hover { background: #dbeafe; }
    .ms-browse__btn--ghost {
        background: #fff;
        border-color: #e2e8f0;
        color: #475569;
    }
    .ms-browse__btn--ghost:hover { background: #f8fafc; }
    .ms-badge-featured {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: .18rem .48rem;
        font-size: .62rem;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
        background: #dbeafe;
        color: #1d4ed8;
    }
    .ms-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        margin-top: .85rem;
        padding-top: .85rem;
        border-top: 1px solid var(--line);
    }
    .ms-pagination__meta {
        font-size: .76rem;
        color: var(--muted);
    }
</style>

<div class="ms-stats">
    <div class="ms-stat">
        <p class="ms-stat__label">Active memberships</p>
        <p class="ms-stat__value">{{ number_format($activeCount) }}</p>
    </div>
    <div class="ms-stat">
        <p class="ms-stat__label">Available memberships</p>
        <p class="ms-stat__value">{{ number_format($availableCount) }}</p>
    </div>
    <div class="ms-stat">
        <p class="ms-stat__label">Platform access</p>
        <p class="ms-stat__value">{{ $hasPlatform ? 'Yes' : 'No' }}</p>
    </div>
</div>

<section class="ms-section">
    <div class="ms-section__head">
        <h2 class="ms-section__title">Your active access</h2>
        <p class="ms-section__desc">Memberships currently unlocking members-only articles.</p>
    </div>

    @forelse($activeMemberships as $membership)
        @php
            $totalDays = max(1, $membership->starts_at?->diffInDays($membership->ends_at) ?? 1);
            $daysLeft = max(0, (int) now()->diffInDays($membership->ends_at, false));
            $progress = min(100, max(4, ($daysLeft / $totalDays) * 100));
            $low = $daysLeft <= 30;
        @endphp
        <article class="ms-access" style="margin-bottom:.85rem">
            <div class="ms-access__top">
                <div>
                    <h3 class="ms-access__title">{{ $membership->plan?->name ?? 'Membership' }}</h3>
                    <p class="ms-access__meta">
                        @if($membership->scope === 'platform')
                            Full platform access
                        @elseif($membership->journal)
                            {{ $membership->journal->title }}
                        @else
                            Journal membership
                        @endif
                        · expires {{ optional($membership->ends_at)->format('M j, Y') }}
                    </p>
                </div>
                <span class="ms-access__badge ms-access__badge--{{ $membership->scope === 'platform' ? 'platform' : 'journal' }}">
                    Active · {{ $membership->scope }}
                </span>
            </div>

            <div class="ms-progress">
                <div class="ms-progress__bar" aria-hidden="true">
                    <div class="ms-progress__fill {{ $low ? 'is-low' : '' }}" style="width: {{ $progress }}%"></div>
                </div>
                <div class="ms-progress__label">
                    <span>{{ $daysLeft }} {{ Str::plural('day', $daysLeft) }} remaining</span>
                    <span>{{ round($progress) }}% of term left</span>
                </div>
            </div>

            @if($membership->journal)
                <div class="ms-access__actions">
                    <a href="{{ route('journals.show', $membership->journal) }}" class="mp-btn mp-btn-secondary" style="padding:.5rem .8rem;font-size:.78rem">View journal</a>
                </div>
            @endif
        </article>
    @empty
        <div class="ms-empty">
            <p class="ms-empty__title">No active membership yet</p>
            <p class="ms-empty__text">Subscribe to a journal membership below to read members-only full text—or choose platform-wide access.</p>
        </div>
    @endforelse
</section>

@if($coveredPlans->isNotEmpty())
    <section class="ms-section">
        <div class="ms-section__head">
            <h2 class="ms-section__title">Already covered</h2>
            <p class="ms-section__desc">These memberships are already included in your current access — no need to pay again.</p>
        </div>
        <div class="ms-grid ms-grid--2">
            @foreach($coveredPlans as $row)
                @php
                    /** @var \App\Models\MembershipPlan $plan */
                    $plan = $row['plan'];
                    $via = $row['membership'];
                @endphp
                <div class="ms-covered">
                    <p class="ms-covered__title">{{ $plan->name }}</p>
                    <p class="ms-covered__meta">
                        Covered by {{ $via?->plan?->name ?? 'your active membership' }}
                        @if($via?->ends_at) until {{ $via->ends_at->format('M j, Y') }} @endif
                    </p>
                </div>
            @endforeach
        </div>
    </section>
@endif

<section class="ms-section">
    <div class="ms-section__head">
        <h2 class="ms-section__title">Memberships available</h2>
        <p class="ms-section__desc">Featured journal memberships appear first. Pay the membership fee set by each journal, or subscribe for platform-wide access.</p>
    </div>

    @if($availablePlatformPlans->isNotEmpty())
        <div class="ms-section__head" style="margin-top:.5rem;margin-bottom:.65rem">
            <h3 class="ms-section__title" style="font-size:.92rem">Platform access</h3>
        </div>
        @foreach($availablePlatformPlans as $plan)
            @include('memberships.partials.plan-card', ['plan' => $plan])
        @endforeach
    @endif

    @if($availablePlatformPlans->isNotEmpty() && ($availableJournalPlans->isNotEmpty() || $journalPlans->total() > 0))
        <div class="ms-section__head" style="margin-top:1rem;margin-bottom:.65rem">
            <h3 class="ms-section__title" style="font-size:.92rem">Journal memberships</h3>
        </div>
    @endif

    <form method="GET" action="{{ route('memberships.index') }}" class="ms-toolbar">
        <label class="ms-search" for="membership-search">
            <svg class="ms-search__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M20 20l-3.5-3.5"/>
            </svg>
            <input
                id="membership-search"
                type="search"
                name="q"
                value="{{ $search }}"
                class="ms-search__input"
                placeholder="Search journal memberships…"
                autocomplete="off"
            >
        </label>
        @if($scope === 'all')
            <input type="hidden" name="scope" value="all">
        @endif
        @if($search !== '')
            <button type="submit" class="ms-browse__btn">Search</button>
            <a href="{{ route('memberships.index', $scope === 'all' ? ['scope' => 'all'] : []) }}" class="ms-browse__btn ms-browse__btn--ghost">Clear</a>
        @endif
    </form>

    @if($scope === 'featured' && $otherJournalPlansCount > 0 && $search === '')
        <div class="ms-browse">
            <a href="{{ route('memberships.index', ['scope' => 'all']) }}" class="ms-browse__btn">
                Browse all journal memberships
                <span>({{ number_format($otherJournalPlansCount) }} more)</span>
            </a>
        </div>
    @elseif($scope === 'all' && $search === '')
        <div class="ms-browse">
            <a href="{{ route('memberships.index') }}" class="ms-browse__btn ms-browse__btn--ghost">← Back to featured journals</a>
        </div>
    @endif

    @forelse($availableJournalPlans as $plan)
        @include('memberships.partials.plan-card', ['plan' => $plan])
    @empty
        @if($availablePlatformPlans->isEmpty())
            <div class="ms-empty">
                <p class="ms-empty__title">You're fully covered</p>
                <p class="ms-empty__text">Every available membership is already included in your active access. Check back later to renew or when journals add new options.</p>
            </div>
        @elseif($search !== '')
            <div class="ms-empty">
                <p class="ms-empty__title">No matching journal memberships</p>
                <p class="ms-empty__text">Try a different search term, or browse all journal memberships.</p>
            </div>
        @elseif($scope === 'featured')
            <div class="ms-empty">
                <p class="ms-empty__title">No featured journal memberships right now</p>
                <p class="ms-empty__text">Browse all journals to see every membership option.</p>
            </div>
        @else
            <div class="ms-empty">
                <p class="ms-empty__title">No journal memberships available</p>
                <p class="ms-empty__text">Check back later when journals publish membership plans.</p>
            </div>
        @endif
    @endforelse

    @if($journalPlans->hasPages())
        <div class="ms-pagination">
            @if($journalPlans->onFirstPage())
                <span class="ms-browse__btn ms-browse__btn--ghost" style="opacity:.45;pointer-events:none">Previous</span>
            @else
                <a href="{{ $journalPlans->previousPageUrl() }}" class="ms-browse__btn ms-browse__btn--ghost">Previous</a>
            @endif
            <span class="ms-pagination__meta">Page {{ $journalPlans->currentPage() }} of {{ $journalPlans->lastPage() }}</span>
            @if($journalPlans->hasMorePages())
                <a href="{{ $journalPlans->nextPageUrl() }}" class="ms-browse__btn ms-browse__btn--ghost">Next</a>
            @else
                <span class="ms-browse__btn ms-browse__btn--ghost" style="opacity:.45;pointer-events:none">Next</span>
            @endif
        </div>
    @endif
</section>

@if(($reviewerRequestJournals ?? collect())->isNotEmpty())
    <section class="ms-section">
        <div class="ms-section__head">
            <h2 class="ms-section__title">Volunteer as a reviewer</h2>
            <p class="ms-section__desc">Journal members can request to join the peer-review team.</p>
        </div>
        <div class="ms-grid ms-grid--2">
            @foreach($reviewerRequestJournals as $row)
                <x-reviewer-request-panel
                    :journal="$row['journal']"
                    :can-request="$row['canRequest']"
                    :reason="$row['reason']"
                    :pending="$row['pending']"
                    :latest="$row['latest']"
                    compact
                />
            @endforeach
        </div>
    </section>
@endif
@endsection
