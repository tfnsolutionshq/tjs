@extends('layouts.journal')

@section('title', 'About | '.$journal->title)
@section('meta_description', Str::limit(strip_tags($journal->description ?: $journal->subtitle ?: $journal->title), 155))
@section('canonical', route('journals.about', $journal))
@section('seo')
    @include('seo.page-meta', ['meta' => app(\App\Services\Seo\PageMeta::class)->journal(
        $journal,
        'About | '.$journal->title,
        \Illuminate\Support\Str::limit(strip_tags($journal->description ?: $journal->subtitle ?: $journal->title), 155),
        route('journals.about', $journal)
    )])
@endsection

@section('content')
@php
    $hasMeta = $journal->publisher || $journal->issn || $journal->eissn || $journal->default_license;
    $licenseUrl = $journal->default_license ? \App\Support\Licenses::url($journal->default_license) : null;
    $licenseLabel = $journal->default_license
        ? (\App\Support\Licenses::label($journal->default_license) ?? $journal->default_license)
        : null;
@endphp

@include('public.journals.partials.page-styles')

<div class="jp">
    <div class="jp-intro">
        <div>
            <p class="jp-lead">
                Learn about the aims, publishing standards, and identifiers for
                <strong style="color:var(--j-text);font-weight:700">{{ $journal->title }}</strong>.
            </p>
        </div>
        @if($journal->language)
            <div class="jp-stat">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5a17.92 17.92 0 01-8.716-2.247m0 0A8.966 8.966 0 013 12c0-1.264.26-2.468.732-3.564"/>
                </svg>
                {{ strtoupper($journal->language) }}
            </div>
        @endif
    </div>

    <div class="jp-split">
        <section class="jp-panel">
            <h2 class="jp-panel__title">About this journal</h2>
            @if(trim((string) $journal->description) !== '')
                <div class="jp-prose tjs-prose">{!! \App\Support\SafeHtml::display($journal->description) !!}</div>
            @else
                <p class="jp-lead" style="max-width:none">
                    A description for this journal has not been added yet. Check back soon for scope, audience, and submission guidance.
                </p>
            @endif

            @if($journal->subtitle)
                <p class="jp-affiliation" style="margin-top:1rem;font-size:.88rem">
                    <strong style="color:var(--j-text)">Focus:</strong> {{ $journal->subtitle }}
                </p>
            @endif
        </section>

        @if($hasMeta)
            <aside class="jp-panel">
                <h2 class="jp-panel__title">Publishing details</h2>
                <ul class="jp-meta-list">
                    @if($journal->publisher)
                        <li class="jp-meta-item">
                            <span class="jp-meta-label">Publisher</span>
                            <span class="jp-meta-value">{{ $journal->publisher }}</span>
                        </li>
                    @endif
                    @if($journal->issn)
                        <li class="jp-meta-item">
                            <span class="jp-meta-label">ISSN</span>
                            <span class="jp-meta-value">{{ $journal->issn }}</span>
                        </li>
                    @endif
                    @if($journal->eissn)
                        <li class="jp-meta-item">
                            <span class="jp-meta-label">eISSN</span>
                            <span class="jp-meta-value">{{ $journal->eissn }}</span>
                        </li>
                    @endif
                    @if($journal->default_license)
                        <li class="jp-meta-item">
                            <span class="jp-meta-label">Default license</span>
                            <span class="jp-meta-value">
                                @if($licenseUrl)
                                    <a class="j-link" href="{{ $licenseUrl }}" target="_blank" rel="noopener license">{{ $licenseLabel }}</a>
                                @else
                                    {{ $licenseLabel }}
                                @endif
                            </span>
                        </li>
                    @endif
                </ul>
            </aside>
        @endif
    </div>
</div>
@endsection
