@props(['text'])

@if(filled($text))
<span
    class="tjs-field-helper"
    x-data="{ open: false }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
>
    <button
        type="button"
        class="tjs-field-helper__btn"
        @click="open = !open"
        :aria-expanded="open"
        aria-label="What does this field mean?"
    >?</button>
    <div
        class="tjs-field-helper__pop"
        x-show="open"
        x-cloak
        x-transition:enter="tjs-field-helper__pop-enter"
        x-transition:enter-start="tjs-field-helper__pop-enter-start"
        x-transition:enter-end="tjs-field-helper__pop-enter-end"
        role="tooltip"
    >{{ $text }}</div>
</span>
@endif
