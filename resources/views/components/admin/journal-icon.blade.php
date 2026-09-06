@props([
    'name',
    'class' => 'h-4 w-4',
])

@php
    $attrs = $attributes->merge([
        'class' => $class,
        'fill' => 'none',
        'viewBox' => '0 0 24 24',
        'stroke' => 'currentColor',
        'stroke-width' => '1.75',
        'stroke-linecap' => 'round',
        'stroke-linejoin' => 'round',
        'aria-hidden' => 'true',
    ]);
@endphp

<svg {{ $attrs }}>
    @switch($name)
        @case('manage')
            <path d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z"/>
            @break
        @case('volumes')
            <path d="M4 5.5C4 4.67 4.67 4 5.5 4H11v16H5.5A1.5 1.5 0 014 18.5v-13z"/>
            <path d="M20 5.5c0-.83-.67-1.5-1.5-1.5H13v16h5.5a1.5 1.5 0 001.5-1.5v-13z"/>
            <path d="M12 4v16"/>
            @break
        @case('articles')
            <path d="M7 3.5h7.5L19 8v12.5a1 1 0 01-1 1H7a1 1 0 01-1-1V4.5a1 1 0 011-1z"/>
            <path d="M14.5 3.5V8H19"/>
            <path d="M9 12h6"/>
            <path d="M9 16h6"/>
            @break
        @case('submissions')
            <path d="M4 14l2.5-7.5A1 1 0 017.45 6h9.1a1 1 0 01.95.5L20 14"/>
            <path d="M4 14h4.2a2 2 0 011.8 1.1l.4.8a1 1 0 00.9.6h1.4a1 1 0 00.9-.6l.4-.8A2 2 0 0115.8 14H20v4.5a1 1 0 01-1 1H5a1 1 0 01-1-1V14z"/>
            @break
        @case('users')
            <path d="M16 11a3.5 3.5 0 100-7 3.5 3.5 0 000 7z"/>
            <path d="M8 12a3.5 3.5 0 100-7 3.5 3.5 0 000 7z"/>
            <path d="M2.8 19.5a5.7 5.7 0 0110.4 0"/>
            <path d="M10.8 19.5a5.7 5.7 0 0110.4 0"/>
            @break
    @endswitch
</svg>
