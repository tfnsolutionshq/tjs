@extends(isset($manageJournal) ? 'layouts.journal-manage' : 'layouts.admin')

@section('title', isset($manageJournal) ? 'Membership plans | '.$manageJournal->title : 'Membership plans | Admin')
@section('page_title', 'Membership plans')
@section('page_subtitle', isset($manageJournal) ? 'Plans for '.$manageJournal->title : 'Platform-wide and journal-scoped access plans')

@section('page_actions')
    <button
        type="button"
        class="admin-btn admin-btn-primary"
        onclick="window.dispatchEvent(new CustomEvent('amp-open-create'))"
    >
        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 8v8M8 12h8"/></svg>
        New plan
    </button>
@endsection

@section('content')
@php
    $currencySymbol = fn (?string $currency) => match (strtoupper((string) $currency)) {
        'NGN' => '₦',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        default => strtoupper((string) $currency).' ',
    };

    $durationLabel = function (int $days): string {
        return match (true) {
            $days === 30 => '1 month',
            $days === 90 => '3 months',
            $days === 180 => '6 months',
            $days === 365 => '1 year',
            $days % 365 === 0 && $days > 365 => ($days / 365).' years',
            $days % 30 === 0 && $days >= 60 => ($days / 30).' months',
            default => $days.' days',
        };
    };

    $plansPayload = $plans->map(fn ($p) => [
        'id' => $p->id,
        'name' => $p->name,
        'scope' => $p->scope,
        'journal_id' => $p->journal_id ? (string) $p->journal_id : '',
        'price_amount' => (int) $p->price_amount,
        'currency' => $p->currency ?: 'NGN',
        'duration_days' => (int) $p->duration_days,
        'is_active' => (bool) $p->is_active,
    ])->values();
@endphp

