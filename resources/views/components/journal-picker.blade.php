@props([
    'journals',
    'name' => 'journal_id',
    'value' => '',
    'inputId' => null,
    'required' => false,
    'placeholder' => 'Select journal…',
    'allowEmpty' => false,
    'emptyLabel' => 'All journals',
    'submitOnChange' => false,
    'class' => '',
])

@php
    $inputId = $inputId ?? $name;
    $options = collect($journals)->map(fn ($journal) => [
        'id' => (string) $journal->id,
        'title' => (string) $journal->title,
        'meta' => trim((string) ($journal->subtitle ?? $journal->issn ?? '')),
    ])->values()->all();
@endphp

<div
    {{ $attributes->class(['tjs-journal-picker', $class]) }}
    x-data="journalPicker({
        options: @js($options),
        value: @js((string) $value),
        allowEmpty: @js((bool) $allowEmpty),
        emptyLabel: @js($emptyLabel),
        placeholder: @js($placeholder),
        submitOnChange: @js((bool) $submitOnChange),
    })"
    @keydown.escape.window="open = false"
>
    <input type="hidden" name="{{ $name }}" id="{{ $inputId }}" x-ref="hidden" :value="selectedId" @if($required) required @endif>

    <button type="button" class="tjs-journal-picker__trigger" @click="open = !open" :aria-expanded="open">
        <span class="tjs-journal-picker__label" :class="!selectedTitle && 'is-empty'" x-text="selectedTitle || @js($placeholder)"></span>
        <svg class="tjs-journal-picker__chevron" :class="open && 'is-open'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
        </svg>
    </button>

    <div class="tjs-journal-picker__menu" x-show="open" x-cloak @click.outside="open = false">
        <div class="tjs-journal-picker__search">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M20 20l-3.5-3.5"/>
            </svg>
            <input type="search" x-model="q" placeholder="Search journals…" @keydown.arrow-down.prevent="highlightNext()" @keydown.arrow-up.prevent="highlightPrev()" @keydown.enter.prevent="pickHighlighted()">
        </div>
        <div class="tjs-journal-picker__list" role="listbox">
            <template x-if="allowEmpty">
                <button type="button" class="tjs-journal-picker__option" :class="selectedId === '' && 'is-selected'" @click="select('')" x-text="emptyLabel"></button>
            </template>
            <template x-for="(option, index) in filtered" :key="option.id">
                <button
                    type="button"
                    class="tjs-journal-picker__option"
                    :class="[selectedId === option.id && 'is-selected', hot === index && 'is-hot']"
                    @click="select(option.id)"
                    @mouseenter="hot = index"
                >
                    <span class="tjs-journal-picker__title" x-text="option.title"></span>
                    <span class="tjs-journal-picker__meta" x-show="option.meta" x-text="option.meta"></span>
                </button>
            </template>
            <p class="tjs-journal-picker__empty" x-show="filtered.length === 0" x-cloak>No journals match your search.</p>
        </div>
    </div>
</div>

@once
<style>
    .tjs-journal-picker { position: relative; min-width: 0; z-index: 1; }
    .tjs-journal-picker:focus-within,
    .tjs-journal-picker:has(.tjs-journal-picker__menu:not([style*='display: none'])) { z-index: 40; }
    .tjs-journal-picker__trigger {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .55rem;
        border: 1px solid #e2e8f0;
        background: #fff;
        border-radius: .7rem;
        padding: .68rem .8rem;
        font: inherit;
        color: var(--ink, #0f172a);
        cursor: pointer;
        text-align: left;
    }
    .tjs-journal-picker__trigger:hover { border-color: #cbd5e1; }
    .tjs-journal-picker__trigger:focus { outline: none; border-color: #93c5fd; box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
    .tjs-journal-picker__label {
        font-size: .88rem;
        font-weight: 650;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .tjs-journal-picker__label.is-empty { color: #94a3b8; font-weight: 600; }
    .tjs-journal-picker__chevron {
        width: 1rem;
        height: 1rem;
        color: #94a3b8;
        flex-shrink: 0;
        transition: transform .15s ease;
    }
    .tjs-journal-picker__chevron.is-open { transform: rotate(180deg); }
    .tjs-journal-picker__menu {
        position: absolute;
        z-index: 50;
        left: 0;
        top: calc(100% + .35rem);
        width: max(100%, 18rem);
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: .8rem;
        box-shadow: 0 16px 36px rgba(15,23,42,.12);
        overflow: hidden;
    }
    .tjs-journal-picker__search {
        display: flex;
        align-items: center;
        gap: .45rem;
        padding: .6rem .7rem;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
    }
    .tjs-journal-picker__search svg { width: .9rem; height: .9rem; color: #64748b; flex-shrink: 0; }
    .tjs-journal-picker__search input {
        width: 100%;
        border: 0;
        background: transparent;
        font: inherit;
        font-size: .84rem;
        outline: none;
    }
    .tjs-journal-picker__list {
        max-height: 16rem;
        overflow: auto;
        padding: .35rem;
    }
    .tjs-journal-picker__option {
        width: 100%;
        display: grid;
        gap: .1rem;
        text-align: left;
        border: 0;
        background: transparent;
        border-radius: .55rem;
        padding: .55rem .65rem;
        cursor: pointer;
        font: inherit;
    }
    .tjs-journal-picker__option:hover,
    .tjs-journal-picker__option.is-hot { background: #f8fafc; }
    .tjs-journal-picker__option.is-selected {
        background: #eff6ff;
        box-shadow: inset 0 0 0 1px #bfdbfe;
    }
    .tjs-journal-picker__title {
        font-size: .84rem;
        font-weight: 700;
        color: #0f172a;
    }
    .tjs-journal-picker__meta {
        font-size: .72rem;
        color: #64748b;
    }
    .tjs-journal-picker__empty {
        margin: 0;
        padding: .75rem .65rem;
        font-size: .78rem;
        color: #64748b;
    }
</style>
<script>
function journalPicker(cfg) {
    return {
        open: false,
        q: '',
        hot: 0,
        options: cfg.options || [],
        allowEmpty: !!cfg.allowEmpty,
        emptyLabel: cfg.emptyLabel || 'All journals',
        placeholder: cfg.placeholder || 'Select journal…',
        submitOnChange: !!cfg.submitOnChange,
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
            if (this.selectedId === '') {
                return this.allowEmpty ? this.emptyLabel : '';
            }
            const hit = this.options.find((option) => option.id === this.selectedId);
            return hit ? hit.title : '';
        },
        init() {
            this.$el.addEventListener('journal-picker-set', (event) => {
                this.selectedId = String(event.detail ?? '');
            });
        },
        select(id) {
            this.selectedId = id;
            this.open = false;
            this.q = '';
            this.hot = 0;

            const hidden = this.$refs.hidden;

            if (hidden) {
                hidden.value = id;
                hidden.dispatchEvent(new Event('change', { bubbles: true }));
            }

            this.$dispatch('picker-change', id);

            if (this.submitOnChange) {
                const form = this.$el.closest('form');

                if (form) {
                    form.requestSubmit();
                }
            }
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
    };
}
</script>
@endonce
