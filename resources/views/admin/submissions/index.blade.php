@extends(isset($manageJournal) ? 'layouts.journal-manage' : 'layouts.admin')

@section('title', isset($manageJournal) ? 'Submissions | '.$manageJournal->title : 'Submissions | Admin')
@section('page_title', 'Submissions')
@section('page_subtitle', isset($manageJournal) ? $manageJournal->title : 'Editorial workflow inbox')

@section('content')
@php
    $submissionsIndexUrl = fn (array $query = []) => isset($manageJournal)
        ? route('journal.manage.submissions.index', array_merge([$manageJournal], array_filter($query, fn ($v) => $v !== null && $v !== '')))
        : route('admin.submissions.index', array_filter($query, fn ($v) => $v !== null && $v !== ''));
    $submissionShowUrl = fn ($submission) => isset($manageJournal)
        ? route('journal.manage.submissions.show', [$manageJournal, $submission])
        : $submissionShowUrl($submission);
    $statusLabels = [
        'submitted' => 'Submitted',
        'under_review' => 'Under review',
        'revision_requested' => 'Revision requested',
        'resubmitted' => 'Resubmitted',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    $statCards = [
        ['key' => null, 'label' => 'Total', 'value' => $stats['total']],
        ['key' => 'submitted', 'label' => 'Submitted', 'value' => $stats['submitted']],
        ['key' => 'under_review', 'label' => 'In review', 'value' => $stats['under_review']],
        ['key' => 'revision_requested', 'label' => 'Revision', 'value' => $stats['revision_requested']],
        ['key' => 'resubmitted', 'label' => 'Resubmitted', 'value' => $stats['resubmitted']],
        ['key' => 'approved', 'label' => 'Approved', 'value' => $stats['approved']],
        ['key' => 'rejected', 'label' => 'Rejected', 'value' => $stats['rejected']],
    ];

    $filterQs = array_filter([
        'q' => $q !== '' ? $q : null,
        'journal_id' => $journalId,
        'per_page' => $perPage !== 20 ? $perPage : null,
    ], fn ($v) => $v !== null && $v !== '');
@endphp

<style>
    .asub-stats {
        display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; margin-bottom: 1rem;
    }
    @media (min-width: 720px) {
        .asub-stats { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }
    @media (min-width: 1100px) {
        .asub-stats { grid-template-columns: repeat(7, minmax(0, 1fr)); }
    }
    .asub-stat {
        background: #fff; border: 1px solid var(--line); border-radius: .95rem;
        padding: .85rem .9rem; box-shadow: 0 8px 24px rgba(15,23,42,.035);
        text-decoration: none; color: inherit; transition: border-color .15s ease, box-shadow .15s ease;
    }
    .asub-stat:hover { border-color: #cbd5e1; }
    .asub-stat.is-active {
        border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(37,99,235,.12), 0 8px 24px rgba(15,23,42,.04);
    }
    .asub-stat__label {
        font-size: .68rem; font-weight: 700; letter-spacing: .08em;
        text-transform: uppercase; color: var(--muted);
    }
    .asub-stat__value {
        margin-top: .35rem; font-size: 1.55rem; font-weight: 800;
        letter-spacing: -.03em; color: var(--ink); line-height: 1;
    }

    .asub-toolbar {
        display: flex; flex-direction: column; gap: .7rem;
        background: #fff; border: 1px solid var(--line); border-radius: 1rem;
        padding: .85rem .95rem; margin-bottom: 1rem;
        box-shadow: 0 8px 24px rgba(15,23,42,.035);
    }
    @media (min-width: 900px) {
        .asub-toolbar { flex-direction: row; align-items: center; }
    }
    .asub-search {
        flex: 1; display: flex; align-items: center; gap: .55rem; min-width: 0;
        border: 1px solid var(--line); border-radius: .75rem; background: #f8fafc;
        padding: .58rem .85rem;
    }
    .asub-search:focus-within {
        border-color: #93c5fd; background: #fff;
        box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    }
    .asub-search > svg { width: 1rem; height: 1rem; color: var(--muted); flex-shrink: 0; }
    .asub-search input {
        width: 100%; border: 0; outline: 0; background: transparent;
        font: inherit; font-size: .9rem; color: var(--ink); min-width: 0;
    }
    .asub-search input::-webkit-search-cancel-button { display: none; }
    .asub-search__clear {
        flex-shrink: 0; width: 1.3rem; height: 1.3rem; border: 0; border-radius: 999px;
        display: inline-flex; align-items: center; justify-content: center;
        background: #e2e8f0; color: #64748b; cursor: pointer; padding: 0;
    }
    .asub-search__clear svg { width: .72rem; height: .72rem; }
    .asub-search__pulse {
        flex-shrink: 0; width: .5rem; height: .5rem; border-radius: 999px;
        background: #2563eb; opacity: 0;
    }
    .asub-search__pulse.is-on { opacity: 1; animation: asub-pulse 1s ease-in-out infinite; }
    @keyframes asub-pulse { 0%,100%{opacity:.35} 50%{opacity:1} }

    .asub-filters { display: flex; flex-wrap: wrap; gap: .4rem; align-items: center; }
    .asub-select {
        border: 1px solid var(--line); border-radius: 999px; background: #fff;
        padding: .45rem .75rem; font: inherit; font-size: .78rem; font-weight: 700;
        color: var(--muted); outline: none; cursor: pointer; max-width: 14rem;
    }
    .asub-select:focus { border-color: #93c5fd; color: var(--ink); }
    .asub-chip {
        display: inline-flex; align-items: center; padding: .45rem .75rem; border-radius: 999px;
        border: 1px solid var(--line); background: #fff; color: var(--muted);
        font-size: .78rem; font-weight: 700; cursor: pointer; font: inherit;
    }
    .asub-chip:hover { border-color: #cbd5e1; color: var(--ink); }

    .asub-results { transition: opacity .15s ease; }
    .asub-results.is-loading { opacity: .55; pointer-events: none; }

    .asub-panel {
        background: #fff; border: 1px solid var(--line); border-radius: 1.05rem;
        box-shadow: 0 8px 24px rgba(15,23,42,.035); overflow: hidden;
    }
    .asub-table { width: 100%; border-collapse: collapse; }
    .asub-table th {
        text-align: left; padding: .75rem 1rem; font-size: .7rem; font-weight: 700;
        letter-spacing: .06em; text-transform: uppercase; color: var(--muted);
        background: #f8fafc; border-bottom: 1px solid var(--line);
    }
    .asub-table td {
        padding: .9rem 1rem; border-bottom: 1px solid #f1f5f9;
        vertical-align: top; font-size: .86rem;
    }
    .asub-table tr:last-child td { border-bottom: 0; }
    .asub-table tr:hover td { background: #fafbfc; }
    .asub-title {
        margin: 0; font-size: .92rem; font-weight: 800; color: var(--ink); line-height: 1.35;
    }
    .asub-title a { color: inherit; text-decoration: none; }
    .asub-title a:hover { color: #1d4ed8; }
    .asub-meta { margin: .3rem 0 0; font-size: .74rem; color: var(--muted); line-height: 1.4; }
    .asub-sub { margin: .2rem 0 0; font-size: .76rem; color: #64748b; }
    .asub-badge {
        display: inline-flex; align-items: center;
        font-size: .66rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase;
        padding: .24rem .5rem; border-radius: 999px; white-space: nowrap;
    }
    .asub-badge--submitted { background: #e8f1fc; color: #1d4ed8; }
    .asub-badge--under_review { background: #fef3c7; color: #b45309; }
    .asub-badge--revision_requested { background: #ffedd5; color: #c2410c; }
    .asub-badge--resubmitted { background: #e0e7ff; color: #4338ca; }
    .asub-badge--approved { background: #dcfce7; color: #15803d; }
    .asub-badge--rejected { background: #fee2e2; color: #b91c1c; }
    .asub-row-actions { display: flex; flex-wrap: wrap; gap: .35rem; justify-content: flex-end; }

    .asub-cards { display: none; }
    @media (max-width: 900px) {
        .asub-table-wrap { display: none; }
        .asub-cards { display: grid; gap: .65rem; }
    }
    .asub-card {
        background: #fff; border: 1px solid var(--line); border-radius: .95rem;
        padding: .9rem 1rem; box-shadow: 0 8px 24px rgba(15,23,42,.035);
    }
    .asub-card__top {
        display: flex; align-items: flex-start; justify-content: space-between; gap: .65rem;
    }
    .asub-card__actions { display: flex; gap: .4rem; margin-top: .75rem; }

    .asub-empty {
        background: #fff; border: 1px solid var(--line); border-radius: 1.05rem;
        padding: 2.4rem 1.25rem; text-align: center;
        box-shadow: 0 8px 24px rgba(15,23,42,.035);
    }
    .asub-empty__icon {
        width: 3rem; height: 3rem; margin: 0 auto .85rem; border-radius: .9rem;
        display: grid; place-items: center; background: #eff6ff; color: #2563eb;
    }
    .asub-empty__icon svg { width: 1.35rem; height: 1.35rem; }
    .asub-empty__title { margin: 0; font-size: 1rem; font-weight: 800; color: var(--ink); }
    .asub-empty__text { margin: .4rem auto 0; max-width: 28rem; font-size: .86rem; color: var(--muted); line-height: 1.5; }

    .asub-footer {
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
        gap: .75rem; margin-top: 1rem;
    }
    .asub-perpage {
        display: inline-flex; align-items: center; gap: .4rem;
        font-size: .8rem; color: var(--muted); font-weight: 600;
    }
    .asub-perpage select {
        border: 1px solid var(--line); border-radius: .55rem; background: #fff;
        padding: .35rem .5rem; font: inherit; font-size: .8rem;
    }
    [x-cloak] { display: none !important; }
</style>

<script>
    window.asubSearch = function (cfg) {
        return {
            q: cfg.q || '',
            journalId: cfg.journalId || '',
            status: cfg.status || null,
            perPage: cfg.perPage || 20,
            baseUrl: cfg.baseUrl,
            pending: false,
            timer: null,
            init() {
                this.$nextTick(() => {
                    const input = this.$refs.q;
                    if (input && sessionStorage.getItem('asub-focus') === '1') {
                        input.focus();
                        const len = input.value.length;
                        input.setSelectionRange(len, len);
                    }
                    sessionStorage.removeItem('asub-focus');
                });
            },
            buildUrl(overrides = {}) {
                const params = new URLSearchParams();
                const q = String(overrides.q !== undefined ? overrides.q : this.q).trim();
                const journalId = overrides.journalId !== undefined ? overrides.journalId : this.journalId;
                const status = overrides.status !== undefined ? overrides.status : this.status;
                const perPage = overrides.perPage !== undefined ? overrides.perPage : this.perPage;

                if (q) params.set('q', q);
                if (journalId) params.set('journal_id', String(journalId));
                if (status) params.set('status', status);
                if (perPage && Number(perPage) !== 20) params.set('per_page', String(perPage));

                const qs = params.toString();
                return qs ? `${this.baseUrl}?${qs}` : this.baseUrl;
            },
            sameAsCurrent(url) {
                const next = new URL(url, window.location.origin);
                return next.pathname === window.location.pathname && next.search === window.location.search;
            },
            go(overrides = {}) {
                const url = this.buildUrl(overrides);
                if (this.sameAsCurrent(url)) {
                    this.pending = false;
                    return;
                }
                this.pending = true;
                sessionStorage.setItem('asub-focus', '1');
                window.location.assign(url);
            },
            onType() {
                this.pending = true;
                clearTimeout(this.timer);
                this.timer = setTimeout(() => this.go(), 280);
            },
            flush() {
                clearTimeout(this.timer);
                this.go();
            },
            setStatus(status) {
                this.status = status;
                clearTimeout(this.timer);
                this.go({ status });
            },
            onJournalChange() {
                clearTimeout(this.timer);
                this.go();
            },
            clearQuery() {
                this.q = '';
                clearTimeout(this.timer);
                this.go({ q: '' });
            },
            clearAll() {
                this.q = '';
                this.journalId = '';
                this.status = null;
                clearTimeout(this.timer);
                this.go({ q: '', journalId: '', status: null });
            },
        };
    };
</script>

<div
    class="asub-page"
    x-data="asubSearch({
        q: @js($q ?? ''),
        journalId: @js($journalId ? (string) $journalId : ''),
        status: @js($status),
        perPage: {{ (int) $perPage }},
        baseUrl: @js($submissionsIndexUrl()),
    })"
>
    <div class="asub-stats">
        @foreach($statCards as $card)
            <a
                href="{{ $submissionsIndexUrl($filterQs + ['status' => $card['key']]) }}"
                class="asub-stat {{ ($status === $card['key'] || ($card['key'] === null && $status === null)) ? 'is-active' : '' }}"
                @click.prevent="setStatus(@js($card['key']))"
            >
                <p class="asub-stat__label">{{ $card['label'] }}</p>
                <p class="asub-stat__value">{{ number_format($card['value']) }}</p>
            </a>
        @endforeach
    </div>

    <div class="asub-toolbar">
        <div class="asub-search">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-3.5-3.5"/></svg>
            <input
                x-ref="q"
                type="search"
                placeholder="Search title, author, keywords…"
                x-model="q"
                @input="onType()"
                @keydown.enter.prevent="flush()"
            >
            <button type="button" class="asub-search__clear" x-show="q" x-cloak @click="clearQuery()" title="Clear search">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
            <span class="asub-search__pulse" :class="pending && 'is-on'"></span>
        </div>

        <div class="asub-filters">
            @unless(isset($manageJournal))
            <select class="asub-select" x-model="journalId" @change="onJournalChange()">
                <option value="">All journals</option>
                @foreach($journals as $journal)
                    <option value="{{ $journal->id }}">{{ $journal->title }}</option>
                @endforeach
            </select>
            @endunless

            <select class="asub-select" x-model="status" @change="setStatus(status || null)">
                <option value="">Any status</option>
                @foreach($statusLabels as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>

            <button type="button" class="asub-chip" x-show="q || journalId || status" x-cloak @click="clearAll()">
                Clear filters
            </button>
        </div>
    </div>

    <div class="asub-results" :class="pending && 'is-loading'">
        @if($submissions->isEmpty())
            <div class="asub-empty">
                <div class="asub-empty__icon">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M7 4h10a2 2 0 0 1 2 2v14l-4-2-4 2-4-2-4 2V6a2 2 0 0 1 2-2Z"/></svg>
                </div>
                <h2 class="asub-empty__title">
                    @if($q !== '' || $status || $journalId)
                        No matching submissions
                    @else
                        No submissions yet
                    @endif
                </h2>
                <p class="asub-empty__text">
                    @if($q !== '' || $status || $journalId)
                        Try another search, journal, or status. Authors’ manuscripts will appear here once they submit.
                    @else
                        When authors submit manuscripts, they’ll land in this inbox for assignment, review, and publishing.
                    @endif
                </p>
                @if($q !== '' || $status || $journalId)
                    <div style="margin-top:1rem">
                        <button type="button" class="admin-btn admin-btn-secondary" @click="clearAll()">Clear filters</button>
                    </div>
                @endif
            </div>
        @else
            <div class="asub-panel asub-table-wrap">
                <table class="asub-table">
                    <thead>
                        <tr>
                            <th style="width:42%">Manuscript</th>
                            <th>Journal</th>
                            <th>Author</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th style="text-align:right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($submissions as $submission)
                            <tr>
                                <td>
                                    <h3 class="asub-title">
                                        <a href="{{ $submissionShowUrl($submission) }}">{{ $submission->title }}</a>
                                    </h3>
                                    <p class="asub-meta">
                                        @if($submission->category){{ $submission->category }} · @endif
                                        @if($submission->reviewer)
                                            Reviewer: {{ $submission->reviewer->name }}
                                        @else
                                            No reviewer assigned
                                        @endif
                                    </p>
                                </td>
                                <td>
                                    <div class="asub-sub">{{ $submission->journal?->title ?: '—' }}</div>
                                </td>
                                <td>
                                    <div class="asub-sub" style="font-weight:700;color:var(--ink)">{{ $submission->author?->name ?: '—' }}</div>
                                    @if($submission->author?->email)
                                        <p class="asub-meta">{{ $submission->author->email }}</p>
                                    @endif
                                </td>
                                <td>
                                    <span class="asub-badge asub-badge--{{ $submission->status }}">
                                        {{ $statusLabels[$submission->status] ?? str_replace('_', ' ', $submission->status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="asub-sub">{{ optional($submission->updated_at)->format('M j, Y') }}</div>
                                    <p class="asub-meta">{{ optional($submission->updated_at)->diffForHumans() }}</p>
                                </td>
                                <td>
                                    <div class="asub-row-actions">
                                        <a href="{{ $submissionShowUrl($submission) }}" class="admin-btn admin-btn-secondary" style="padding:.4rem .7rem;font-size:.75rem">Open</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="asub-cards">
                @foreach($submissions as $submission)
                    <div class="asub-card">
                        <div class="asub-card__top">
                            <div style="min-width:0">
                                <h3 class="asub-title">
                                    <a href="{{ $submissionShowUrl($submission) }}">{{ $submission->title }}</a>
                                </h3>
                                <p class="asub-meta">
                                    {{ $submission->journal?->title ?: 'No journal' }}
                                    · {{ $submission->author?->name ?: 'Unknown author' }}
                                </p>
                            </div>
                            <span class="asub-badge asub-badge--{{ $submission->status }}">
                                {{ $statusLabels[$submission->status] ?? str_replace('_', ' ', $submission->status) }}
                            </span>
                        </div>
                        <p class="asub-sub" style="margin-top:.55rem">
                            Updated {{ optional($submission->updated_at)->diffForHumans() }}
                            @if($submission->reviewer) · Reviewer: {{ $submission->reviewer->name }} @endif
                        </p>
                        <div class="asub-card__actions">
                            <a href="{{ $submissionShowUrl($submission) }}" class="admin-btn admin-btn-secondary" style="padding:.4rem .7rem;font-size:.75rem">Open</a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="asub-footer">
                <label class="asub-perpage">
                    Show
                    <select x-model="perPage" @change="go({ perPage: Number(perPage) })">
                        @foreach([10, 20, 50] as $size)
                            <option value="{{ $size }}">{{ $size }}</option>
                        @endforeach
                    </select>
                    per page
                </label>
                <div>{{ $submissions->links() }}</div>
            </div>
        @endif
    </div>
</div>
@endsection
