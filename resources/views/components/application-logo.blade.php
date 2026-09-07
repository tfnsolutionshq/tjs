@props([
    'class' => 'h-8 w-auto object-contain',
    'alt' => config('tjs.full_name'),
    'variant' => 'dark',
    'type' => 'full',
])

<x-platform-logo
    :variant="$variant"
    :type="$type"
    :alt="$alt"
    {{ $attributes->merge(['class' => $class]) }}
/>
