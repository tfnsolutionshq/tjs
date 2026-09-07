@props([
    'variant' => 'dark',
    'type' => 'full',
    'alt' => config('tjs.full_name'),
])

@php
    $key = match (true) {
        $type === 'icon' && $variant === 'light' => 'icon_light',
        $type === 'icon' && $variant === 'dark' => 'icon_dark',
        $variant === 'light' => 'logo_light',
        default => 'logo_dark',
    };
@endphp

<img
    src="{{ \App\Support\PlatformBrand::asset($key) }}"
    alt="{{ $alt }}"
    {{ $attributes }}
>
