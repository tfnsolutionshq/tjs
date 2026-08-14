@props([
    'class' => 'h-8 w-auto object-contain',
    'alt' => config('tjs.organization').' logo',
])

<img
    src="{{ asset('images/tfns-logo.jpeg') }}"
    alt="{{ $alt }}"
    {{ $attributes->merge(['class' => $class]) }}
>