<style>
    .amp-stats {
        display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; margin-bottom: 1rem;
    }
    @media (min-width: 720px) {
        .amp-stats { grid-template-columns: repeat(5, minmax(0, 1fr)); }
    }
    .amp-stat {
        background: #fff; border: 1px solid var(--line); border-radius: .95rem;
        padding: .85rem .9rem; box-shadow: 0 8px 24px rgba(15,23,42,.035);
        text-decoration: none; color: inherit; transition: border-color .15s ease, box-shadow .15s ease;
    }
    .amp-stat:hover { border-color: #cbd5e1; }
    .amp-stat.is-active {
        border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(37,99,235,.12), 0 8px 24px rgba(15,23,42,.04);
    }
    .amp-stat__label {
        font-size: .68rem; font-weight: 700; letter-spacing: .08em;
        text-transform: uppercase; color: var(--muted);
    }
    .amp-stat__value {
        margin-top: .35rem; font-size: 1.55rem; font-weight: 800;
        letter-spacing: -.03em; color: var(--ink); line-height: 1;
    }

    .amp-toolbar {
        display: flex; flex-direction: column; gap: .7rem;
        background: #fff; border: 1px solid var(--line); border-radius: 1rem;
        padding: .85rem .95rem; margin-bottom: 1rem;
        box-shadow: 0 8px 24px rgba(15,23,42,.035);
    }
    @media (min-width: 860px) {
        .amp-toolbar { flex-direction: row; align-items: center; }
    }
    .amp-search {
        flex: 1; display: flex; align-items: center; gap: .55rem; min-width: 0;
        border: 1px solid var(--line); border-radius: .75rem; background: #f8fafc;
        padding: .58rem .85rem;
    }
    .amp-search:focus-within {
        border-color: #93c5fd; background: #fff;
        box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    }
    .amp-search > svg { width: 1rem; height: 1rem; color: var(--muted); flex-shrink: 0; }
    .amp-search input {
        width: 100%; border: 0; outline: 0; background: transparent;
        font: inherit; font-size: .9rem; color: var(--ink); min-width: 0;
    }
    .amp-filters { display: flex; flex-wrap: wrap; gap: .4rem; align-items: center; }
    .amp-select, .amp-chip {
        border: 1px solid var(--line); border-radius: 999px; background: #fff;
        padding: .45rem .75rem; font: inherit; font-size: .78rem; font-weight: 700;
        color: var(--muted); outline: none; cursor: pointer;
    }
    .amp-select:focus { border-color: #93c5fd; color: var(--ink); }
    .amp-chip:hover { border-color: #cbd5e1; color: var(--ink); }

    .amp-grid {
        display: grid; gap: .85rem;
        grid-template-columns: 1fr;
    }
    @media (min-width: 780px) {
        .amp-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (min-width: 1180px) {
        .amp-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }

    .amp-card {
        background: #fff; border: 1px solid var(--line); border-radius: 1.05rem;
        padding: 1.05rem 1.1rem 1rem; box-shadow: 0 8px 24px rgba(15,23,42,.035);
        display: flex; flex-direction: column; gap: .85rem; min-height: 100%;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .amp-card:hover { border-color: #cbd5e1; box-shadow: 0 12px 28px rgba(15,23,42,.05); }
    .amp-card.is-inactive { opacity: .78; background: #f8fafc; }
    .amp-card__top { display: flex; align-items: flex-start; justify-content: space-between; gap: .65rem; }
    .amp-card__name { margin: 0; font-size: 1rem; font-weight: 800; color: var(--ink); letter-spacing: -.015em; line-height: 1.3; }
    .amp-card__meta { margin: .35rem 0 0; font-size: .78rem; color: var(--muted); line-height: 1.4; }
    .amp-badges { display: flex; flex-wrap: wrap; gap: .35rem; justify-content: flex-end; }
    .amp-badge {
        display: inline-flex; align-items: center;
        font-size: .66rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase;
        padding: .24rem .5rem; border-radius: 999px; white-space: nowrap;
    }
    .amp-badge--platform { background: #e8f1fc; color: #1d4ed8; }
    .amp-badge--journal { background: #f3e8ff; color: #7e22ce; }
    .amp-badge--active { background: #dcfce7; color: #15803d; }
    .amp-badge--inactive { background: #f1f5f9; color: #64748b; }
    .amp-price {
        font-size: 1.55rem; font-weight: 800; letter-spacing: -.03em; color: var(--ink); line-height: 1;
    }
    .amp-price span { font-size: .78rem; font-weight: 700; color: var(--muted); margin-left: .25rem; }
    .amp-facts {
        display: grid; grid-template-columns: 1fr 1fr; gap: .55rem;
        padding: .7rem .75rem; border-radius: .8rem; background: #f8fafc; border: 1px solid #eef2f7;
    }
    .amp-fact__label { font-size: .66rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--muted); }
    .amp-fact__value { margin-top: .15rem; font-size: .84rem; font-weight: 700; color: var(--ink); }
    .amp-card__actions { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: auto; padding-top: .15rem; }

    .amp-empty {
        background: #fff; border: 1px solid var(--line); border-radius: 1.05rem;
        padding: 2.4rem 1.25rem; text-align: center;
        box-shadow: 0 8px 24px rgba(15,23,42,.035);
    }
    .amp-empty__icon {
        width: 3rem; height: 3rem; margin: 0 auto .85rem; border-radius: .9rem;
        display: grid; place-items: center; background: #eff6ff; color: #2563eb;
    }
    .amp-empty__icon svg { width: 1.35rem; height: 1.35rem; }
    .amp-empty__title { margin: 0; font-size: 1rem; font-weight: 800; color: var(--ink); }
    .amp-empty__text { margin: .4rem auto 0; max-width: 28rem; font-size: .86rem; color: var(--muted); line-height: 1.5; }

    .amp-overlay {
        position: fixed; inset: 0; z-index: 60; background: rgba(15,23,42,.42);
        display: flex; align-items: flex-end; justify-content: center;
        padding: 1rem;
    }
    @media (min-width: 720px) {
        .amp-overlay { align-items: center; }
    }
    .amp-drawer {
        width: min(34rem, 100%); max-height: min(92vh, 46rem); overflow: auto;
        background: #fff; border-radius: 1.15rem; border: 1px solid var(--line);
        box-shadow: 0 24px 60px rgba(15,23,42,.22);
    }
    .amp-drawer__head {
        display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem;
        padding: 1.1rem 1.2rem .35rem; position: sticky; top: 0; background: #fff; z-index: 1;
    }
    .amp-drawer__title { margin: 0; font-size: 1.05rem; font-weight: 800; color: var(--ink); letter-spacing: -.015em; }
    .amp-drawer__desc { margin: .25rem 0 0; font-size: .8rem; color: var(--muted); line-height: 1.4; }
    .amp-drawer__close {
        appearance: none; border: 0; background: #f1f5f9; color: #64748b; cursor: pointer;
        width: 2rem; height: 2rem; border-radius: .6rem; display: grid; place-items: center; flex-shrink: 0;
    }
    .amp-drawer__close:hover { color: #0f172a; background: #e2e8f0; }
    .amp-drawer__body { padding: .75rem 1.2rem 1.25rem; display: grid; gap: .85rem; }
    .amp-field label {
        display: block; margin-bottom: .4rem;
        font-size: .78rem; font-weight: 700; color: #334155;
    }
    .amp-req { color: #dc2626; font-weight: 800; margin-left: .12rem; }
    .amp-input, .amp-select-field {
        width: 100%; border: 1px solid var(--line); border-radius: .7rem; background: #fff;
        padding: .65rem .8rem; font: inherit; font-size: .9rem; color: var(--ink); outline: none;
    }
    .amp-input:focus, .amp-select-field:focus {
        border-color: #93c5fd; box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    }
    .amp-hint { margin: .35rem 0 0; font-size: .72rem; color: var(--muted); line-height: 1.4; }
    .amp-error { margin: .35rem 0 0; font-size: .75rem; font-weight: 600; color: #b91c1c; }
    .amp-grid-2 { display: grid; gap: .85rem; }
    @media (min-width: 560px) {
        .amp-grid-2 { grid-template-columns: 1fr 1fr; }
    }
    .amp-price-wrap {
        display: grid; grid-template-columns: 5.5rem minmax(0, 1fr); gap: .45rem;
    }
    .amp-duration-chips { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .45rem; }
    .amp-duration-chip {
        appearance: none; border: 1px solid #e2e8f0; background: #fff; color: #64748b;
        border-radius: 999px; padding: .35rem .65rem; font: inherit; font-size: .72rem;
        font-weight: 700; cursor: pointer;
    }
    .amp-duration-chip.is-on {
        border-color: #2563eb; color: #1d4ed8; background: #eff6ff;
        box-shadow: 0 0 0 2px rgba(37,99,235,.12);
    }
    .amp-toggle {
        display: flex; align-items: center; justify-content: space-between; gap: .75rem;
        padding: .75rem .85rem; border: 1px solid var(--line); border-radius: .8rem; background: #f8fafc;
    }
    .amp-toggle__label { font-size: .84rem; font-weight: 700; color: var(--ink); }
    .amp-toggle__hint { margin: .15rem 0 0; font-size: .72rem; color: var(--muted); }
    .amp-switch {
        position: relative; width: 2.55rem; height: 1.45rem; border-radius: 999px;
        background: #cbd5e1; border: 0; cursor: pointer; flex-shrink: 0; padding: 0;
        transition: background .15s ease;
    }
    .amp-switch.is-on { background: #2563eb; }
    .amp-switch::after {
        content: ''; position: absolute; top: .15rem; left: .15rem;
        width: 1.15rem; height: 1.15rem; border-radius: 999px; background: #fff;
        box-shadow: 0 1px 2px rgba(15,23,42,.2); transition: transform .15s ease;
    }
    .amp-switch.is-on::after { transform: translateX(1.1rem); }
    .amp-drawer__foot {
        display: flex; flex-wrap: wrap; gap: .5rem; justify-content: flex-end;
        padding-top: .35rem; border-top: 1px dashed #e2e8f0; margin-top: .15rem;
    }
    [x-cloak] { display: none !important; }
</style>

<div
    x-data="ampPlans({
        plans: @js($plansPayload),
        q: @js($q),
        scope: @js($scope),
        active: @js($active),
        baseUrl: @js(isset($manageJournal) ? route('journal.manage.membership-plans.index', $manageJournal) : route('admin.membership-plans.index')),
        storeUrl: @js(isset($manageJournal) ? route('journal.manage.membership-plans.store', $manageJournal) : route('admin.membership-plans.store')),
        updateUrlTemplate: @js(isset($manageJournal) ? url('/j/'.$manageJournal->slug.'/manage/membership-plans/__ID__') : url('/admin/membership-plans/__ID__')),
        csrf: @js(csrf_token()),
        old: @js([
            'name' => old('name'),
            'scope' => old('scope', isset($manageJournal) ? 'journal' : 'platform'),
            'journal_id' => old('journal_id', isset($manageJournal) ? (string) $manageJournal->id : ''),
            'price_amount' => old('price_amount'),
            'currency' => old('currency', 'NGN'),
            'duration_days' => old('duration_days', 365),
            'is_active' => old('is_active', '1') !== null && old('is_active', '1') !== false && old('is_active', '1') !== '0',
        ]),
        lockedJournal: @js(isset($manageJournal)),
        manageJournalId: @js(isset($manageJournal) ? (string) $manageJournal->id : ''),
        openOnLoad: @js($errors->any()),
        editIdOnLoad: @js(old('_plan_id')),
    })"
    @amp-open-create.window="openCreate()"
>
    @php
        $plansIndexUrl = fn (array $query = []) => isset($manageJournal)
            ? route('journal.manage.membership-plans.index', array_filter(array_merge(['journal' => $manageJournal], $query)))
            : route('admin.membership-plans.index', array_filter($query));
    @endphp
    <div class="amp-stats">
        <a href="{{ $plansIndexUrl(['q' => $q ?: null]) }}" class="amp-stat {{ ($scope === null || (isset($manageJournal) && $scope === 'journal')) && $active === null ? 'is-active' : '' }}" @click.prevent="go({ scope: lockedJournal ? 'journal' : null, active: null })">
            <p class="amp-stat__label">Total</p>
            <p class="amp-stat__value">{{ number_format($stats['total']) }}</p>
        </a>
        @unless(isset($manageJournal))
        <a href="{{ $plansIndexUrl(['q' => $q ?: null, 'scope' => 'platform']) }}" class="amp-stat {{ $scope === 'platform' ? 'is-active' : '' }}" @click.prevent="go({ scope: 'platform' })">
            <p class="amp-stat__label">Platform</p>
            <p class="amp-stat__value">{{ number_format($stats['platform']) }}</p>
        </a>
        <a href="{{ $plansIndexUrl(['q' => $q ?: null, 'scope' => 'journal']) }}" class="amp-stat {{ $scope === 'journal' ? 'is-active' : '' }}" @click.prevent="go({ scope: 'journal' })">
            <p class="amp-stat__label">Journal</p>
            <p class="amp-stat__value">{{ number_format($stats['journal']) }}</p>
        </a>
        @endunless
        <a href="{{ $plansIndexUrl(['q' => $q ?: null, 'scope' => $scope, 'active' => '1']) }}" class="amp-stat {{ $active === '1' ? 'is-active' : '' }}" @click.prevent="go({ active: '1' })">
            <p class="amp-stat__label">Active</p>
            <p class="amp-stat__value">{{ number_format($stats['active']) }}</p>
        </a>
        <a href="{{ $plansIndexUrl(['q' => $q ?: null, 'scope' => $scope, 'active' => '0']) }}" class="amp-stat {{ $active === '0' ? 'is-active' : '' }}" @click.prevent="go({ active: '0' })">
            <p class="amp-stat__label">Inactive</p>
            <p class="amp-stat__value">{{ number_format($stats['inactive']) }}</p>
        </a>
    </div>

    <div class="amp-toolbar">
        <div class="amp-search">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-3.5-3.5"/></svg>
            <input type="search" placeholder="Search plans or journals…" x-model="q" @input="onType()" @keydown.enter.prevent="flush()">
                </div>
        <div class="amp-filters">
            <select class="amp-select" x-model="scope" @change="go({ scope: scope || null })">
                <option value="">All scopes</option>
                <option value="platform">Platform</option>
                <option value="journal">Journal</option>
            </select>
            <select class="amp-select" x-model="active" @change="go({ active: active || null })">
                <option value="">Any status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
            <button type="button" class="amp-chip" x-show="q || scope || active" x-cloak @click="clearAll()">Clear</button>
            </div>
    </div>

    @if($plans->isEmpty())
        <div class="amp-empty">
            <div class="amp-empty__icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 0 0 3-3V8a3 3 0 0 0-3-3H6a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3Z"/></svg>
            </div>
            <h2 class="amp-empty__title">
                @if($q !== '' || $scope || $active !== null) No matching plans @else No membership plans yet @endif
            </h2>
            <p class="amp-empty__text">
                @if($q !== '' || $scope || $active !== null)
                    Try another search or clear filters.
                @else
                    Create a platform or journal plan so readers can buy access.
                @endif
            </p>
            <div style="margin-top:1rem">
                @if($q !== '' || $scope || $active !== null)
                    <button type="button" class="admin-btn admin-btn-secondary" @click="clearAll()">Clear filters</button>
                @else
                    <button type="button" class="admin-btn admin-btn-primary" @click="openCreate()">New plan</button>
                @endif
            </div>
        </div>
    @else
        <div class="amp-grid">
            @foreach($plans as $plan)
                <article class="amp-card {{ $plan->is_active ? '' : 'is-inactive' }}">
                    <div class="amp-card__top">
                        <div style="min-width:0">
                            <h2 class="amp-card__name">{{ $plan->name }}</h2>
                            <p class="amp-card__meta">
                                @if($plan->scope === 'journal')
                                    {{ $plan->journal?->title ?: 'Journal plan' }}
                                @else
                                    Platform-wide access
                                @endif
                            </p>
                        </div>
                        <div class="amp-badges">
                            <span class="amp-badge amp-badge--{{ $plan->scope }}">{{ $plan->scope }}</span>
                            <span class="amp-badge {{ $plan->is_active ? 'amp-badge--active' : 'amp-badge--inactive' }}">
                                {{ $plan->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <div class="amp-price">
                            {{ $currencySymbol($plan->currency) }}{{ number_format($plan->price_amount) }}
                            <span>{{ strtoupper($plan->currency) }}</span>
                        </div>
                    </div>

                    <div class="amp-facts">
            <div>
                            <div class="amp-fact__label">Duration</div>
                            <div class="amp-fact__value">{{ $durationLabel((int) $plan->duration_days) }}</div>
            </div>
            <div>
                            <div class="amp-fact__label">Members</div>
                            <div class="amp-fact__value">{{ number_format($plan->memberships_count) }}</div>
                        </div>
                    </div>

                    <div class="amp-card__actions">
                        <button type="button" class="admin-btn admin-btn-secondary" style="padding:.42rem .75rem;font-size:.75rem"
                            @click="openEdit({{ $plan->id }})">Edit</button>
                        <form method="POST" action="{{ isset($manageJournal) ? route('journal.manage.membership-plans.toggle', [$manageJournal, $plan]) : route('admin.membership-plans.toggle', $plan) }}">
                            @csrf
                            <button type="submit" class="admin-btn admin-btn-secondary" style="padding:.42rem .75rem;font-size:.75rem">
                                {{ $plan->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                        @if($plan->memberships_count === 0)
                            <form method="POST" action="{{ isset($manageJournal) ? route('journal.manage.membership-plans.destroy', [$manageJournal, $plan]) : route('admin.membership-plans.destroy', $plan) }}"
                                onsubmit="return confirm('Delete this membership plan?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="admin-btn admin-btn-secondary" style="padding:.42rem .75rem;font-size:.75rem;color:#b91c1c">Delete</button>
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
            </div>
    @endif

    <div class="amp-overlay" x-show="drawerOpen" x-cloak @keydown.escape.window="closeDrawer()" @click.self="closeDrawer()">
        <div class="amp-drawer" role="dialog" aria-modal="true" :aria-label="editingId ? 'Edit membership plan' : 'Create membership plan'">
            <div class="amp-drawer__head">
            <div>
                    <h2 class="amp-drawer__title" x-text="editingId ? 'Edit plan' : 'Create plan'"></h2>
                    <p class="amp-drawer__desc">Set pricing, duration, and whether the plan is platform-wide or journal-specific.</p>
                </div>
                <button type="button" class="amp-drawer__close" @click="closeDrawer()" aria-label="Close">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>

            <form method="POST" class="amp-drawer__body" :action="formAction">
                @csrf
                <template x-if="editingId">
                    <input type="hidden" name="_method" value="PUT">
                </template>
                <input type="hidden" name="_plan_id" :value="editingId || ''">

                <div class="amp-field">
                    <label for="amp_name">Name <span class="amp-req">*</span></label>
                    <input id="amp_name" name="name" type="text" required class="amp-input" x-model="form.name" placeholder="e.g. Platform Annual Membership">
                    @error('name')<p class="amp-error">{{ $message }}</p>@enderror
                </div>

                <div class="amp-grid-2">
                    @if(isset($manageJournal))
                        <input type="hidden" name="scope" value="journal">
                        <input type="hidden" name="journal_id" value="{{ $manageJournal->id }}">
                        <div class="amp-field" style="grid-column:1/-1">
                            <label>Journal</label>
                            <div class="amp-input" style="background:#f8fafc;font-weight:700">{{ $manageJournal->title }}</div>
                            <p class="amp-hint">Plans created here are scoped to this journal only.</p>
                        </div>
                    @else
                    <div class="amp-field">
                        <label for="amp_scope">Scope <span class="amp-req">*</span></label>
                        <select id="amp_scope" name="scope" required class="amp-select-field" x-model="form.scope">
                            <option value="platform">Platform</option>
                            <option value="journal">Journal</option>
                        </select>
                    </div>
                    <div class="amp-field" x-show="form.scope === 'journal'" x-cloak>
                        <label for="amp_journal_id">Journal <span class="amp-req">*</span></label>
                        <select id="amp_journal_id" name="journal_id" class="amp-select-field" x-model="form.journal_id" :required="form.scope === 'journal'">
                            <option value="">Select journal…</option>
                            @foreach($journals as $journalOption)
                                <option value="{{ $journalOption->id }}">{{ $journalOption->title }}</option>
                            @endforeach
                        </select>
                        @error('journal_id')<p class="amp-error">{{ $message }}</p>@enderror
                    </div>
                    @endif
                </div>

                <div class="amp-field">
                    <label for="amp_price_amount">Price <span class="amp-req">*</span></label>
                    <div class="amp-price-wrap">
                        <select name="currency" class="amp-select-field" x-model="form.currency">
                            @foreach(['NGN', 'USD', 'EUR', 'GBP'] as $code)
                                <option value="{{ $code }}">{{ $code }}</option>
                            @endforeach
                        </select>
                        <input id="amp_price_amount" name="price_amount" type="number" min="0" required class="amp-input" x-model="form.price_amount" placeholder="15000">
                    </div>
                    <p class="amp-hint">Whole currency units (e.g. 15000 for ₦15,000).</p>
                    @error('price_amount')<p class="amp-error">{{ $message }}</p>@enderror
                </div>

                <div class="amp-field">
                    <label for="amp_duration_days">Duration (days) <span class="amp-req">*</span></label>
                    <input id="amp_duration_days" name="duration_days" type="number" min="1" max="3650" required class="amp-input" x-model="form.duration_days">
                    <div class="amp-duration-chips">
                        <template x-for="preset in durationPresets" :key="preset.days">
                            <button
                                type="button"
                                class="amp-duration-chip"
                                :class="Number(form.duration_days) === preset.days && 'is-on'"
                                @click="form.duration_days = preset.days"
                                x-text="preset.label"
                            ></button>
                        </template>
                    </div>
                    @error('duration_days')<p class="amp-error">{{ $message }}</p>@enderror
                </div>

                <div class="amp-toggle">
            <div>
                        <div class="amp-toggle__label">Active</div>
                        <p class="amp-toggle__hint">Inactive plans stay hidden from purchase options.</p>
            </div>
                    <button type="button" class="amp-switch" :class="form.is_active && 'is-on'" @click="form.is_active = !form.is_active" :aria-pressed="form.is_active"></button>
                    <input type="hidden" name="is_active" :value="form.is_active ? '1' : '0'">
            </div>

                <div class="amp-drawer__foot">
                    <button type="button" class="admin-btn admin-btn-secondary" @click="closeDrawer()">Cancel</button>
                    <button type="submit" class="admin-btn admin-btn-primary" x-text="editingId ? 'Save changes' : 'Create plan'"></button>
            </div>
        </form>
        </div>
    </div>
</div>

<script>
function ampPlans(cfg) {
    const blank = () => ({
        name: '',
        scope: cfg.lockedJournal ? 'journal' : 'platform',
        journal_id: cfg.old?.journal_id && cfg.lockedJournal ? String(cfg.old.journal_id) : (cfg.lockedJournal ? String(cfg.old?.journal_id || '') : ''),
        price_amount: '',
        currency: 'NGN',
        duration_days: 365,
        is_active: true,
    });

    return {
        plans: cfg.plans || [],
        q: cfg.q || '',
        scope: cfg.scope || '',
        active: cfg.active || '',
        lockedJournal: !!cfg.lockedJournal,
        drawerOpen: false,
        editingId: null,
        form: blank(),
        timer: null,
        durationPresets: [
            { days: 30, label: '30 days' },
            { days: 90, label: '90 days' },
            { days: 180, label: '6 months' },
            { days: 365, label: '1 year' },
        ],
        init() {
            if (cfg.lockedJournal && cfg.old?.journal_id) {
                this.form.journal_id = String(cfg.old.journal_id);
            }
            if (cfg.openOnLoad) {
                if (cfg.editIdOnLoad) this.openEdit(Number(cfg.editIdOnLoad));
                else this.openCreate();
                if (cfg.old) {
                    this.form = {
                        name: cfg.old.name || '',
                        scope: cfg.lockedJournal ? 'journal' : (cfg.old.scope || 'platform'),
                        journal_id: cfg.old.journal_id ? String(cfg.old.journal_id) : '',
                        price_amount: cfg.old.price_amount ?? '',
                        currency: cfg.old.currency || 'NGN',
                        duration_days: cfg.old.duration_days || 365,
                        is_active: !!cfg.old.is_active,
                    };
                }
            }
        },
        get formAction() {
            if (this.editingId) {
                return String(cfg.updateUrlTemplate).replace('__ID__', String(this.editingId));
            }
            return cfg.storeUrl;
        },
        openCreate() {
            this.editingId = null;
            this.form = blank();
            if (cfg.lockedJournal) {
                this.form.scope = 'journal';
                this.form.journal_id = String(cfg.manageJournalId || '');
            }
            this.drawerOpen = true;
        },
        openEdit(id) {
            const plan = this.plans.find((p) => Number(p.id) === Number(id));
            if (!plan) return;
            this.editingId = Number(plan.id);
            this.form = {
                name: plan.name,
                scope: plan.scope,
                journal_id: plan.journal_id || '',
                price_amount: plan.price_amount,
                currency: plan.currency || 'NGN',
                duration_days: plan.duration_days || 365,
                is_active: !!plan.is_active,
            };
            this.drawerOpen = true;
        },
        closeDrawer() {
            this.drawerOpen = false;
        },
        buildUrl(overrides = {}) {
            const params = new URLSearchParams();
            const q = String(overrides.q !== undefined ? overrides.q : this.q).trim();
            const scope = overrides.scope !== undefined ? overrides.scope : this.scope;
            const active = overrides.active !== undefined ? overrides.active : this.active;
            if (q) params.set('q', q);
            if (scope) params.set('scope', scope);
            if (active !== null && active !== undefined && active !== '') params.set('active', String(active));
            const qs = params.toString();
            return qs ? `${cfg.baseUrl}?${qs}` : cfg.baseUrl;
        },
        go(overrides = {}) {
            if (overrides.scope !== undefined) this.scope = overrides.scope || '';
            if (overrides.active !== undefined) this.active = overrides.active || '';
            if (overrides.q !== undefined) this.q = overrides.q;
            window.location.assign(this.buildUrl(overrides));
        },
        onType() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.flush(), 280);
        },
        flush() {
            clearTimeout(this.timer);
            this.go();
        },
        clearAll() {
            this.q = '';
            this.scope = '';
            this.active = '';
            this.go({ q: '', scope: null, active: null });
        },
    };
}
</script>
@endsection
