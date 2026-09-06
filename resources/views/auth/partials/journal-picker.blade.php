@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Journal> $featuredJournals */
    $action = $pickerAction ?? ($action ?? 'login');
    $redirect = $redirect ?? null;
    $otherJournalsCount = $otherJournalsCount ?? 0;
    $totalJournalsCount = $totalJournalsCount ?? $featuredJournals->count() + $otherJournalsCount;
    $emptyCtaRoute = $emptyCtaRoute ?? route('login');
    $emptyCtaLabel = $emptyCtaLabel ?? 'Platform login';
    $platformRoute = $platformRoute ?? route('login', array_filter(['redirect' => $redirect]));
    $platformPrompt = $platformPrompt ?? 'Prefer the platform?';
    $platformLinkLabel = $platformLinkLabel ?? 'Log in here';

    $picker = app(\App\Services\Journal\JournalPickerService::class);
    $featuredItems = $featuredJournals->map(
        fn ($journal) => $picker->toPickerItem($journal, $action, $redirect)
    )->values();
@endphp

@if($totalJournalsCount === 0)
    <div class="jp-empty-state">
        <p class="auth-muted text-sm">{{ $emptyMessage ?? 'No journals are available right now. Please check back later.' }}</p>
        <a href="{{ $emptyCtaRoute }}" class="auth-btn auth-btn-secondary mt-4">{{ $emptyCtaLabel }}</a>
    </div>
@else
    <div
        class="jp"
        x-data="journalPicker({
            featured: @js($featuredItems),
            otherCount: @js($otherJournalsCount),
            total: @js($totalJournalsCount),
            action: @js($action),
            redirect: @js($redirect),
            searchUrl: @js(route('journals.picker')),
        })"
        @keydown.escape.window="if (query) { query = ''; mode = defaultMode(); $refs.search?.focus() }"
    >
        <div class="jp-toolbar">
            <label class="jp-search" for="journal-picker-search">
                <svg class="jp-search__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M20 20l-3.5-3.5"/>
                </svg>
                <input
                    id="journal-picker-search"
                    type="search"
                    class="jp-search__input"
                    placeholder="Search journals…"
                    autocomplete="off"
                    spellcheck="false"
                    x-model="query"
                    x-ref="search"
                    @input.debounce.300ms="onSearchInput()"
                    @keydown.arrow-down.prevent="focusNext()"
                    @keydown.arrow-up.prevent="focusPrev()"
                    @keydown.enter.prevent="openFocused()"
                    autofocus
                >
                <button
                    type="button"
                    class="jp-search__clear"
                    x-show="query.length"
                    x-cloak
                    @click="clearSearch()"
                    aria-label="Clear search"
                >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </label>
            <p class="jp-meta" aria-live="polite">
                <span x-text="metaLabel()"></span>
            </p>
        </div>

        <div class="jp-browse" x-show="!query.trim() && otherCount > 0 && browseMode === 'featured'" x-cloak>
            <button type="button" class="jp-browse__btn" @click="openBrowseAll()">
                Browse all journals
                <span x-text="'(' + otherCount + ' more)'"></span>
            </button>
        </div>

        <div class="jp-browse" x-show="!query.trim() && browseMode === 'other'" x-cloak>
            <button type="button" class="jp-browse__btn jp-browse__btn--ghost" @click="showFeaturedOnly()">
                ← Back to featured journals
            </button>
        </div>

        <div class="jp-list" role="listbox" aria-label="Journals" tabindex="-1" x-ref="list">
            <template x-if="loading">
                <div class="jp-no-results"><p>Loading journals…</p></div>
            </template>

            <template x-if="!loading && visibleItems.length === 0">
                <div class="jp-no-results">
                    <p x-show="query.trim()">No journals match “<span x-text="query.trim()"></span>”.</p>
                    <p x-show="!query.trim()">No journals to show.</p>
                    <button type="button" class="auth-link" @click="clearSearch()" x-show="query.trim()">Clear search</button>
                </div>
            </template>

            <template x-for="(group, gi) in grouped" :key="group.key">
                <div class="jp-group" x-show="!loading">
                    <div class="jp-group__label" x-show="group.label" x-text="group.label" x-cloak></div>
                    <template x-for="(item, ii) in group.items" :key="item.slug">
                        <a
                            :href="item.href"
                            class="jp-item"
                            role="option"
                            :class="{ 'is-focused': isFocused(item.slug) }"
                            :data-slug="item.slug"
                            @mouseenter="focusedSlug = item.slug"
                            @focus="focusedSlug = item.slug"
                        >
                            <template x-if="item.logo">
                                <img :src="item.logo" alt="" class="jp-item__logo">
                            </template>
                            <template x-if="!item.logo">
                                <span class="jp-item__initials" x-text="item.initials.slice(0, 3)" aria-hidden="true"></span>
                            </template>
                            <span class="jp-item__text min-w-0 flex-1">
                                <span class="jp-item__title">
                                    <span x-text="item.title"></span>
                                    <span class="jp-item__badge" x-show="item.featured" x-cloak>Featured</span>
                                </span>
                                <span class="jp-item__sub" x-show="item.subtitle" x-text="item.subtitle" x-cloak></span>
                                <span class="jp-item__slug" x-text="'/j/' + item.slug"></span>
                            </span>
                            <svg class="jp-item__chevron" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </template>
                </div>
            </template>
        </div>

        <div class="jp-pagination" x-show="!loading && (browseMode === 'other' || query.trim()) && lastPage > 1" x-cloak>
            <button type="button" class="jp-browse__btn jp-browse__btn--ghost" :disabled="page <= 1" @click="goToPage(page - 1)">Previous</button>
            <span class="jp-meta" x-text="'Page ' + page + ' of ' + lastPage"></span>
            <button type="button" class="jp-browse__btn jp-browse__btn--ghost" :disabled="page >= lastPage" @click="goToPage(page + 1)">Next</button>
        </div>
    </div>
