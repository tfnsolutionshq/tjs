@props([
    'name' => 'language',
    'value' => 'en',
    'id' => 'language',
    'label' => 'Language',
    'useFormLabel' => true,
])

@php
    $languages = \App\Support\JournalLanguages::forPicker();
    $selected = \App\Support\JournalLanguages::find((string) $value)
        ?? ['code' => strtolower((string) $value) ?: 'en', 'name' => strtoupper((string) $value) ?: 'English', 'flag' => 'gb'];
    $selectedUrl = \App\Support\JournalLanguages::flagSvgUrl($selected['flag'] ?? 'gb');
@endphp

<div
    {{ $attributes->class(['jf-lang']) }}
    x-data="jfLanguagePicker({
        languages: @js($languages),
        value: @js(strtolower((string) $value) ?: 'en'),
        selectedName: @js($selected['name'] ?? 'English'),
        selectedFlag: @js($selectedUrl),
    })"
    @keydown.escape.window="open = false"
    @click.outside="open = false"
>
    @if($useFormLabel)
        <x-form-label for="{{ $id }}-search" field="journal.language">{{ $label }}</x-form-label>
    @else
        <label class="mj-label" for="{{ $id }}-search">{{ $label }}</label>
    @endif
    <input type="hidden" name="{{ $name }}" :value="value" id="{{ $id }}">

    <button
        type="button"
        class="jf-lang__trigger"
        @click="toggle()"
        :aria-expanded="open.toString()"
        aria-haspopup="listbox"
    >
        <img class="jf-lang__flag" :src="selectedFlag" :alt="selectedName + ' flag'" width="22" height="16" loading="lazy" decoding="async">
        <span class="jf-lang__meta">
            <span class="jf-lang__name" x-text="selectedName"></span>
            <span class="jf-lang__code" x-text="value"></span>
        </span>
        <svg class="jf-lang__chevron" :class="{ 'is-open': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/>
        </svg>
    </button>

    <div class="jf-lang__menu" x-show="open" x-cloak x-transition.opacity.duration.120ms role="listbox">
        <div class="jf-lang__search">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M20 20l-3.5-3.5"/></svg>
            <input
                id="{{ $id }}-search"
                type="search"
                x-ref="search"
                x-model="query"
                @keydown.enter.prevent="pickFirst()"
                @keydown.arrow-down.prevent="highlightNext()"
                @keydown.arrow-up.prevent="highlightPrev()"
                placeholder="Search languages…"
                autocomplete="off"
            >
        </div>
        <ul class="jf-lang__list">
            <template x-for="(lang, index) in filtered" :key="lang.code">
                <li>
                    <button
                        type="button"
                        class="jf-lang__option"
                        :class="{ 'is-active': lang.code === value, 'is-hot': index === hot }"
                        @click="select(lang)"
                        @mouseenter="hot = index"
                        role="option"
                        :aria-selected="(lang.code === value).toString()"
                    >
                        <img class="jf-lang__flag" :src="lang.flag_url" :alt="lang.name + ' flag'" width="22" height="16" loading="lazy" decoding="async">
                        <span class="jf-lang__meta">
                            <span class="jf-lang__name" x-text="lang.name"></span>
                            <span class="jf-lang__code" x-text="lang.code"></span>
                        </span>
                        <svg class="jf-lang__check" x-show="lang.code === value" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </button>
                </li>
            </template>
            <li x-show="filtered.length === 0" class="jf-lang__empty">No languages match.</li>
        </ul>
    </div>
</div>

