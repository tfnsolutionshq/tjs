@props([
    'type' => 'success',
    'dismissible' => true,
    'timeout' => 7000,
    'title' => null,
])

@php
    $type = in_array($type, ['success', 'error', 'warning', 'info'], true) ? $type : 'info';
    $titles = [
        'success' => 'Success',
        'error' => 'Something went wrong',
        'warning' => 'Please note',
        'info' => 'Notice',
    ];
    $heading = $title ?? $titles[$type];
@endphp

<div
    x-data="{
        show: false,
        timeout: {{ (int) $timeout }},
        init() {
            this.$nextTick(() => { this.show = true })
            if (this.timeout > 0) {
                setTimeout(() => { this.dismiss() }, this.timeout)
            }
        },
        dismiss() {
            this.show = false
        }
    }"
    x-cloak
    x-show="show"
    x-transition:enter="tjs-alert-enter"
    x-transition:enter-start="tjs-alert-enter-start"
    x-transition:enter-end="tjs-alert-enter-end"
    x-transition:leave="tjs-alert-leave"
    x-transition:leave-start="tjs-alert-leave-start"
    x-transition:leave-end="tjs-alert-leave-end"
    role="alert"
    {{ $attributes->class(["tjs-alert tjs-alert--{$type}"]) }}
>
    <span class="tjs-alert__icon" aria-hidden="true">
        @if($type === 'success')
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.78-9.72a.75.75 0 00-1.06-1.06L9 11.94 7.28 10.22a.75.75 0 10-1.06 1.06l2.25 2.25a.75.75 0 001.06 0l4.25-4.25z" clip-rule="evenodd"/></svg>
        @elseif($type === 'error')
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/></svg>
        @elseif($type === 'warning')
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.168 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 6.75a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6.75zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
        @else
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/></svg>
        @endif
    </span>
    <div class="tjs-alert__body">
        <p class="tjs-alert__title">{{ $heading }}</p>
        <div class="tjs-alert__message">{{ $slot }}</div>
    </div>
    @if($dismissible)
        <button type="button" class="tjs-alert__close" @click="dismiss()" aria-label="Dismiss">
            <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/></svg>
        </button>
    @endif
    @if((int) $timeout > 0)
        <span class="tjs-alert__progress" style="animation-duration: {{ (int) $timeout }}ms" aria-hidden="true"></span>
    @endif
</div>