@endif

<p class="auth-footer auth-footer--compact">
    <span class="auth-footer__alt" style="display:block;margin:0">
        {{ $platformPrompt }}
        <a href="{{ $platformRoute }}">{{ $platformLinkLabel }}</a>
    </span>
</p>

<script>
    document.addEventListener('alpine:init', () => {
        if (window.__journalPickerRegistered) return;
        window.__journalPickerRegistered = true;

        Alpine.data('journalPicker', (cfg) => ({
            featured: cfg.featured || [],
            otherCount: cfg.otherCount || 0,
            total: cfg.total || 0,
            action: cfg.action || 'login',
            redirect: cfg.redirect || null,
            searchUrl: cfg.searchUrl,
            query: '',
            browseMode: (cfg.otherCount || 0) > 0 ? 'featured' : 'all',
            remoteItems: [],
            page: 1,
            lastPage: 1,
            loading: false,
            focusedSlug: '',
            get visibleItems() {
                if (this.query.trim() || this.browseMode === 'other') {
                    return this.remoteItems;
                }
                if (this.browseMode === 'featured') {
                    return this.featured;
                }
                return this.remoteItems.length ? this.remoteItems : this.featured;
            },
            get grouped() {
                const list = this.visibleItems;
                if (! list.length) return [];

                if (this.query.trim()) {
                    return [{ key: 'results', label: 'Search results', items: list }];
                }

                if (this.browseMode === 'featured') {
                    return [{ key: 'featured', label: 'Featured journals', items: list }];
                }

                if (this.browseMode === 'other') {
                    return [{ key: 'other', label: 'All journals', items: list }];
                }

                const featured = list.filter((i) => i.featured);
                const rest = list.filter((i) => ! i.featured);
                const groups = [];
                if (featured.length) {
                    groups.push({ key: 'featured', label: 'Featured', items: featured });
                }
                let current = null;
                rest.forEach((item) => {
                    const letter = /^[A-Z]$/.test(item.letter) ? item.letter : '#';
                    if (! current || current.key !== letter) {
                        current = { key: letter, label: letter, items: [] };
                        groups.push(current);
                    }
                    current.items.push(item);
                });
                return groups;
            },
            defaultMode() {
                return this.otherCount > 0 ? 'featured' : 'all';
            },
            metaLabel() {
                if (this.query.trim()) {
                    const count = this.visibleItems.length;
                    return count === 1 ? '1 match' : `${count} matches`;
                }
                if (this.browseMode === 'featured') {
                    const n = this.featured.length;
                    return n === 1 ? '1 featured journal' : `${n} featured journals`;
                }
                return `${this.total} journals`;
            },
            isFocused(slug) {
                return this.focusedSlug === slug;
            },
            async fetchRemote(page = 1) {
                this.loading = true;
                const params = new URLSearchParams({
                    action: this.action,
                    page: String(page),
                });
                if (this.redirect) params.set('redirect', this.redirect);
                if (this.query.trim()) {
                    params.set('q', this.query.trim());
                    params.set('mode', 'all');
                } else if (this.browseMode === 'other') {
                    params.set('mode', 'other');
                } else {
                    params.set('mode', 'all');
                }
                try {
                    const response = await fetch(`${this.searchUrl}?${params.toString()}`, {
                        headers: { Accept: 'application/json' },
                    });
                    const payload = await response.json();
                    if (! response.ok) throw new Error('fetch failed');
                    this.remoteItems = payload.data || [];
                    this.page = payload.meta?.current_page || 1;
                    this.lastPage = payload.meta?.last_page || 1;
                    this.focusedSlug = this.remoteItems[0]?.slug || '';
                } catch (error) {
                    this.remoteItems = [];
                } finally {
                    this.loading = false;
                }
            },
            onSearchInput() {
                if (! this.query.trim()) {
                    this.remoteItems = [];
                    this.page = 1;
                    this.lastPage = 1;
                    this.browseMode = this.defaultMode();
                    this.focusedSlug = this.featured[0]?.slug || '';
                    return;
                }
                this.fetchRemote(1);
            },
            openBrowseAll() {
                this.browseMode = 'other';
                this.query = '';
                this.fetchRemote(1);
            },
            showFeaturedOnly() {
                this.browseMode = 'featured';
                this.remoteItems = [];
                this.page = 1;
                this.lastPage = 1;
                this.focusedSlug = this.featured[0]?.slug || '';
            },
            clearSearch() {
                this.query = '';
                this.onSearchInput();
                this.$refs.search?.focus();
            },
            goToPage(page) {
                this.fetchRemote(page);
            },
            flatSlugs() {
                return this.visibleItems.map((i) => i.slug);
            },
            focusNext() {
                const slugs = this.flatSlugs();
                if (! slugs.length) return;
                const idx = slugs.indexOf(this.focusedSlug);
                this.focusedSlug = slugs[Math.min(idx + 1, slugs.length - 1)] || slugs[0];
                this.scrollFocused();
            },
            focusPrev() {
                const slugs = this.flatSlugs();
                if (! slugs.length) return;
                const idx = slugs.indexOf(this.focusedSlug);
                this.focusedSlug = slugs[Math.max(idx - 1, 0)] || slugs[0];
                this.scrollFocused();
            },
            openFocused() {
                const item = this.visibleItems.find((i) => i.slug === this.focusedSlug) || this.visibleItems[0];
                if (item) window.location.href = item.href;
            },
            scrollFocused() {
                this.$nextTick(() => {
                    const el = this.$refs.list?.querySelector(`[data-slug="${this.focusedSlug}"]`);
                    el?.scrollIntoView({ block: 'nearest' });
                });
            },
            init() {
                this.focusedSlug = this.featured[0]?.slug || '';
            },
        }));
    });
</script>

<style>
    .jp-browse { margin: .35rem 0 .65rem; }
    .jp-browse__btn {
        display: inline-flex; align-items: center; gap: .35rem;
        border: 1px solid #dbeafe; background: #eff6ff; color: #1d4ed8;
        border-radius: 999px; padding: .45rem .8rem; font: inherit; font-size: .78rem; font-weight: 700; cursor: pointer;
    }
    .jp-browse__btn:hover { background: #dbeafe; }
    .jp-browse__btn:disabled { opacity: .45; cursor: not-allowed; }
    .jp-browse__btn--ghost { background: #fff; border-color: #e2e8f0; color: #475569; }
    .jp-browse__btn--ghost:hover { background: #f8fafc; }
    .jp-pagination {
        display: flex; align-items: center; justify-content: space-between; gap: .75rem;
        margin-top: .75rem; padding-top: .75rem; border-top: 1px solid #e2e8f0;
    }
</style>
