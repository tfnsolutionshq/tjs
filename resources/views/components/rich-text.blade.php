@props([
    'name',
    'id' => null,
    'value' => '',
    'placeholder' => 'Write here…',
    'rows' => 6,
    'minHeight' => null,
])

@php
    $id = $id ?: $name;
    $height = $minHeight ?: max(6, (int) $rows) * 1.35.'rem';
    $initial = old($name, $value ?? '');
@endphp

<div
    {{ $attributes->class('tjs-rt') }}
    x-data="tjsRichText({
        value: @js($initial),
        placeholder: @js($placeholder),
        minHeight: @js($height),
    })"
>
    <div class="tjs-rt__toolbar" role="toolbar" aria-label="Text formatting">
        <button type="button" class="tjs-rt__btn" title="Bold" @mousedown.prevent="cmd('bold')"><strong>B</strong></button>
        <button type="button" class="tjs-rt__btn" title="Italic" @mousedown.prevent="cmd('italic')"><em>I</em></button>
        <button type="button" class="tjs-rt__btn" title="Underline" @mousedown.prevent="cmd('underline')"><span style="text-decoration:underline">U</span></button>
        <span class="tjs-rt__sep" aria-hidden="true"></span>
        <button type="button" class="tjs-rt__btn" title="Bullet list" @mousedown.prevent="cmd('insertUnorderedList')">• List</button>
        <button type="button" class="tjs-rt__btn" title="Numbered list" @mousedown.prevent="cmd('insertOrderedList')">1. List</button>
        <span class="tjs-rt__sep" aria-hidden="true"></span>
        <button type="button" class="tjs-rt__btn" title="Heading" @mousedown.prevent="cmd('formatBlock', 'h3')">H</button>
        <button type="button" class="tjs-rt__btn" title="Quote" @mousedown.prevent="cmd('formatBlock', 'blockquote')">“”</button>
        <button type="button" class="tjs-rt__btn" title="Link" @mousedown.prevent="link()">Link</button>
        <button type="button" class="tjs-rt__btn" title="Clear formatting" @mousedown.prevent="cmd('removeFormat')">Clear</button>
    </div>

    <div
        x-ref="editor"
        class="tjs-rt__editor"
        contenteditable="true"
        role="textbox"
        aria-multiline="true"
        :aria-placeholder="placeholder"
        :data-empty="!value"
        :style="'min-height:' + minHeight"
        @input="sync()"
        @blur="focused = false; sync()"
        @focus="focused = true"
        @paste="onPaste($event)"
    ></div>

    <textarea
        x-ref="input"
        id="{{ $id }}"
        name="{{ $name }}"
        class="tjs-rt__input"
        tabindex="-1"
        aria-hidden="true"
    >{{ $initial }}</textarea>
</div>
