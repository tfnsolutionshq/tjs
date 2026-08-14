@props([
    'name' => 'license',
    'id' => null,
    'value' => null,
    'required' => false,
    'allowEmpty' => true,
    'emptyLabel' => 'Select a license…',
    'selectClass' => 'af-select',
])

@php
    use App\Support\Licenses;

    $id = $id ?: $name;
    $options = Licenses::options();
    $current = Licenses::normalize($value) ?? '';
    $urls = collect($options)->mapWithKeys(fn ($o) => [$o['label'] => $o['url']])->all();
@endphp

<div
    class="license-picker"
    x-data="{
        license: @js($current),
        urls: @js($urls),
        get url() { return this.urls[this.license] || ''; }
    }"
>
    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @class([$selectClass])
        x-model="license"
        @if($required) required @endif
        {{ $attributes }}
    >
        @if($allowEmpty)
            <option value="">{{ $emptyLabel }}</option>
        @endif
        @foreach($options as $opt)
            <option value="{{ $opt['label'] }}">{{ $opt['label'] }} — {{ $opt['description'] }}</option>
        @endforeach
    </select>

    <p class="af-hint" style="margin-top:.45rem" x-show="url" x-cloak>
        <a style="color:#1d4ed8;font-weight:700;text-decoration:none" :href="url" target="_blank" rel="noopener">View license terms →</a>
    </p>
</div>
