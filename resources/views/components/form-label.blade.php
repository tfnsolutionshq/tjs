@props([
    'for' => null,
    'field' => null,
    'help' => null,
    'required' => false,
    'reqClass' => 'tjs-req',
])

@php
    $helpText = $help ?? ($field ? \App\Support\FormHelp::get($field) : null);
@endphp

<label @if($for) for="{{ $for }}" @endif {{ $attributes->merge(['class' => 'tjs-label-row']) }}>
    <span class="tjs-label-row__text">{{ $slot }}</span>
    @if($required)
        <span class="{{ $reqClass }}" title="Required">*</span>
    @endif
    <x-field-helper :text="$helpText" />
</label>
