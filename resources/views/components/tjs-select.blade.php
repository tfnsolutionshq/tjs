@props([
    'name' => null,
    'value' => '',
    'options' => [],
    'inputId' => null,
    'placeholder' => 'Select…',
    'variant' => 'default',
    'submitOnChange' => false,
    'class' => '',
])

@php
    $inputId = $inputId ?? $name;
    $normalizedOptions = collect($options)->map(function ($option) {
        if (is_array($option)) {
            return array_filter([
                'value' => (string) ($option['value'] ?? ''),
                'label' => (string) ($option['label'] ?? ''),
                'hint' => isset($option['hint']) ? (string) $option['hint'] : null,
                'icon' => $option['icon'] ?? null,
            ], fn ($value) => $value !== null);
        }

        return [
            'value' => (string) $option,
            'label' => (string) $option,
        ];
    })->values()->all();
@endphp

<div
    {{ $attributes->class(['tjs-select', "tjs-select--{$variant}", $class]) }}
    x-data="tjsSelect({
        options: @js($normalizedOptions),
        value: @js((string) $value),
        placeholder: @js($placeholder),
        submitOnChange: @js((bool) $submitOnChange),
        variant: @js($variant),
    })"
    @keydown.escape.window="open = false"
>
  @if($name)
      <input type="hidden" name="{{ $name }}" id="{{ $inputId }}" x-ref="hidden" :value="selectedValue">
  @else
      <input type="hidden" x-ref="hidden" :value="selectedValue">
  @endif

    <button
        type="button"
        class="tjs-select__trigger"
        @click="toggle()"
        :aria-expanded="open"
        @keydown.arrow-down.prevent="open = true; highlightNext()"
        @keydown.arrow-up.prevent="open = true; highlightPrev()"
        @keydown.enter.prevent="open ? pickHighlighted() : toggle()"
        @if($inputId) id="{{ $inputId }}_trigger" @endif
    >
        <span class="tjs-select__trigger-main">
            <span class="tjs-select__trigger-icon" x-show="selectedIcon" x-html="selectedIcon"></span>
            <span class="tjs-select__trigger-text">
                <span class="tjs-select__label" x-text="selectedLabel"></span>
                <span class="tjs-select__trigger-hint" x-show="selectedHint && variant === 'rich'" x-text="selectedHint"></span>
            </span>
        </span>
        <svg class="tjs-select__chevron" :class="open && 'is-open'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
        </svg>
    </button>

    <div
        class="tjs-select__menu"
        x-show="open"
        x-cloak
        @click.outside="open = false"
        x-transition:enter="tjs-select-enter"
        x-transition:enter-start="tjs-select-enter-from"
        x-transition:enter-end="tjs-select-enter-to"
        x-transition:leave="tjs-select-leave"
        x-transition:leave-start="tjs-select-leave-from"
        x-transition:leave-end="tjs-select-leave-to"
    >
        <div class="tjs-select__list" role="listbox">
            <template x-for="(option, index) in options" :key="option.value + '-' + index">
                <button
                    type="button"
                    class="tjs-select__option"
                    :class="[selectedValue === option.value && 'is-selected', hot === index && 'is-hot']"
                    @click="select(option.value)"
                    @mouseenter="hot = index"
                >
                    <span class="tjs-select__option-main">
                        <span class="tjs-select__option-icon" x-show="option.icon" x-html="option.icon"></span>
                        <span class="tjs-select__option-text">
                            <span class="tjs-select__option-label" x-text="option.label"></span>
                            <span class="tjs-select__option-hint" x-show="option.hint" x-text="option.hint"></span>
                        </span>
                    </span>
                    <svg class="tjs-select__check" x-show="selectedValue === option.value" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                </button>
            </template>
        </div>
    </div>
</div>
