{{-- Favicon / touch icons. Pass brandJournal explicitly — never inherit $journal from page loops. --}}
@php
    $brandJournal = ($brandJournal ?? null) instanceof \App\Models\Journal ? $brandJournal : null;
    $icon = $brandJournal?->logoUrl() ?: asset(config('tjs.brand_icon', 'images/tfns-logo.jpeg'));
@endphp
<link rel="icon" href="{{ $icon }}" sizes="any">
<link rel="apple-touch-icon" href="{{ $icon }}">
<link rel="shortcut icon" href="{{ $icon }}">
