@extends('layouts.admin')

@section('title', 'Journals | Admin')
@section('page_title', 'Journals')
@section('page_subtitle', 'Create and manage journals')

@section('page_actions')
    <a href="{{ route('admin.journals.create') }}" class="admin-btn admin-btn-primary">
        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 8v8M8 12h8"/></svg>
        New journal
    </a>
@endsection

@section('content')
<style>
    .aj-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: .85rem;
        margin-bottom: 1rem;
    }
    @media (min-width: 960px) {
        .aj-stats { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }
    .aj-stat {
        position: relative;
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 1rem;
        padding: 1rem 1.05rem 1.05rem;
        box-shadow: 0 8px 24px rgba(15,23,42,.035);
        text-decoration: none;
        color: inherit;
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
        overflow: hidden;
    }
    .aj-stat:hover { transform: translateY(-1px); box-shadow: 0 12px 28px rgba(15,23,42,.06); }
    .aj-stat.is-active {
        border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(37,99,235,.12), 0 10px 24px rgba(15,23,42,.05);
    }
    .aj-stat__icon {
        position: absolute; top: .9rem; right: .9rem;
        width: 2.15rem; height: 2.15rem; border-radius: .7rem;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--aj-soft, #dbeafe); color: var(--aj-accent, #2563eb);
    }
    .aj-stat__icon svg { width: 1.05rem; height: 1.05rem; }
    .aj-stat__label {
        font-size: .7rem; font-weight: 700; letter-spacing: .08em;
        text-transform: uppercase; color: var(--muted);
    }
    .aj-stat__value {
        margin-top: .45rem; font-size: 1.7rem; font-weight: 800;
        letter-spacing: -.04em; color: var(--ink); line-height: 1;
    }
    .aj-stat__hint {
        margin: .4rem 0 0; font-size: .75rem; color: var(--muted); line-height: 1.35;
        max-width: 85%;
    }

    .aj-toolbar {
        display: flex; flex-direction: column; gap: .75rem;
        background: #fff; border: 1px solid var(--line); border-radius: 1rem;
        padding: .85rem .95rem; margin-bottom: 1rem;
        box-shadow: 0 8px 24px rgba(15,23,42,.035);
    }
    @media (min-width: 860px) {
        .aj-toolbar {
            flex-direction: row; align-items: center;
            gap: .7rem;
        }
    }
    .aj-search {
        flex: 1; display: flex; align-items: center; gap: .55rem;
        border: 1px solid var(--line); border-radius: .75rem;
        background: #f8fafc; padding: .62rem .85rem; min-width: 0;
        position: relative;
    }
    .aj-search:focus-within {
        border-color: #93c5fd; background: #fff;
        box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    }
    .aj-search > svg { width: 1rem; height: 1rem; color: var(--muted); flex-shrink: 0; }
    .aj-search input {
        width: 100%; border: 0; outline: 0; background: transparent;
        font: inherit; font-size: .9rem; color: var(--ink);
        min-width: 0;
    }
    .aj-search input::-webkit-search-cancel-button { display: none; }
    .aj-search__clear {
        flex-shrink: 0; width: 1.35rem; height: 1.35rem; border: 0; border-radius: 999px;
        display: inline-flex; align-items: center; justify-content: center;
        background: #e2e8f0; color: #64748b; cursor: pointer; padding: 0;
    }
    .aj-search__clear:hover { background: #cbd5e1; color: var(--ink); }
    .aj-search__clear svg { width: .75rem; height: .75rem; }
    .aj-search__pulse {
        flex-shrink: 0; width: .55rem; height: .55rem; border-radius: 999px;
        background: #2563eb; opacity: 0; transform: scale(.6);
        transition: opacity .15s ease, transform .15s ease;
    }
    .aj-search__pulse.is-on {
        opacity: 1; transform: scale(1);
        animation: aj-pulse 1s ease-in-out infinite;
    }
    @keyframes aj-pulse {
        0%, 100% { opacity: .35; }
        50% { opacity: 1; }
    }
    .aj-filters { display: flex; flex-wrap: wrap; gap: .4rem; align-items: center; }
    .aj-view-toggle {
        display: inline-flex; align-items: center; gap: .2rem;
        padding: .2rem; border: 1px solid var(--line); border-radius: .65rem;
        background: #f8fafc; margin-left: auto;
    }
    .aj-view-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 2rem; height: 2rem; border: 0; border-radius: .5rem;
        background: transparent; color: #94a3b8; cursor: pointer; padding: 0;
    }
    .aj-view-btn:hover { color: var(--ink); background: #fff; }
    .aj-view-btn.is-active {
        background: #fff; color: #1d4ed8;
        box-shadow: 0 1px 2px rgba(15,23,42,.08);
    }
    .aj-view-btn svg { width: 1rem; height: 1rem; }
    .aj-chip {
        display: inline-flex; align-items: center;
        padding: .48rem .8rem; border-radius: 999px;
        border: 1px solid var(--line); background: #fff;
        color: var(--muted); font-size: .78rem; font-weight: 700;
        text-decoration: none; transition: .15s ease;
        cursor: pointer; font: inherit;
    }
    .aj-chip:hover { border-color: #cbd5e1; color: var(--ink); }
    .aj-chip.is-active {
        background: #fff; border-color: #2563eb; color: #1d4ed8;
        box-shadow: 0 0 0 2px rgba(37,99,235,.12);
    }
    .aj-results { transition: opacity .15s ease; }
    .aj-results.is-loading { opacity: .55; pointer-events: none; }
    [x-cloak] { display: none !important; }

    .aj-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    @media (min-width: 960px) {
        .aj-grid.is-grid { grid-template-columns: 1fr 1fr; }
    }
    .aj-grid.is-list {
        grid-template-columns: 1fr;
        gap: .65rem;
    }

    .aj-card {
        position: relative;
        overflow: visible;
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 1.05rem;
        box-shadow: 0 8px 24px rgba(15,23,42,.035);
        display: flex; flex-direction: column; min-height: 100%;
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .aj-card:hover {
        transform: translateY(-2px);
        border-color: #d7e0ea;
        box-shadow: 0 16px 34px rgba(15,23,42,.08);
    }
    .aj-grid.is-list .aj-card {
        min-height: 0; border-radius: .85rem;
        box-shadow: 0 4px 14px rgba(15,23,42,.03);
    }
    .aj-grid.is-list .aj-card:hover { transform: none; box-shadow: 0 8px 20px rgba(15,23,42,.06); }
    .aj-card__accent { height: 4px; background: var(--j-accent, #2563eb); border-radius: 1.05rem 1.05rem 0 0; }
    .aj-grid.is-list .aj-card__accent { height: 100%; width: 3px; position: absolute; left: 0; top: 0; bottom: 0; border-radius: .85rem 0 0 .85rem; }
    .aj-card__body { padding: 1.05rem 1.1rem 1.1rem; flex: 1; display: flex; flex-direction: column; }
    .aj-grid.is-list .aj-card__body {
        padding: .85rem 1rem .85rem 1.05rem;
        flex-direction: row; align-items: flex-start; gap: .85rem;
    }
    .aj-card__top { display: flex; gap: .85rem; align-items: flex-start; }
    .aj-grid.is-list .aj-card__top { flex: 1; min-width: 0; }
    .aj-card__logo {
        width: 3.1rem; height: 3.1rem; border-radius: .85rem; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--j-accent, #2563eb);
        color: #fff; font-weight: 800; font-size: .62rem; letter-spacing: .02em;
        text-align: center; line-height: 1.15; padding: .25rem;
        overflow: hidden;
    }
    .aj-grid.is-list .aj-card__logo { width: 2.5rem; height: 2.5rem; border-radius: .65rem; font-size: .55rem; }
    .aj-card__logo img { width: 100%; height: 100%; object-fit: cover; }
    .aj-card__head { min-width: 0; flex: 1; }
    .aj-card__title-row { display: flex; align-items: flex-start; justify-content: space-between; gap: .5rem; }
    .aj-card__title {
        margin: 0; font-size: 1.02rem; font-weight: 800; color: var(--ink); line-height: 1.3;
    }
    .aj-grid.is-list .aj-card__title { font-size: .95rem; }
    .aj-menu {
        width: 1.65rem; height: 1.65rem; border: 0; border-radius: .45rem;
        background: transparent; color: #94a3b8; cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .aj-menu:hover { background: #f1f5f9; color: var(--ink); }
    .aj-card__meta {
        margin: .3rem 0 0; font-size: .76rem; color: var(--muted); line-height: 1.4;
        word-break: break-word;
    }
    .aj-card__badges { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .55rem; }
    .aj-grid.is-list .aj-card__badges { margin-top: .4rem; }
    .aj-badge {
        display: inline-flex; align-items: center;
        font-size: .68rem; font-weight: 700; letter-spacing: .02em;
        padding: .28rem .55rem; border-radius: 999px;
    }
    .aj-badge--featured { background: #fef3c7; color: #b45309; }
    .aj-badge--active { background: #dcfce7; color: #15803d; }
    .aj-badge--inactive { background: #f1f5f9; color: #64748b; }
    .aj-card__desc {
        margin: .85rem 0 0; font-size: .84rem; color: var(--muted); line-height: 1.5;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .aj-grid.is-list .aj-card__desc {
        margin: .45rem 0 0; font-size: .82rem; -webkit-line-clamp: 3;
        flex: 1; min-width: 0;
    }
    .aj-card__main { min-width: 0; flex: 1; display: flex; flex-direction: column; }
    .aj-grid.is-list .aj-card__main { display: block; }
    .aj-card__metrics {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: .55rem;
        margin-top: 1rem; padding-top: .95rem; border-top: 1px solid var(--line);
    }
    .aj-grid.is-list .aj-card__metrics { display: none; }
    .aj-metric {
        display: flex; align-items: center; gap: .55rem;
        background: #f8fafc; border-radius: .75rem; padding: .6rem .6rem;
        min-height: 3.1rem;
    }
    .aj-metric__icon {
        width: 1.75rem; height: 1.75rem; border-radius: .5rem; flex-shrink: 0;
        display: flex !important; align-items: center; justify-content: center;
        background: #e2e8f0; color: #64748b;
        margin: 0 !important;
    }
    .aj-metric__icon svg {
        width: .9rem; height: .9rem; display: block; flex-shrink: 0;
    }
    .aj-metric__text {
        display: flex; flex-direction: column; justify-content: center;
        min-width: 0; line-height: 1.1;
    }
    .aj-metric__text strong {
        display: block; font-size: .95rem; font-weight: 800; color: var(--ink);
        letter-spacing: -.02em; line-height: 1.1;
    }
    .aj-metric__text span {
        display: block; margin-top: .2rem; font-size: .62rem; font-weight: 700;
        letter-spacing: .04em; text-transform: uppercase; color: var(--muted);
        line-height: 1;
    }
    .aj-card__actions {
        display: grid; grid-template-columns: 1fr 1fr 1fr; gap: .45rem;
        margin-top: 1rem;
    }
    .aj-grid.is-list .aj-card__actions {
        display: flex; flex-direction: column; gap: .35rem;
        margin-top: 0; flex-shrink: 0; width: 6.5rem;
    }
    .aj-card__actions .admin-btn,
    .aj-card__actions .admin-chip {
        width: 100%; justify-content: center; padding: .58rem .5rem; font-size: .8rem;
    }
    .aj-grid.is-list .aj-card__actions .admin-btn,
    .aj-grid.is-list .aj-card__actions .admin-chip {
        padding: .42rem .45rem; font-size: .72rem;
    }
    .aj-card__actions svg { width: .9rem; height: .9rem; }
    .aj-grid.is-list .aj-card__actions svg { display: none; }
    .aj-list-stats {
        display: none; margin-top: .45rem; font-size: .72rem; color: var(--muted); font-weight: 600;
        gap: .55rem; flex-wrap: wrap;
    }
    .aj-grid.is-list .aj-list-stats { display: flex; }

    .aj-empty {
        background: #fff; border: 1px solid var(--line); border-radius: 1.05rem;
        padding: 2.5rem 1.25rem; text-align: center;
        box-shadow: 0 8px 24px rgba(15,23,42,.035);
    }
    .aj-empty__icon {
        width: 3.25rem; height: 3.25rem; margin: 0 auto;
        border-radius: 1rem; display: flex; align-items: center; justify-content: center;
        background: #dbeafe; color: #2563eb;
    }
    .aj-empty__icon svg { width: 1.4rem; height: 1.4rem; }
    .aj-empty__title { margin: .9rem 0 0; font-size: 1rem; font-weight: 800; color: var(--ink); }
    .aj-empty__text { margin: .35rem 0 0; font-size: .86rem; color: var(--muted); }

    .aj-footer {
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
        gap: .75rem; margin-top: 1.15rem;
    }
    .aj-footer .pagination { margin: 0; }
    .aj-perpage {
        display: inline-flex; align-items: center; gap: .45rem;
        font-size: .8rem; color: var(--muted); font-weight: 600;
    }
    .aj-perpage select {
        border: 1px solid var(--line); border-radius: .55rem; background: #fff;
        padding: .4rem .55rem; font: inherit; font-size: .8rem; color: var(--ink);
    }
    .aj-dropdown { position: relative; }
    .aj-dropdown__menu {
        position: absolute; right: 0; top: calc(100% + .15rem); z-index: 30;
        min-width: 5.75rem; width: max-content; padding: .12rem;
        background: #fff; border: 1px solid var(--line); border-radius: .4rem;
        box-shadow: 0 6px 14px rgba(15,23,42,.1);
        display: none;
    }
    .aj-dropdown.is-open .aj-dropdown__menu { display: block; }
    .aj-dropdown__menu form { margin: 0; padding: 0; }
    .aj-dropdown__menu a,
    .aj-dropdown__menu button {
        display: block; width: 100%; box-sizing: border-box;
        margin: 0; padding: .28rem .45rem; border-radius: .28rem;
        color: var(--ink); text-decoration: none;
        font-family: inherit; font-size: .68rem; font-weight: 600; line-height: 1.15;
        background: transparent; border: 0; text-align: left; cursor: pointer;
    }
    .aj-dropdown__menu a:hover,
    .aj-dropdown__menu button:hover { background: #f1f5f9; }
    .aj-dropdown__menu button.is-danger { color: #b91c1c; }
    .aj-dropdown__menu button.is-danger:hover { background: #fef2f2; }

    .aj-modal {
        position: fixed; inset: 0; z-index: 80;
        display: flex; align-items: center; justify-content: center;
        padding: 1rem;
    }
    .aj-modal__backdrop {
        position: absolute; inset: 0;
        background: rgba(15, 23, 42, .48);
        backdrop-filter: blur(3px);
    }
    .aj-modal__panel {
        position: relative; z-index: 1; width: min(100%, 24rem);
        background: #fff; border: 1px solid var(--line); border-radius: 1rem;
        box-shadow: 0 24px 60px rgba(15,23,42,.22);
        overflow: hidden;
    }
    .aj-modal__head {
        padding: 1rem 1.1rem .85rem;
        border-bottom: 1px solid #fecaca;
        background: #fef2f2;
    }
    .aj-modal__eyebrow {
        margin: 0; font-size: .68rem; font-weight: 800; letter-spacing: .08em;
        text-transform: uppercase; color: #b91c1c;
    }
    .aj-modal__title {
        margin: .35rem 0 0; font-size: 1.05rem; font-weight: 800; color: #7f1d1d; line-height: 1.3;
    }
    .aj-modal__body { padding: 1rem 1.1rem 1.15rem; }
    .aj-modal__text {
        margin: 0; font-size: .84rem; color: var(--muted); line-height: 1.5;
    }
    .aj-modal__label {
        display: block; margin: .9rem 0 .4rem;
        font-size: .76rem; font-weight: 700; color: #334155;
    }
    .aj-modal__label code {
        font-size: .72rem; background: #f1f5f9; color: var(--ink);
        padding: .12rem .35rem; border-radius: .3rem;
    }
    .aj-modal__input {
        width: 100%; border: 1px solid #e2e8f0; border-radius: .7rem;
        padding: .68rem .8rem; font: inherit; font-size: .88rem; color: var(--ink);
        outline: none; background: #fff;
    }
    .aj-modal__input:focus {
        border-color: #fca5a5;
        box-shadow: 0 0 0 3px rgba(185, 28, 28, .12);
    }
    .aj-modal__error {
        margin: .4rem 0 0; font-size: .74rem; font-weight: 600; color: #b91c1c;
    }
    .aj-modal__actions {
        display: flex; gap: .5rem; justify-content: flex-end; margin-top: 1rem;
    }
    .aj-modal__actions .admin-btn { min-height: 2.35rem; }
    .aj-modal__danger {
        background: #b91c1c; color: #fff;
        box-shadow: 0 8px 16px rgba(185, 28, 28, .22);
    }
    .aj-modal__danger:disabled {
        opacity: .45; cursor: not-allowed; box-shadow: none;
    }

    @media (max-width: 720px) {
        .aj-grid.is-list .aj-card__body { flex-direction: column; }
        .aj-grid.is-list .aj-card__actions {
            width: 100%; flex-direction: row; display: grid; grid-template-columns: 1fr 1fr 1fr;
        }
        .aj-view-toggle { margin-left: 0; }
    }
</style>

@php
    $currentStatus = request('status');
    $q = request('q');
@endphp

<script>
    window.ajJournalSearch = function (cfg) {
        const savedView = localStorage.getItem('aj-journals-view');
        return {
            q: cfg.q || '',
            status: cfg.status || null,
            perPage: cfg.perPage || 6,
            baseUrl: cfg.baseUrl,
            view: savedView === 'list' || savedView === 'grid' ? savedView : 'grid',
            pending: false,
            timer: null,
            deleteOpen: false,
            deleteTitle: '',
            deleteSlug: '',
            deleteAction: '',
            deleteConfirm: '',
            deleteMismatch: false,
            init() {
                this.$nextTick(() => {
                    const input = this.$refs.q;
                    if (! input || ! document.activeElement || document.activeElement === document.body) {
                        if (input && (this.q || sessionStorage.getItem('aj-journals-focus') === '1')) {
                            input.focus();
                            const len = input.value.length;
                            input.setSelectionRange(len, len);
                        }
                    }
                    sessionStorage.removeItem('aj-journals-focus');
                });
            },
            setView(mode) {
                this.view = mode;
                localStorage.setItem('aj-journals-view', mode);
            },
            askDelete(detail) {
                this.deleteTitle = detail.title || '';
                this.deleteSlug = detail.slug || '';
                this.deleteAction = detail.action || '';
                this.deleteConfirm = '';
                this.deleteMismatch = false;
                this.deleteOpen = true;
                this.$nextTick(() => this.$refs.deleteInput?.focus());
            },
            closeDelete() {
                this.deleteOpen = false;
                this.deleteConfirm = '';
                this.deleteMismatch = false;
            },
            submitDelete() {
                if (this.deleteConfirm !== this.deleteSlug) {
                    this.deleteMismatch = true;
                    this.$refs.deleteInput?.focus();
                    return;
                }
                this.$refs.deleteForm?.submit();
            },
            buildUrl(overrides = {}) {
                const params = new URLSearchParams();
                const q = String(overrides.q !== undefined ? overrides.q : this.q).trim();
                const status = overrides.status !== undefined ? overrides.status : this.status;
                const perPage = overrides.perPage !== undefined ? overrides.perPage : this.perPage;

                if (q) params.set('q', q);
                if (status) params.set('status', status);
                if (perPage && Number(perPage) !== 6) params.set('per_page', String(perPage));

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
                sessionStorage.setItem('aj-journals-focus', '1');
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
            clearQuery() {
                this.q = '';
                clearTimeout(this.timer);
                this.go({ q: '' });
            },
        };
    };
</script>

<div
    class="aj-page"
    x-data="ajJournalSearch({
        q: @js($q ?? ''),
        status: @js($currentStatus),
        perPage: {{ (int) $perPage }},
        baseUrl: @js(route('admin.journals.index')),
    })"
    @keydown.escape.window="if (deleteOpen) closeDelete()"
    @aj-ask-delete="askDelete($event.detail)"
>
<div class="aj-stats">
    <a href="{{ route('admin.journals.index', array_filter(['q' => $q, 'per_page' => $perPage])) }}"
       class="aj-stat {{ $currentStatus === null ? 'is-active' : '' }}" style="--aj-accent:#2563eb;--aj-soft:#dbeafe">
        <span class="aj-stat__icon">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5C4 4.67 4.67 4 5.5 4H11v16H5.5A1.5 1.5 0 014 18.5v-13zM20 5.5c0-.83-.67-1.5-1.5-1.5H13v16h5.5a1.5 1.5 0 001.5-1.5v-13z"/><path stroke-linecap="round" d="M12 4v16"/></svg>
        </span>
        <p class="aj-stat__label">Total</p>
        <p class="aj-stat__value">{{ number_format($stats['total']) }}</p>
        <p class="aj-stat__hint">All journals on the platform</p>
    </a>
    <a href="{{ route('admin.journals.index', array_filter(['q' => $q, 'status' => 'active', 'per_page' => $perPage])) }}"
       class="aj-stat {{ $currentStatus === 'active' ? 'is-active' : '' }}" style="--aj-accent:#2563eb;--aj-soft:#dbeafe">
        <span class="aj-stat__icon">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M8.5 12.2l2.2 2.2 4.8-4.8"/></svg>
        </span>
        <p class="aj-stat__label">Active</p>
        <p class="aj-stat__value">{{ number_format($stats['active']) }}</p>
        <p class="aj-stat__hint">Journals currently active</p>
    </a>
    <a href="{{ route('admin.journals.index', array_filter(['q' => $q, 'status' => 'featured', 'per_page' => $perPage])) }}"
       class="aj-stat {{ $currentStatus === 'featured' ? 'is-active' : '' }}" style="--aj-accent:#d97706;--aj-soft:#fef3c7">
        <span class="aj-stat__icon">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4l2.2 4.5 5 .7-3.6 3.5.9 5L12 15.8 7.5 17.7l.9-5L4.8 9.2l5-.7L12 4z"/></svg>
        </span>
        <p class="aj-stat__label">Featured</p>
        <p class="aj-stat__value">{{ number_format($stats['featured']) }}</p>
        <p class="aj-stat__hint">Highlighted on the homepage</p>
    </a>
    <a href="{{ route('admin.journals.index', array_filter(['q' => $q, 'status' => 'inactive', 'per_page' => $perPage])) }}"
       class="aj-stat {{ $currentStatus === 'inactive' ? 'is-active' : '' }}" style="--aj-accent:#64748b;--aj-soft:#e2e8f0">
        <span class="aj-stat__icon">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M10 9v6M14 9v6"/></svg>
        </span>
        <p class="aj-stat__label">Inactive</p>
        <p class="aj-stat__value">{{ number_format($stats['inactive']) }}</p>
        <p class="aj-stat__hint">Hidden from public browse</p>
    </a>
</div>

<div class="aj-toolbar" role="search">
    <label class="aj-search">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M20 20l-3.5-3.5"/></svg>
        <input
            type="search"
            x-ref="q"
            x-model="q"
            @input="onType()"
            @keydown.enter.prevent="flush()"
            placeholder="Search by title, slug, or ISSN…"
            autocomplete="off"
            aria-label="Search journals"
        >
        <button
            type="button"
            class="aj-search__clear"
            x-show="q.length > 0"
            x-cloak
            @click="clearQuery()"
            aria-label="Clear search"
        >
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
        <span class="aj-search__pulse" :class="{ 'is-on': pending }" aria-hidden="true"></span>
    </label>
    <div class="aj-filters">
        <button type="button" class="aj-chip" :class="{ 'is-active': !status }" @click="setStatus(null)">All</button>
        <button type="button" class="aj-chip" :class="{ 'is-active': status === 'active' }" @click="setStatus('active')">Active</button>
        <button type="button" class="aj-chip" :class="{ 'is-active': status === 'featured' }" @click="setStatus('featured')">Featured</button>
        <button type="button" class="aj-chip" :class="{ 'is-active': status === 'inactive' }" @click="setStatus('inactive')">Inactive</button>
        <div class="aj-view-toggle" role="group" aria-label="Layout">
            <button
                type="button"
                class="aj-view-btn"
                :class="{ 'is-active': view === 'grid' }"
                @click="setView('grid')"
                title="Grid view"
                aria-label="Grid view"
            >
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z"/></svg>
            </button>
            <button
                type="button"
                class="aj-view-btn"
                :class="{ 'is-active': view === 'list' }"
                @click="setView('list')"
                title="Description view"
                aria-label="Description view"
            >
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M5 7h14M5 12h14M5 17h10"/></svg>
            </button>
        </div>
    </div>
</div>

<div class="aj-results" :class="{ 'is-loading': pending }">
@if($journals->isEmpty())
    <div class="aj-empty">
        <div class="aj-empty__icon">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5C4 4.67 4.67 4 5.5 4H11v16H5.5A1.5 1.5 0 014 18.5v-13zM20 5.5c0-.83-.67-1.5-1.5-1.5H13v16h5.5a1.5 1.5 0 001.5-1.5v-13z"/><path stroke-linecap="round" d="M12 4v16"/></svg>
        </div>
        <p class="aj-empty__title">{{ $q || $currentStatus ? 'No journals match your filters' : 'No journals yet' }}</p>
        <p class="aj-empty__text">
            {{ $q || $currentStatus ? 'Try a different search or clear filters.' : 'Create your first journal to start publishing.' }}
        </p>
        @if($q || $currentStatus)
            <button type="button" class="admin-btn admin-btn-secondary" style="margin-top:1rem" @click="q = ''; status = null; go({ q: '', status: null })">Clear filters</button>
        @else
            <a href="{{ route('admin.journals.create') }}" class="admin-btn admin-btn-primary" style="margin-top:1rem">Create journal</a>
        @endif
    </div>
@else
    <div class="aj-grid" :class="view === 'list' ? 'is-list' : 'is-grid'">
        @foreach($journals as $journal)
            @php
                $theme = $journal->themeConfig();
                $accent = $theme['primary'] ?? '#2563eb';
                $initials = $journal->displayInitials();
                $desc = $journal->subtitle ?: \Illuminate\Support\Str::limit(strip_tags($journal->description ?? ''), 180);
            @endphp
            <article class="aj-card" style="--j-accent: {{ $accent }}" x-data="{ open: false }" @click.outside="open = false">
                <div class="aj-card__accent"></div>
                <div class="aj-card__body">
                    <div class="aj-card__main">
                        <div class="aj-card__top">
                            <div class="aj-card__logo">
                                @if($journal->logoUrl())
                                    <img src="{{ $journal->logoUrl() }}" alt="">
                                @else
                                    {{ $initials }}
                                @endif
                            </div>
                            <div class="aj-card__head">
                                <div class="aj-card__title-row">
                                    <h2 class="aj-card__title">{{ $journal->title }}</h2>
                                    <div class="aj-dropdown" :class="{ 'is-open': open }">
                                        <button type="button" class="aj-menu" @click="open = !open" aria-label="More actions">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="19" r="1.5"/></svg>
                                        </button>
                                        <div class="aj-dropdown__menu">
                                            <a href="{{ route('journals.show', $journal) }}" target="_blank" rel="noopener">Preview</a>
                                            <a href="{{ route('admin.journals.edit', $journal) }}">Edit</a>
                                            <a href="{{ route('admin.volumes.index', $journal) }}">Volumes</a>
                                            <button
                                                type="button"
                                                class="is-danger"
                                                @click="open = false; $dispatch('aj-ask-delete', {
                                                    title: @js($journal->title),
                                                    slug: @js($journal->slug),
                                                    action: @js(route('admin.journals.destroy', $journal)),
                                                })"
                                            >Delete</button>
                                        </div>
                                    </div>
                                </div>
                                <p class="aj-card__meta">
                                    {{ $journal->slug }}
                                    @if($journal->issn) · ISSN {{ $journal->issn }}@endif
                                </p>
                                <div class="aj-card__badges">
                                    @if($journal->is_featured)<span class="aj-badge aj-badge--featured">Featured</span>@endif
                                    @if($journal->is_active)
                                        <span class="aj-badge aj-badge--active">Active</span>
                                    @else
                                        <span class="aj-badge aj-badge--inactive">Inactive</span>
                                    @endif
                                </div>
                                <p class="aj-list-stats">
                                    <span>{{ number_format($journal->articles_count) }} articles</span>
                                    <span>{{ number_format($journal->volumes_count) }} volumes</span>
                                    <span>{{ number_format($journal->submissions_count) }} submissions</span>
                                </p>
                            </div>
                        </div>

                        @if($desc)
                            <p class="aj-card__desc">{{ $desc }}</p>
                        @endif

                        <div class="aj-card__metrics">
                            <div class="aj-metric">
                                <div class="aj-metric__icon" aria-hidden="true">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3.5h7.5L19 8v12.5a1 1 0 01-1 1H7a1 1 0 01-1-1V4.5a1 1 0 011-1z"/><path d="M14.5 3.5V8H19"/></svg>
                                </div>
                                <div class="aj-metric__text">
                                    <strong>{{ number_format($journal->articles_count) }}</strong>
                                    <span>Articles</span>
                                </div>
                            </div>
                            <div class="aj-metric">
                                <div class="aj-metric__icon" aria-hidden="true">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5l8 4-8 4-8-4 8-4z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 12.5l8 4 8-4"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 16.5l8 4 8-4"/></svg>
                                </div>
                                <div class="aj-metric__text">
                                    <strong>{{ number_format($journal->volumes_count) }}</strong>
                                    <span>Volumes</span>
                                </div>
                            </div>
                            <div class="aj-metric">
                                <div class="aj-metric__icon" aria-hidden="true">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M12 12a3.5 3.5 0 100-7 3.5 3.5 0 000 7z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4.2 19a7.8 7.8 0 0115.6 0"/></svg>
                                </div>
                                <div class="aj-metric__text">
                                    <strong>{{ number_format($journal->submissions_count) }}</strong>
                                    <span>Submissions</span>
                                </div>
                            </div>
                        </div>
    </div>

                    <div class="aj-card__actions">
                        <a href="{{ route('journals.show', $journal) }}" target="_blank" rel="noopener" class="admin-chip">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-6.5 9.5-6.5S21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="2.5"/></svg>
                            Preview
                        </a>
                        <a href="{{ route('admin.volumes.index', $journal) }}" class="admin-btn admin-btn-secondary">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5l8 4-8 4-8-4 8-4z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 12.5l8 4 8-4"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 16.5l8 4 8-4"/></svg>
                            Volumes
                        </a>
                        <a href="{{ route('admin.journals.edit', $journal) }}" class="admin-btn admin-btn-primary">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.8 8.2l1 1-5.3 5.3H9.5v-1.5l5.3-5.3z"/><path stroke-linecap="round" d="M5 19h14"/></svg>
                            Edit
                        </a>
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    <div class="aj-footer">
        <div>{{ $journals->links() }}</div>
        <form method="GET" action="{{ route('admin.journals.index') }}" class="aj-perpage">
            @if($q)<input type="hidden" name="q" value="{{ $q }}">@endif
            @if($currentStatus)<input type="hidden" name="status" value="{{ $currentStatus }}">@endif
            <label for="per_page">Show</label>
            <select id="per_page" name="per_page" onchange="this.form.submit()">
                @foreach([6, 12, 24] as $n)
                    <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                @endforeach
            </select>
            <span>per page</span>
        </form>
    </div>
@endif
</div>

    <div
        class="aj-modal"
        x-show="deleteOpen"
        x-cloak
        x-transition.opacity.duration.150ms
        role="dialog"
        aria-modal="true"
        aria-labelledby="aj-delete-title"
    >
        <div class="aj-modal__backdrop" @click="closeDelete()"></div>
        <div class="aj-modal__panel" @click.stop x-transition.scale.duration.150ms>
            <div class="aj-modal__head">
                <p class="aj-modal__eyebrow">Delete journal</p>
                <h2 class="aj-modal__title" id="aj-delete-title" x-text="'Delete “' + deleteTitle + '”?'"></h2>
            </div>
            <div class="aj-modal__body">
                <p class="aj-modal__text">
                    This permanently removes volumes, issues, articles, submissions, and branding files. This cannot be undone.
                </p>
                <form
                    x-ref="deleteForm"
                    method="POST"
                    :action="deleteAction"
                    @submit.prevent="submitDelete()"
                >
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="confirm" :value="deleteConfirm">
                    <label class="aj-modal__label" for="aj-delete-confirm">
                        Type <code x-text="deleteSlug"></code> to confirm
                    </label>
                    <input
                        id="aj-delete-confirm"
                        class="aj-modal__input"
                        type="text"
                        x-ref="deleteInput"
                        x-model="deleteConfirm"
                        @input="deleteMismatch = false"
                        autocomplete="off"
                        spellcheck="false"
                        placeholder="journal-slug"
                    >
                    <p class="aj-modal__error" x-show="deleteMismatch" x-cloak>Slug did not match.</p>
                    <div class="aj-modal__actions">
                        <button type="button" class="admin-btn admin-btn-secondary" @click="closeDelete()">Cancel</button>
                        <button
                            type="submit"
                            class="admin-btn aj-modal__danger"
                            :disabled="deleteConfirm !== deleteSlug"
                        >
                            Delete permanently
                        </button>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
