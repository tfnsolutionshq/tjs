@extends('layouts.journal-manage')

@section('title', 'Payments & Income | '.$journal->title)
@section('page_title', 'Payments & Income')
@section('page_subtitle', 'Activation fees you pay, and sales income for this journal')

@section('page_actions')
    <a href="{{ route('journal.manage.activation.show', $journal) }}" class="admin-btn admin-btn-ghost">Activation</a>
    <a
        href="{{ route('journal.manage.billing.export', array_merge(['journal' => $journal], request()->query())) }}"
        class="admin-btn admin-btn-secondary"
        data-billing-export
    >
        Export CSV
    </a>
@endsection

@section('content')
@php
    $currency = $summary['currency'];
    $hasFilters = collect($filters)->filter(fn ($v) => filled($v))->isNotEmpty();
@endphp

<style>
    .jb-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .75rem;
        margin-bottom: 1rem;
    }
    @media (max-width: 900px) {
        .jb-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 520px) {
        .jb-stats { grid-template-columns: 1fr; }
    }
    .jb-stat {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: .95rem;
        padding: .95rem 1.05rem;
        box-shadow: 0 8px 24px rgba(15,23,42,.035);
    }
    .jb-stat--income { border-color: #bbf7d0; background: linear-gradient(180deg, #f0fdf4 0%, #fff 70%); }
    .jb-stat--spent { border-color: #fecaca; background: linear-gradient(180deg, #fef2f2 0%, #fff 70%); }
    .jb-stat__label {
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--muted);
    }
    .jb-stat__value {
        margin-top: .4rem;
        font-size: 1.4rem;
        font-weight: 800;
        letter-spacing: -.03em;
        color: var(--ink);
        line-height: 1.1;
    }
    .jb-stat__hint {
        margin-top: .4rem;
        font-size: .72rem;
        color: var(--muted);
        line-height: 1.35;
    }
    .jb-activation {
        display: flex;
        flex-wrap: wrap;
        gap: .85rem;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
        padding: .95rem 1.1rem;
        border-radius: .95rem;
        border: 1px solid #dbeafe;
        background: linear-gradient(135deg, #f8fbff 0%, #eff6ff 100%);
    }
    .jb-activation__title { margin: 0; font-size: .88rem; font-weight: 800; color: var(--ink); }
    .jb-activation__meta { margin: .25rem 0 0; font-size: .78rem; color: #475569; line-height: 1.45; }
    .jb-filters {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 1rem;
        padding: 1rem 1.1rem;
        margin-bottom: 1rem;
        box-shadow: 0 8px 24px rgba(15,23,42,.035);
    }
    .jb-filters__grid {
        display: grid;
        grid-template-columns: 1.4fr repeat(3, minmax(0, 1fr)) repeat(2, minmax(0, .9fr)) auto;
        gap: .65rem;
        align-items: end;
    }
    @media (max-width: 1100px) {
        .jb-filters__grid { grid-template-columns: 1fr 1fr 1fr; }
    }
    @media (max-width: 640px) {
        .jb-filters__grid { grid-template-columns: 1fr; }
    }
    .jb-field label {
        display: block;
        margin-bottom: .35rem;
        font-size: .72rem;
        font-weight: 700;
        color: #334155;
    }
    .jb-input, .jb-select {
        width: 100%;
        border: 1px solid #e2e8f0;
        background: #fff;
        border-radius: .7rem;
        padding: .55rem .7rem;
        font: inherit;
        font-size: .84rem;
        color: var(--ink);
    }
    .jb-input:focus, .jb-select:focus {
        outline: none;
        border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(59,130,246,.15);
    }
    .jb-filters__actions {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
        align-items: center;
    }
    .jb-panel {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(15,23,42,.035);
        position: relative;
    }
    .jb-panel.is-loading::after {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(255,255,255,.55);
        pointer-events: none;
    }
    .jb-panel__head {
        display: flex;
        flex-wrap: wrap;
        gap: .65rem;
        align-items: center;
        justify-content: space-between;
        padding: .95rem 1.1rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .jb-panel__title { margin: 0; font-size: .92rem; font-weight: 800; }
    .jb-panel__meta { margin: .2rem 0 0; font-size: .75rem; color: var(--muted); }
    .jb-table-wrap { overflow-x: auto; }
    .jb-table { width: 100%; border-collapse: collapse; min-width: 780px; }
    .jb-table th {
        text-align: left;
        padding: .7rem 1rem;
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--muted);
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }
    .jb-table td {
        padding: .85rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: top;
        font-size: .84rem;
    }
    .jb-table tr:last-child td { border-bottom: 0; }
    .jb-table tr:hover td { background: #fafbfc; }
    .jb-ref {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: .78rem;
        color: #334155;
        word-break: break-all;
    }
    .jb-detail { font-weight: 650; color: var(--ink); }
    .jb-sub { margin-top: .2rem; font-size: .74rem; color: var(--muted); }
    .jb-pill {
        display: inline-flex;
        align-items: center;
        padding: .18rem .5rem;
        border-radius: 999px;
        font-size: .68rem;
        font-weight: 750;
        letter-spacing: .02em;
        white-space: nowrap;
    }
    .jb-pill--in { background: #dcfce7; color: #166534; }
    .jb-pill--out { background: #fee2e2; color: #991b1b; }
    .jb-pill--success { background: #dcfce7; color: #166534; }
    .jb-pill--pending { background: #fef3c7; color: #92400e; }
    .jb-pill--failed { background: #fee2e2; color: #991b1b; }
    .jb-pill--type { background: #eff6ff; color: #1d4ed8; }
    .jb-amount { font-weight: 800; white-space: nowrap; }
    .jb-amount--in { color: #15803d; }
    .jb-amount--out { color: #b91c1c; }
    .jb-empty {
        padding: 2.5rem 1.25rem;
        text-align: center;
        color: var(--muted);
    }
    .jb-empty__title { margin: 0; font-size: .95rem; font-weight: 800; color: var(--ink); }
    .jb-empty__text { margin: .4rem 0 0; font-size: .82rem; line-height: 1.5; }
    .jb-pager { padding: .85rem 1.1rem; border-top: 1px solid #f1f5f9; }
    .jb-status {
        min-height: 1.1rem;
        margin: 0 0 .65rem;
        font-size: .75rem;
        color: var(--muted);
    }
    .jb-status[data-busy="1"] { color: #1d4ed8; font-weight: 650; }
</style>

<div
    x-data="journalBilling(@js([
        'url' => route('journal.manage.billing.index', $journal),
        'exportBase' => route('journal.manage.billing.export', $journal),
        'filters' => $filters,
    ]))"
>

@if($activation['fee_enabled'] || ! $activation['unlocked'])
    <div class="jb-activation">
        <div>
            <p class="jb-activation__title">
                Activation:
                <span style="text-transform:capitalize">{{ $activation['status'] }}</span>
                @if($activation['unlocked'])
                    · current
                @else
                    · locked
                @endif
            </p>
            <p class="jb-activation__meta">
                Fee {{ number_format($activation['price']) }} {{ $activation['currency'] }}
                · {{ $activation['days'] }} days
                @if($activation['expires_at'])
                    · {{ $activation['unlocked'] ? 'Expires' : 'Expired' }}
                    {{ $activation['expires_at']->timezone(config('app.timezone'))->toDayDateTimeString() }}
                @elseif($activation['unlocked'])
                    · No expiry (platform-waived)
                @endif
            </p>
        </div>
        <a href="{{ route('journal.manage.activation.show', $journal) }}" class="admin-btn admin-btn-primary" style="white-space:nowrap">
            {{ $activation['unlocked'] ? 'View details' : 'Pay activation' }}
        </a>
    </div>
@endif

<div class="jb-stats">
    <div class="jb-stat jb-stat--income">
        <p class="jb-stat__label">Income (success)</p>
        <p class="jb-stat__value">{{ number_format($summary['income']) }} {{ $currency }}</p>
        <p class="jb-stat__hint">
            Articles {{ number_format($summary['income_articles']) }}
            · Memberships {{ number_format($summary['income_memberships']) }}
            @if(($summary['income_submissions'] ?? 0) > 0)
                · Submissions {{ number_format($summary['income_submissions']) }}
            @endif
        </p>
    </div>
    <div class="jb-stat jb-stat--spent">
        <p class="jb-stat__label">Activation spent</p>
        <p class="jb-stat__value">{{ number_format($summary['spent_activation']) }} {{ $currency }}</p>
        <p class="jb-stat__hint">Listing fees paid to the platform</p>
    </div>
    <div class="jb-stat jb-stat--spent">
        <p class="jb-stat__label">Platform charges</p>
        <p class="jb-stat__value">{{ number_format($summary['platform_charges']) }} {{ $currency }}</p>
        <p class="jb-stat__hint">
            Activation {{ number_format($summary['spent_activation']) }}
            · DOI {{ number_format($summary['spent_doi']) }}
        </p>
    </div>
    <div class="jb-stat">
        <p class="jb-stat__label">Pending</p>
        <p class="jb-stat__value">{{ number_format($summary['pending']) }} {{ $currency }}</p>
        <p class="jb-stat__hint">{{ number_format($summary['success_count']) }} successful payments on record</p>
    </div>
</div>

<form class="jb-filters" @submit.prevent="applyFilters()">
    <div class="jb-filters__grid">
        <div class="jb-field">
            <label for="jb-q">Search</label>
            <input id="jb-q" class="jb-input" type="search" x-model="filters.q" @input.debounce.350ms="applyFilters()" placeholder="Reference, payer name or email">
        </div>
        <div class="jb-field">
            <label for="jb-type_trigger">Type</label>
            <x-tjs-select
                input-id="jb-type"
                :value="request('type', '')"
                placeholder="All types"
                :options="[
                    ['value' => '', 'label' => 'All types'],
                    ['value' => 'activation', 'label' => 'Activation fee'],
                    ['value' => 'article_sale', 'label' => 'Article sale'],
                    ['value' => 'membership', 'label' => 'Membership'],
                    ['value' => 'submission_fee', 'label' => 'Submission fee'],
                ]"
                @picker-change="filters.type = $event.detail; applyFilters()"
            />
        </div>
        <div class="jb-field">
            <label for="jb-direction_trigger">Direction</label>
            <x-tjs-select
                input-id="jb-direction"
                :value="request('direction', '')"
                placeholder="In & out"
                :options="[
                    ['value' => '', 'label' => 'In & out'],
                    ['value' => 'in', 'label' => 'Income'],
                    ['value' => 'out', 'label' => 'Expense'],
                ]"
                @picker-change="filters.direction = $event.detail; applyFilters()"
            />
        </div>
        <div class="jb-field">
            <label for="jb-status_trigger">Status</label>
            <x-tjs-select
                input-id="jb-status"
                :value="request('status', '')"
                placeholder="All statuses"
                :options="[
                    ['value' => '', 'label' => 'All statuses'],
                    ['value' => 'success', 'label' => 'Success'],
                    ['value' => 'pending', 'label' => 'Pending'],
                    ['value' => 'failed', 'label' => 'Failed'],
                ]"
                @picker-change="filters.status = $event.detail; applyFilters()"
            />
        </div>
        <div class="jb-field">
            <label for="jb-from">From</label>
            <input id="jb-from" class="jb-input" type="date" x-model="filters.from" @change="applyFilters()">
        </div>
        <div class="jb-field">
            <label for="jb-to">To</label>
            <input id="jb-to" class="jb-input" type="date" x-model="filters.to" @change="applyFilters()">
        </div>
        <div class="jb-filters__actions">
            <button type="button" class="admin-btn admin-btn-ghost" x-show="hasFilters" x-cloak @click="clearFilters()">Clear</button>
        </div>
    </div>
</form>

<p class="jb-status" :data-busy="loading ? '1' : '0'" x-text="statusText"></p>

<section class="jb-panel" :class="{ 'is-loading': loading }">
    <div class="jb-panel__head">
        <div>
            <h2 class="jb-panel__title">Transaction audit</h2>
            <p class="jb-panel__meta">
                Every payment tied to this journal — activation fees you paid, plus article, membership, and submission income. {{ $transactions->perPage() }} per page.
            </p>
        </div>
        <a :href="exportUrl" class="admin-btn admin-btn-secondary" data-billing-export>Export filtered CSV</a>
    </div>

    <div x-ref="results" @click="onResultsClick($event)">
        @include('journal-manage.billing.partials.transactions', [
            'journal' => $journal,
            'transactions' => $transactions,
            'filters' => $filters,
        ])
    </div>
</section>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('journalBilling', (config) => ({
        url: config.url,
        exportBase: config.exportBase,
        filters: {
            q: config.filters.q || '',
            type: config.filters.type || '',
            direction: config.filters.direction || '',
            status: config.filters.status || '',
            from: config.filters.from || '',
            to: config.filters.to || '',
        },
        loading: false,
        statusText: '',
        exportUrl: '',
        requestId: 0,

        init() {
            this.exportUrl = this.buildExportUrl();
            this.syncExportLinks();
            this.bindPager();
        },

        get hasFilters() {
            return Object.values(this.filters).some((value) => String(value || '').trim() !== '');
        },

        queryParams(extra = {}) {
            const params = new URLSearchParams();
            Object.entries({ ...this.filters, ...extra }).forEach(([key, value]) => {
                const trimmed = String(value ?? '').trim();
                if (trimmed !== '') {
                    params.set(key, trimmed);
                }
            });
            return params;
        },

        buildExportUrl() {
            const params = this.queryParams();
            const query = params.toString();
            return query ? `${this.exportBase}?${query}` : this.exportBase;
        },

        syncExportLinks() {
            document.querySelectorAll('[data-billing-export]').forEach((el) => {
                el.setAttribute('href', this.exportUrl);
            });
        },

        clearFilters() {
            this.filters = { q: '', type: '', direction: '', status: '', from: '', to: '' };
            this.applyFilters();
        },

        async applyFilters(page = null) {
            this.loading = true;
            this.statusText = 'Updating transactions…';
            const id = ++this.requestId;
            const params = this.queryParams(page ? { page } : {});
            params.set('partial', '1');

            try {
                const response = await fetch(`${this.url}?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Filter request failed');
                }

                const data = await response.json();
                if (id !== this.requestId) {
                    return;
                }

                this.$refs.results.innerHTML = data.html;
                this.exportUrl = data.export_url || this.buildExportUrl();
                this.syncExportLinks();
                this.bindPager();

                const urlParams = this.queryParams(page ? { page } : {});
                const nextUrl = urlParams.toString() ? `${this.url}?${urlParams.toString()}` : this.url;
                window.history.replaceState({}, '', nextUrl);

                const count = Number(data.count || 0);
                this.statusText = this.hasFilters
                    ? `${count} matching transaction${count === 1 ? '' : 's'}`
                    : '';
            } catch (error) {
                if (id === this.requestId) {
                    this.statusText = 'Could not update filters. Try again.';
                }
            } finally {
                if (id === this.requestId) {
                    this.loading = false;
                }
            }
        },

        bindPager() {
            // Pagination links are replaced with each partial render.
        },

        onResultsClick(event) {
            const link = event.target.closest('a');
            if (!link || !this.$refs.results.contains(link)) {
                return;
            }

            if (!link.closest('[data-billing-pager]') && !link.closest('.jb-pager')) {
                return;
            }

            const href = link.getAttribute('href');
            if (!href || href === '#') {
                return;
            }

            event.preventDefault();
            try {
                const page = new URL(href, window.location.origin).searchParams.get('page');
                this.applyFilters(page);
            } catch (e) {
                // ignore malformed URLs
            }
        },
    }));
});
</script>
@endsection
