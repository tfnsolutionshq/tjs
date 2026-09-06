@extends(isset($manageJournal) ? 'layouts.journal-manage' : 'layouts.admin')

@php
    $q = request('q');
    $journalId = isset($manageJournal) ? $manageJournal->id : request('journal_id');
    $status = request('status');
    $visibility = request('visibility');
    $articlesIndexRoute = isset($manageJournal)
        ? 'journal.manage.articles.index'
        : 'admin.articles.index';
    $articlesIndexParams = isset($manageJournal) ? [$manageJournal] : [];
    $articlesCreateUrl = isset($manageJournal)
        ? route('journal.manage.articles.create', $manageJournal)
        : route('admin.articles.create', array_filter(['journal_id' => request('journal_id')]));
    $articlesEdit = fn ($article) => isset($manageJournal)
        ? route('journal.manage.articles.edit', [$manageJournal, $article])
        : route('admin.articles.edit', $article);
    $articlesIndexUrl = fn (array $query = []) => route(
        $articlesIndexRoute,
        array_merge($articlesIndexParams, array_filter($query, fn ($v) => $v !== null && $v !== ''))
    );
@endphp

@section('title', isset($manageJournal) ? 'Articles | '.$manageJournal->title : 'Articles | Admin')
@section('page_title', 'Articles')
@section('page_subtitle', isset($manageJournal) ? $manageJournal->title : 'Catalog articles across journals')

@section('page_actions')
    <a href="{{ $articlesCreateUrl }}" class="admin-btn admin-btn-primary">
        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 8v8M8 12h8"/></svg>
        New article
    </a>
@endsection

@section('content')