@once
    <style>
        .jf-lang { position: relative; z-index: 1; }
        .jf-lang:focus-within { z-index: 40; }
        .jf-lang > label,
        .jf-lang > .tjs-label-row {
            display: block; margin-bottom: .4rem;
            font-size: .78rem; font-weight: 700; color: #334155; letter-spacing: .01em;
        }
        .jf-lang .mj-label { margin-bottom: .4rem; }
        .jf-lang__trigger {
            width: 100%; display: flex; align-items: center; gap: .7rem;
            border: 1px solid #d8dee8; background: #fff; border-radius: .7rem;
            padding: .72rem .85rem; cursor: pointer; text-align: left;
            font: inherit; color: var(--ink, #0f172a);
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .jf-lang__trigger:hover { border-color: #cbd5e1; }
        .jf-lang__trigger:focus {
            outline: none; border-color: var(--blue, #2f7de1);
            box-shadow: 0 0 0 3px rgba(47,125,225,.14);
        }
        .jf-lang__flag {
            width: 1.5rem; height: 1.1rem; object-fit: cover;
            border-radius: .22rem; border: 1px solid rgba(15,23,42,.1);
            flex-shrink: 0; background: #f1f5f9;
            box-shadow: 0 1px 2px rgba(15,23,42,.06);
        }
        .jf-lang__meta {
            display: flex; align-items: baseline; gap: .45rem; min-width: 0; flex: 1;
        }
        .jf-lang__name {
            font-size: .9rem; font-weight: 650; color: var(--ink, #0f172a);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .jf-lang__code {
            font-size: .72rem; font-weight: 700; letter-spacing: .04em;
            text-transform: uppercase; color: var(--muted, #64748b); flex-shrink: 0;
        }
        .jf-lang__chevron {
            width: 1rem; height: 1rem; color: #94a3b8; flex-shrink: 0;
            transition: transform .15s ease;
        }
        .jf-lang__chevron.is-open { transform: rotate(180deg); }
        .jf-lang__menu {
            position: absolute; z-index: 50; left: 0; right: 0; top: calc(100% + .4rem);
            background: #fff; border: 1px solid var(--line, #e8edf3); border-radius: .85rem;
            box-shadow: 0 18px 40px rgba(15,23,42,.12);
            overflow: hidden;
        }
        .jf-lang__search {
            display: flex; align-items: center; gap: .5rem;
            padding: .65rem .75rem; border-bottom: 1px solid var(--line, #e8edf3); background: #f8fafc;
        }
        .jf-lang__search svg { width: .95rem; height: .95rem; color: var(--muted, #64748b); flex-shrink: 0; }
        .jf-lang__search input {
            width: 100%; border: 0; outline: 0; background: transparent;
            font: inherit; font-size: .86rem; color: var(--ink, #0f172a);
        }
        .jf-lang__list {
            list-style: none; margin: 0; padding: .35rem;
            max-height: 16rem; overflow: auto;
        }
        .jf-lang__option {
            width: 100%; display: flex; align-items: center; gap: .7rem;
            border: 0; background: transparent; border-radius: .6rem;
            padding: .55rem .6rem; cursor: pointer; text-align: left; font: inherit;
        }
        .jf-lang__option:hover,
        .jf-lang__option.is-hot { background: #f1f5f9; }
        .jf-lang__option.is-active { background: #eff6ff; }
        .jf-lang__check { width: .95rem; height: .95rem; color: #2563eb; flex-shrink: 0; margin-left: auto; }
        .jf-lang__empty {
            padding: .85rem .7rem; font-size: .8rem; color: var(--muted, #64748b); text-align: center;
        }
    </style>

    <script>
        window.jfLanguagePicker = window.jfLanguagePicker || function (cfg) {
            return {
                languages: cfg.languages || [],
                value: cfg.value || 'en',
                selectedName: cfg.selectedName || 'English',
                selectedFlag: cfg.selectedFlag || '',
                open: false,
                query: '',
                hot: 0,
                get filtered() {
                    const q = this.query.trim().toLowerCase();
                    if (! q) return this.languages;
                    return this.languages.filter((lang) =>
                        lang.name.toLowerCase().includes(q) || lang.code.toLowerCase().includes(q)
                    );
                },
                toggle() {
                    this.open = ! this.open;
                    if (this.open) {
                        this.query = '';
                        this.hot = Math.max(0, this.filtered.findIndex((l) => l.code === this.value));
                        this.$nextTick(() => this.$refs.search?.focus());
                    }
                },
                select(lang) {
                    this.value = lang.code;
                    this.selectedName = lang.name;
                    this.selectedFlag = lang.flag_url;
                    this.open = false;
                    this.query = '';
                },
                pickFirst() {
                    const first = this.filtered[this.hot] || this.filtered[0];
                    if (first) this.select(first);
                },
                highlightNext() {
                    if (! this.filtered.length) return;
                    this.hot = Math.min(this.hot + 1, this.filtered.length - 1);
                    this.scrollHot();
                },
                highlightPrev() {
                    if (! this.filtered.length) return;
                    this.hot = Math.max(this.hot - 1, 0);
                    this.scrollHot();
                },
                scrollHot() {
                    this.$nextTick(() => {
                        const el = this.$root.querySelectorAll('.jf-lang__option')[this.hot];
                        el?.scrollIntoView({ block: 'nearest' });
                    });
                },
            };
        };
    </script>
@endonce
