@extends('layouts.journal')
@section('title', 'About | '.$journal->title)
@section('content')
<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
    <h1 class="j-section-title">About the journal</h1>
    <div class="j-card mt-6 p-6 text-sm leading-relaxed whitespace-pre-line">{{ $journal->description }}</div>
    <dl class="j-card mt-4 grid gap-3 p-6 text-sm">
        @if($journal->publisher)<div><dt class="font-semibold">Publisher</dt><dd class="j-meta">{{ $journal->publisher }}</dd></div>@endif
        @if($journal->issn)<div><dt class="font-semibold">ISSN</dt><dd class="j-meta">{{ $journal->issn }}</dd></div>@endif
        @if($journal->eissn)<div><dt class="font-semibold">eISSN</dt><dd class="j-meta">{{ $journal->eissn }}</dd></div>@endif
        @if($journal->default_license)
            <div>
                <dt class="font-semibold">Default license</dt>
                <dd class="j-meta">
                    @php($licenseUrl = \App\Support\Licenses::url($journal->default_license))
                    @if($licenseUrl)
                        <a class="j-link" href="{{ $licenseUrl }}" target="_blank" rel="noopener license">{{ \App\Support\Licenses::label($journal->default_license) ?? $journal->default_license }}</a>
                    @else
                        {{ $journal->default_license }}
                    @endif
                </dd>
            </div>
        @endif
    </dl>
</div>
@endsection
