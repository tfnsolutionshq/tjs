@props([
    'code' => 'NGN',
    'size' => 20,
])

@php
    $code = strtoupper((string) $code);
    $size = (int) $size;
@endphp

<span {{ $attributes->class('currency-icon') }} aria-hidden="true">
    @switch($code)
        @case('NGN')
            <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="10" cy="10" r="10" fill="#fff"/>
                <path d="M0 10C0 4.477 4.477 0 10 0v20C4.477 20 0 15.523 0 10Z" fill="#008751"/>
                <path d="M10 0h10v20H10V0Z" fill="#008751"/>
                <circle cx="10" cy="10" r="2.8" fill="#008751"/>
            </svg>
            @break
        @case('USD')
            <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="10" cy="10" r="10" fill="#B22234"/>
                <path d="M0 4.5h20M0 9h20M0 13.5h20M0 18h20" stroke="#fff" stroke-width="1.5"/>
                <rect width="8.5" height="8.5" fill="#3C3B6E"/>
                <circle cx="4.25" cy="2.1" r="0.55" fill="#fff"/>
                <circle cx="6.5" cy="2.1" r="0.55" fill="#fff"/>
                <circle cx="4.25" cy="4.35" r="0.55" fill="#fff"/>
                <circle cx="6.5" cy="4.35" r="0.55" fill="#fff"/>
                <circle cx="4.25" cy="6.6" r="0.55" fill="#fff"/>
                <circle cx="6.5" cy="6.6" r="0.55" fill="#fff"/>
            </svg>
            @break
        @case('EUR')
            <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="10" cy="10" r="10" fill="#003399"/>
                <g fill="#FFCC00">
                    <circle cx="10" cy="4.4" r="0.85"/>
                    <circle cx="13.6" cy="5.3" r="0.85"/>
                    <circle cx="15.7" cy="8.2" r="0.85"/>
                    <circle cx="15.7" cy="11.8" r="0.85"/>
                    <circle cx="13.6" cy="14.7" r="0.85"/>
                    <circle cx="10" cy="15.6" r="0.85"/>
                    <circle cx="6.4" cy="14.7" r="0.85"/>
                    <circle cx="4.3" cy="11.8" r="0.85"/>
                    <circle cx="4.3" cy="8.2" r="0.85"/>
                    <circle cx="6.4" cy="5.3" r="0.85"/>
                </g>
            </svg>
            @break
        @case('GBP')
            <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="10" cy="10" r="10" fill="#012169"/>
                <path d="M0 0l20 20M20 0L0 20" stroke="#fff" stroke-width="3.2"/>
                <path d="M0 0l20 20M20 0L0 20" stroke="#C8102E" stroke-width="1.6"/>
                <path d="M10 0v20M0 10h20" stroke="#fff" stroke-width="5.2"/>
                <path d="M10 0v20M0 10h20" stroke="#C8102E" stroke-width="2.8"/>
            </svg>
            @break
        @default
            <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="10" cy="10" r="10" fill="#e2e8f0"/>
                <text x="10" y="13.5" text-anchor="middle" font-size="8" font-weight="700" fill="#64748b">{{ substr($code, 0, 3) }}</text>
            </svg>
    @endswitch
</span>
