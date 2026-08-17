@props([
    'calls',
    'name' => 'announcement_id',
    'value' => '',
    'inputId' => null,
    'required' => false,
    'placeholder' => 'Select an open call for submissions…',
    'class' => '',
])

@php
    $inputId = $inputId ?? $name;
    $options = collect($calls)->map(fn ($call) => [
        'id' => (string) $call->id,
        'title' => (string) $call->title,
        'meta' => trim(collect([
            $call->journal?->title,
            $call->issueLabel(),
            $call->closes_at ? 'Closes '.$call->closes_at->format('M j, Y') : null,
        ])->filter()->join(' · ')),
        'journal_id' => (string) $call->journal_id,
        'review_type' => (string) ($call->journal?->review_type ?? 'closed'),
    ])->values()->all();
@endphp

<div
    {{ $attributes->class(['tjs-call-picker', $class]) }}
    x-data="callPicker({
        options: @js($options),
        value: @js((string) $value),
        placeholder: @js($placeholder),
    })"
    @keydown.escape.window="open = false"
>
    <input type="hidden" name="{{ $name }}" id="{{ $inputId }}" :value="selectedId" @if($required) required @endif>

    <button type="button" class="tjs-call-picker__trigger" @click="open = !open" :aria-expanded="open">
        <span class="tjs-call-picker__label" :class="!selectedTitle && 'is-empty'" x-text="selectedTitle || @js($placeholder)"></span>
        <svg class="tjs-call-picker__chevron" :class="open && 'is-open'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
        </svg>
    </button>

    <div class="tjs-call-picker__menu" x-show="open" x-cloak @click.outside="open = false">
        <div class="tjs-call-picker__search">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M20 20l-3.5-3.5"/>
            </svg>
            <input type="search" x-model="q" placeholder="Search calls, journals, issues…" @keydown.arrow-down.prevent="highlightNext()" @keydown.arrow-up.prevent="highlightPrev()" @keydown.enter.prevent="pickHighlighted()">
        </div>
        <div class="tjs-call-picker__list" role="listbox">
            <template x-for="(option, index) in filtered" :key="option.id">
                <button
                    type="button"
                    class="tjs-call-picker__option"
                    :class="[selectedId === option.id && 'is-selected', hot === index && 'is-hot']"
                    @click="select(option.id)"
                    @mouseenter="hot = index"
                >
                    <span class="tjs-call-picker__title" x-text="option.title"></span>
                    <span class="tjs-call-picker__meta" x-show="option.meta" x-text="option.meta"></span>
                </button>
            </template>
            <p class="tjs-call-picker__empty" x-show="filtered.length === 0" x-cloak>No open calls match your search.</p>
        </div>
    </div>
</div>

@once
<style>
    .tjs-call-picker { position: relative; min-width: 0; z-index: 1; }
    .tjs-call-picker:focus-within { z-index: 40; }
    .tjs-call-picker__trigger {
        width: 100%; display: flex; align-items: center; justify-content: space-between; gap: .55rem;
        border: 1px solid #e2e8f0; background: #fff; border-radius: .7rem; padding: .68rem .8rem;
        font: inherit; color: var(--ink, #0f172a); cursor: pointer; text-align: left;
    }
    .tjs-call-picker__trigger:hover { border-color: #cbd5e1; }
    .tjs-call-picker__label { font-size: .88rem; font-weight: 650; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tjs-call-picker__label.is-empty { color: #94a3b8; font-weight: 600; }
    .tjs-call-picker__chevron { width: 1rem; height: 1rem; color: #94a3b8; flex-shrink: 0; transition: transform .15s ease; }
    .tjs-call-picker__chevron.is-open { transform: rotate(180deg); }
    .tjs-call-picker__menu {
        position: absolute; z-index: 50; left: 0; top: calc(100% + .35rem); width: max(100%, 20rem);
        background: #fff; border: 1px solid #e2e8f0; border-radius: .8rem;
        box-shadow: 0 16px 36px rgba(15,23,42,.12); overflow: hidden;
    }
    .tjs-call-picker__search {
        display: flex; align-items: center; gap: .45rem; padding: .6rem .7rem;
        border-bottom: 1px solid #e2e8f0; background: #f8fafc;
    }
    .tjs-call-picker__search svg { width: .9rem; height: .9rem; color: #64748b; flex-shrink: 0; }
    .tjs-call-picker__search input { width: 100%; border: 0; background: transparent; font: inherit; font-size: .84rem; outline: none; }
    .tjs-call-picker__list { max-height: 16rem; overflow: auto; padding: .35rem; }
    .tjs-call-picker__option {
        width: 100%; display: grid; gap: .1rem; text-align: left; border: 0; background: transparent;
        border-radius: .55rem; padding: .55rem .65rem; cursor: pointer; font: inherit;
    }
    .tjs-call-picker__option:hover, .tjs-call-picker__option.is-hot { background: #f8fafc; }
    .tjs-call-picker__option.is-selected { background: #eff6ff; box-shadow: inset 0 0 0 1px #bfdbfe; }
    .tjs-call-picker__title { font-size: .84rem; font-weight: 700; color: #0f172a; }
    .tjs-call-picker__meta { font-size: .72rem; color: #64748b; }
    .tjs-call-picker__empty { margin: 0; padding: .75rem .65rem; font-size: .78rem; color: #64748b; }
</style>
<script>
function callPicker(cfg) {
    return {
        open: false,
        q: '',
        hot: 0,
        options: cfg.options || [],
        placeholder: cfg.placeholder || 'Select an open call for submissions…',
        selectedId: cfg.value || '',
        get filtered() {
            const needle = this.q.trim().toLowerCase();
            if (!needle) return this.options;
            return this.options.filter((option) =>
                option.title.toLowerCase().includes(needle)
                || (option.meta && option.meta.toLowerCase().includes(needle))
            );
        },
        get selectedTitle() {
            const hit = this.options.find((option) => option.id === this.selectedId);
            return hit ? hit.title : '';
        },
        get selectedOption() {
            return this.options.find((option) => option.id === this.selectedId) || null;
        },
        select(id) {
            this.selectedId = id;
            this.open = false;
            this.q = '';
            this.hot = 0;
            const option = this.options.find((item) => item.id === id);
            this.$dispatch('call-selected', option || { id: '' });
        },
        pickHighlighted() {
            const option = this.filtered[this.hot];
            if (option) this.select(option.id);
        },
        highlightNext() {
            if (!this.filtered.length) return;
            this.hot = (this.hot + 1) % this.filtered.length;
        },
        highlightPrev() {
            if (!this.filtered.length) return;
            this.hot = (this.hot - 1 + this.filtered.length) % this.filtered.length;
        },
        init() {
            this.$el.addEventListener('call-picker-set', (event) => {
                this.selectedId = String(event.detail ?? '');
            });
        },
    };
}
</script>
@endonce
