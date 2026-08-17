@props([
    'name' => 'language',
    'value' => 'en',
    'id' => 'language',
    'label' => 'Language',
])

@php
    $languages = \App\Support\JournalLanguages::forPicker();
    $selected = \App\Support\JournalLanguages::find((string) $value)
        ?? ['code' => strtolower((string) $value) ?: 'en', 'name' => strtoupper((string) $value) ?: 'English', 'flag' => 'gb'];
    $selectedUrl = \App\Support\JournalLanguages::flagSvgUrl($selected['flag'] ?? 'gb');
@endphp

<div
    class="jf-lang"
    x-data="jfLanguagePicker({
        languages: @js($languages),
        value: @js(strtolower((string) $value) ?: 'en'),
        selectedName: @js($selected['name'] ?? 'English'),
        selectedFlag: @js($selectedUrl),
    })"
    @keydown.escape.window="open = false"
    @click.outside="open = false"
>
    <x-form-label for="{{ $id }}-search" field="journal.language">{{ $label }}</x-form-label>
    <input type="hidden" name="{{ $name }}" :value="value" id="{{ $id }}">

    <button
        type="button"
        class="jf-lang__trigger"
        @click="toggle()"
        :aria-expanded="open.toString()"
        aria-haspopup="listbox"
    >
        <img class="jf-lang__flag" :src="selectedFlag" :alt="selectedName + ' flag'" width="22" height="16" loading="lazy">
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
                        <img class="jf-lang__flag" :src="lang.flag_url" :alt="lang.name + ' flag'" width="22" height="16" loading="lazy">
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