<style>
    .aa-stats {
        display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; margin-bottom: 1rem;
    }
    @media (min-width: 720px) {
        .aa-stats { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }
    @media (min-width: 1100px) {
        .aa-stats { grid-template-columns: repeat(7, minmax(0, 1fr)); }
    }
    .aa-stat {
        background: #fff; border: 1px solid var(--line); border-radius: .95rem;
        padding: .85rem .9rem; box-shadow: 0 8px 24px rgba(15,23,42,.035);
        text-decoration: none; color: inherit; transition: border-color .15s ease, box-shadow .15s ease;
    }
    .aa-stat:hover { border-color: #cbd5e1; }
    .aa-stat.is-active {
        border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(37,99,235,.12), 0 8px 24px rgba(15,23,42,.04);
    }
    .aa-stat__label {
        font-size: .68rem; font-weight: 700; letter-spacing: .08em;
        text-transform: uppercase; color: var(--muted);
    }
    .aa-stat__value {
        margin-top: .35rem; font-size: 1.55rem; font-weight: 800;
        letter-spacing: -.03em; color: var(--ink); line-height: 1;
    }

    .aa-toolbar {
        display: flex; flex-direction: column; gap: .7rem;
        background: #fff; border: 1px solid var(--line); border-radius: 1rem;
        padding: .85rem .95rem; margin-bottom: 1rem;
        box-shadow: 0 8px 24px rgba(15,23,42,.035);
    }
    @media (min-width: 900px) {
        .aa-toolbar { flex-direction: row; align-items: center; }
    }
    .aa-search {
        flex: 1; display: flex; align-items: center; gap: .55rem; min-width: 0;
        border: 1px solid var(--line); border-radius: .75rem; background: #f8fafc;
        padding: .58rem .85rem;
    }
    .aa-search:focus-within {
        border-color: #93c5fd; background: #fff;
        box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    }
    .aa-search > svg { width: 1rem; height: 1rem; color: var(--muted); flex-shrink: 0; }
    .aa-search input {
        width: 100%; border: 0; outline: 0; background: transparent;
        font: inherit; font-size: .9rem; color: var(--ink); min-width: 0;
    }
    .aa-search input::-webkit-search-cancel-button { display: none; }
    .aa-search__clear {
        flex-shrink: 0; width: 1.3rem; height: 1.3rem; border: 0; border-radius: 999px;
        display: inline-flex; align-items: center; justify-content: center;
        background: #e2e8f0; color: #64748b; cursor: pointer; padding: 0;
    }
    .aa-search__clear svg { width: .72rem; height: .72rem; }
    .aa-search__pulse {
        flex-shrink: 0; width: .5rem; height: .5rem; border-radius: 999px;
        background: #2563eb; opacity: 0;
    }
    .aa-search__pulse.is-on { opacity: 1; animation: aa-pulse 1s ease-in-out infinite; }
    @keyframes aa-pulse { 0%,100%{opacity:.35} 50%{opacity:1} }

    .aa-filters { display: flex; flex-wrap: wrap; gap: .4rem; align-items: center; }
    .aa-select {
        border: 1px solid var(--line); border-radius: 999px; background: #fff;
        padding: .45rem .75rem; font: inherit; font-size: .78rem; font-weight: 700;
        color: var(--muted); outline: none; cursor: pointer; max-width: 11rem;
    }
    .aa-select:focus { border-color: #93c5fd; color: var(--ink); }
    .aa-chip {
        display: inline-flex; align-items: center; padding: .45rem .75rem; border-radius: 999px;
        border: 1px solid var(--line); background: #fff; color: var(--muted);
        font-size: .78rem; font-weight: 700; cursor: pointer; font: inherit;
    }
    .aa-chip:hover { border-color: #cbd5e1; color: var(--ink); }
    .aa-chip.is-active {
        border-color: #2563eb; color: #1d4ed8;
        box-shadow: 0 0 0 2px rgba(37,99,235,.12);
    }

    .aa-results { transition: opacity .15s ease; }
    .aa-results.is-loading { opacity: .55; pointer-events: none; }

    .aa-panel {
        background: #fff; border: 1px solid var(--line); border-radius: 1.05rem;
        box-shadow: 0 8px 24px rgba(15,23,42,.035); overflow: hidden;
    }
    .aa-table { width: 100%; border-collapse: collapse; }
    .aa-table th {
        text-align: left; padding: .75rem 1rem; font-size: .7rem; font-weight: 700;
        letter-spacing: .06em; text-transform: uppercase; color: var(--muted);
        background: #f8fafc; border-bottom: 1px solid var(--line);
    }
    .aa-table td {
        padding: .75rem 1rem; border-bottom: 1px solid #f1f5f9;
        vertical-align: middle; font-size: .86rem;
    }
    .aa-table tr:last-child td { border-bottom: 0; }
    .aa-table tr:hover td { background: #fafbfc; }
    .aa-title {
        margin: 0; font-size: .92rem; font-weight: 800; color: var(--ink); line-height: 1.35;
    }
    .aa-title a { color: inherit; text-decoration: none; }
    .aa-title a:hover { color: #1d4ed8; }
    .aa-meta { margin: .3rem 0 0; font-size: .74rem; color: var(--muted); line-height: 1.4; }
    .aa-authors { margin: .25rem 0 0; font-size: .76rem; color: #64748b; }
    .aa-badge {
        display: inline-flex; align-items: center;
        font-size: .66rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase;
        padding: .24rem .5rem; border-radius: 999px;
    }
    .aa-badge--open { background: #e8f1fc; color: #1d4ed8; }
    .aa-badge--members_only { background: #fef3c7; color: #b45309; }
    .aa-badge--paid { background: #f3e8ff; color: #7e22ce; }
    .aa-badge--closed { background: #f1f5f9; color: #64748b; }
    .aa-badge--published { background: #dcfce7; color: #15803d; }
    .aa-badge--draft { background: #f1f5f9; color: #64748b; }
    .aa-table th.aa-table__actions,
    .aa-table td.aa-table__actions {
        width: 1%; white-space: nowrap; text-align: right;
    }
    .aa-action-group {
        display: inline-flex; align-items: stretch;
        border: 1px solid #dbeafe; border-radius: .6rem;
        overflow: hidden; background: #fff;
        box-shadow: 0 1px 2px rgba(15,23,42,.04);
    }
    .aa-action {
        display: inline-flex; align-items: center; justify-content: center; gap: .32rem;
        padding: .42rem .68rem; border: 0; background: #fff;
        color: #475569; font: inherit; font-size: .72rem; font-weight: 700;
        line-height: 1; text-decoration: none; cursor: pointer;
        transition: background .15s ease, color .15s ease;
    }
    .aa-action + .aa-action { border-left: 1px solid #e2e8f0; }
    .aa-action svg { width: .82rem; height: .82rem; flex-shrink: 0; }
    .aa-action:hover { background: #f8fafc; color: #1e293b; }
    .aa-action--preview:hover { color: #1d4ed8; background: #eff6ff; }
    .aa-action--edit {
        background: #2563eb; color: #fff;
    }
    .aa-action--edit:hover {
        background: #1d4ed8; color: #fff;
    }
    .aa-action--solo {
        border: 1px solid #dbeafe; border-radius: .6rem;
        box-shadow: 0 1px 2px rgba(15,23,42,.04);
    }

    .aa-cards { display: none; }
    @media (max-width: 860px) {
        .aa-table-wrap { display: none; }
        .aa-cards { display: grid; gap: .65rem; }
    }
    .aa-card {
        background: #fff; border: 1px solid var(--line); border-radius: .95rem;
        padding: .9rem 1rem; box-shadow: 0 8px 24px rgba(15,23,42,.035);
    }
    .aa-card__actions { display: flex; gap: .45rem; margin-top: .75rem; }
    .aa-card__actions .aa-action-group,
    .aa-card__actions .aa-action--solo { width: 100%; }
    .aa-card__actions .aa-action { flex: 1; padding: .55rem .75rem; font-size: .78rem; }

    .aa-empty {
        background: #fff; border: 1px solid var(--line); border-radius: 1.05rem;
        padding: 2.25rem 1.25rem; text-align: center;
        box-shadow: 0 8px 24px rgba(15,23,42,.035);
    }
    .aa-empty__title { margin: 0; font-size: 1rem; font-weight: 800; color: var(--ink); }
    .aa-empty__text { margin: .35rem 0 0; font-size: .86rem; color: var(--muted); }

    .aa-footer {
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
        gap: .75rem; margin-top: 1rem;
    }
    .aa-perpage {
        display: inline-flex; align-items: center; gap: .4rem;
        font-size: .8rem; color: var(--muted); font-weight: 600;
    }
    .aa-perpage select {
        border: 1px solid var(--line); border-radius: .55rem; background: #fff;
        padding: .35rem .5rem; font: inherit; font-size: .8rem;
    }
    [x-cloak] { display: none !important; }
</style>

<script>
    window.aaArticleSearch = function (cfg) {
        return {
            q: cfg.q || '',
            journalId: cfg.journalId || '',
            status: cfg.status || null,
            visibility: cfg.visibility || null,
            perPage: cfg.perPage || 12,
            baseUrl: cfg.baseUrl,
            pending: false,
            timer: null,
            init() {
                this.$nextTick(() => {
                    const input = this.$refs.q;
                    if (input && sessionStorage.getItem('aa-articles-focus') === '1') {
                        input.focus();
                        const len = input.value.length;
                        input.setSelectionRange(len, len);
                    }
                    sessionStorage.removeItem('aa-articles-focus');
                });
            },
            buildUrl(overrides = {}) {
                const params = new URLSearchParams();
                const q = String(overrides.q !== undefined ? overrides.q : this.q).trim();
                const journalId = overrides.journalId !== undefined ? overrides.journalId : this.journalId;
                const status = overrides.status !== undefined ? overrides.status : this.status;
                const visibility = overrides.visibility !== undefined ? overrides.visibility : this.visibility;
                const perPage = overrides.perPage !== undefined ? overrides.perPage : this.perPage;

                if (q) params.set('q', q);
                if (journalId) params.set('journal_id', String(journalId));
                if (status) params.set('status', status);
                if (visibility) params.set('visibility', visibility);
                if (perPage && Number(perPage) !== 12) params.set('per_page', String(perPage));

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
                sessionStorage.setItem('aa-articles-focus', '1');
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
            setVisibility(visibility) {
                this.visibility = visibility;
                clearTimeout(this.timer);
                this.go({ visibility });
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
                this.visibility = null;
                clearTimeout(this.timer);
                this.go({ q: '', journalId: '', status: null, visibility: null });
            },
        };
    };
</script>

<div
    class="aa-page"
    x-data="aaArticleSearch({
        q: @js($q ?? ''),
        journalId: @js($journalId ? (string) $journalId : ''),
        status: @js($status),
        visibility: @js($visibility),
        perPage: {{ (int) $perPage }},
        baseUrl: @js($articlesIndexUrl()),
    })"
>
    <div class="aa-stats">
        <a href="{{ $articlesIndexUrl(['q' => $q, 'journal_id' => $journalId, 'per_page' => $perPage]) }}"
           class="aa-stat {{ $status === null && $visibility === null ? 'is-active' : '' }}">
            <p class="aa-stat__label">Total</p>
            <p class="aa-stat__value">{{ number_format($stats['total']) }}</p>
        </a>
        <a href="{{ $articlesIndexUrl(['q' => $q, 'journal_id' => $journalId, 'status' => 'published', 'visibility' => $visibility, 'per_page' => $perPage]) }}"
           class="aa-stat {{ $status === 'published' ? 'is-active' : '' }}">
            <p class="aa-stat__label">Published</p>
            <p class="aa-stat__value">{{ number_format($stats['published']) }}</p>
        </a>
        <a href="{{ $articlesIndexUrl(['q' => $q, 'journal_id' => $journalId, 'status' => 'draft', 'visibility' => $visibility, 'per_page' => $perPage]) }}"
           class="aa-stat {{ $status === 'draft' ? 'is-active' : '' }}">
            <p class="aa-stat__label">Draft</p>
            <p class="aa-stat__value">{{ number_format($stats['draft']) }}</p>
        </a>
        <a href="{{ $articlesIndexUrl(['q' => $q, 'journal_id' => $journalId, 'status' => $status, 'visibility' => 'open', 'per_page' => $perPage]) }}"
           class="aa-stat {{ $visibility === 'open' ? 'is-active' : '' }}">
            <p class="aa-stat__label">Open</p>
            <p class="aa-stat__value">{{ number_format($stats['open']) }}</p>
        </a>
        <a href="{{ $articlesIndexUrl(['q' => $q, 'journal_id' => $journalId, 'status' => $status, 'visibility' => 'members_only', 'per_page' => $perPage]) }}"
           class="aa-stat {{ $visibility === 'members_only' ? 'is-active' : '' }}">
            <p class="aa-stat__label">Members</p>
            <p class="aa-stat__value">{{ number_format($stats['members_only']) }}</p>
        </a>
        <a href="{{ $articlesIndexUrl(['q' => $q, 'journal_id' => $journalId, 'status' => $status, 'visibility' => 'paid', 'per_page' => $perPage]) }}"
           class="aa-stat {{ $visibility === 'paid' ? 'is-active' : '' }}">
            <p class="aa-stat__label">Paid</p>
            <p class="aa-stat__value">{{ number_format($stats['paid']) }}</p>
        </a>
        <a href="{{ $articlesIndexUrl(['q' => $q, 'journal_id' => $journalId, 'status' => $status, 'visibility' => 'closed', 'per_page' => $perPage]) }}"
           class="aa-stat {{ $visibility === 'closed' ? 'is-active' : '' }}">
            <p class="aa-stat__label">Closed</p>
            <p class="aa-stat__value">{{ number_format($stats['closed']) }}</p>
        </a>
    </div>

    <div class="aa-toolbar" role="search">
        <label class="aa-search">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M20 20l-3.5-3.5"/></svg>
            <input
                type="search"
                x-ref="q"
                x-model="q"
                @input="onType()"
                @keydown.enter.prevent="flush()"
                placeholder="Search title, slug, DOI, keywords…"
                autocomplete="off"
                aria-label="Search articles"
            >
            <button type="button" class="aa-search__clear" x-show="q.length > 0" x-cloak @click="clearQuery()" aria-label="Clear search">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
            <span class="aa-search__pulse" :class="{ 'is-on': pending }" aria-hidden="true"></span>
        </label>

        <div class="aa-filters">
            @unless(isset($manageJournal))
            <x-journal-picker
                :journals="$journals"
                name="_filter_journal"
                :value="(string) ($journalId ?? '')"
                allow-empty
                empty-label="All journals"
                class="aa-journal-filter"
                @picker-change="journalId = $event.detail; onJournalChange()"
            />
            @endunless

            <button type="button" class="aa-chip" :class="{ 'is-active': !status }" @click="setStatus(null)">All status</button>
            <button type="button" class="aa-chip" :class="{ 'is-active': status === 'published' }" @click="setStatus('published')">Published</button>
            <button type="button" class="aa-chip" :class="{ 'is-active': status === 'draft' }" @click="setStatus('draft')">Draft</button>

            <button type="button" class="aa-chip" :class="{ 'is-active': !visibility }" @click="setVisibility(null)">Any access</button>
            <button type="button" class="aa-chip" :class="{ 'is-active': visibility === 'open' }" @click="setVisibility('open')">Open</button>
            <button type="button" class="aa-chip" :class="{ 'is-active': visibility === 'members_only' }" @click="setVisibility('members_only')">Members</button>
            <button type="button" class="aa-chip" :class="{ 'is-active': visibility === 'paid' }" @click="setVisibility('paid')">Paid</button>
            <button type="button" class="aa-chip" :class="{ 'is-active': visibility === 'closed' }" @click="setVisibility('closed')">Closed</button>
        </div>
        </div>

    <div class="aa-results" :class="{ 'is-loading': pending }">
        @if($articles->isEmpty())
            <div class="aa-empty">
                <p class="aa-empty__title">{{ $q || $journalId || $status || $visibility ? 'No articles match your filters' : 'No articles yet' }}</p>
                <p class="aa-empty__text">
                    {{ $q || $journalId || $status || $visibility ? 'Try a different search or clear filters.' : 'Create your first article to populate the catalog.' }}
                </p>
                @if($q || $journalId || $status || $visibility)
                    <button type="button" class="admin-btn admin-btn-secondary" style="margin-top:1rem" @click="clearAll()">Clear filters</button>
                @else
                    <a href="{{ $articlesCreateUrl }}" class="admin-btn admin-btn-primary" style="margin-top:1rem">New article</a>
                @endif
            </div>
        @else
            <div class="aa-panel aa-table-wrap">
                <table class="aa-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Journal</th>
                            <th>Access</th>
                            <th>Status</th>
                            <th class="aa-table__actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                        @foreach($articles as $article)
                            @php
                                $authors = $article->authors->pluck('name')->filter()->take(3)->implode(', ');
                                if ($article->authors->count() > 3) {
                                    $authors .= ' et al.';
                                }
                            @endphp
                            <tr>
                                <td>
                                    <h3 class="aa-title">
                                        <a href="{{ $articlesEdit($article) }}">{{ $article->title }}</a>
                                    </h3>
                                    <p class="aa-meta">
                                        {{ $article->slug }}
                                        @if($article->issue)
                                            · {{ $article->issue->label() }}
                                        @endif
                                        @if($article->doi) · DOI {{ $article->doi }}@endif
                                    </p>
                                    @if($authors)
                                        <p class="aa-authors">{{ $authors }}</p>
                                    @endif
                                </td>
                                <td style="color:#475569;font-weight:600">{{ $article->journal?->title }}</td>
                                <td>
                                    <span class="aa-badge aa-badge--{{ $article->visibility }}">
                                        {{ str_replace('_', ' ', $article->visibility) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="aa-badge aa-badge--{{ $article->status }}">{{ $article->status }}</span>
                                </td>
                                <td class="aa-table__actions">
                                    @if($article->journal)
                                        <div class="aa-action-group">
                                            <a href="{{ route('journals.articles.show', [$article->journal, $article]) }}" target="_blank" rel="noopener" class="aa-action aa-action--preview" title="Preview on site">
                                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                Preview
                                            </a>
                                            <a href="{{ $articlesEdit($article) }}" class="aa-action aa-action--edit" title="Edit article">
                                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                Edit
                                            </a>
                                        </div>
                                    @else
                                        <a href="{{ $articlesEdit($article) }}" class="aa-action aa-action--edit aa-action--solo" title="Edit article">
                                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            Edit
                                        </a>
                                    @endif
                        </td>
                    </tr>
                        @endforeach
            </tbody>
        </table>
    </div>

            <div class="aa-cards">
                @foreach($articles as $article)
                    <article class="aa-card">
                        <h3 class="aa-title">
                            <a href="{{ $articlesEdit($article) }}">{{ $article->title }}</a>
                        </h3>
                        <p class="aa-meta">{{ $article->journal?->title }} · {{ $article->slug }}</p>
                        <div style="display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.55rem">
                            <span class="aa-badge aa-badge--{{ $article->visibility }}">{{ str_replace('_', ' ', $article->visibility) }}</span>
                            <span class="aa-badge aa-badge--{{ $article->status }}">{{ $article->status }}</span>
                        </div>
                        <div class="aa-card__actions">
                            @if($article->journal)
                                <div class="aa-action-group">
                                    <a href="{{ route('journals.articles.show', [$article->journal, $article]) }}" target="_blank" rel="noopener" class="aa-action aa-action--preview">
                                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        Preview
                                    </a>
                                    <a href="{{ $articlesEdit($article) }}" class="aa-action aa-action--edit">
                                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Edit
                                    </a>
                                </div>
                            @else
                                <a href="{{ $articlesEdit($article) }}" class="aa-action aa-action--edit aa-action--solo">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Edit
                                </a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="aa-footer">
                <div>{{ $articles->links() }}</div>
                <form method="GET" action="{{ $articlesIndexUrl() }}" class="aa-perpage">
                    @if($q)<input type="hidden" name="q" value="{{ $q }}">@endif
                    @if($journalId)<input type="hidden" name="journal_id" value="{{ $journalId }}">@endif
                    @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
                    @if($visibility)<input type="hidden" name="visibility" value="{{ $visibility }}">@endif
                    <label for="per_page_trigger">Show</label>
                    <x-tjs-select
                        name="per_page"
                        input-id="per_page"
                        class="tjs-select--compact"
                        :value="(string) $perPage"
                        :options="collect([12, 24, 48])->map(fn ($n) => ['value' => (string) $n, 'label' => (string) $n])->all()"
                        submit-on-change
                    />
                    <span>per page</span>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
