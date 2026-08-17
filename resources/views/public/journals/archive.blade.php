@extends('layouts.journal')

@section('title', 'Archives | '.$journal->title)
@section('meta_description', 'Browse published volumes and issues of '.$journal->title)
@section('canonical', route('journals.archive', $journal))

@section('seo')
    <meta property="og:title" content="Archives | {{ $journal->title }}">
    <meta property="og:description" content="Browse published volumes and issues of {{ $journal->title }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ route('journals.archive', $journal) }}">
    <meta property="og:site_name" content="{{ $journal->title }}">
    @if($journal->headerImageUrl() || $journal->logoUrl())
        <meta property="og:image" content="{{ $journal->headerImageUrl() ?: $journal->logoUrl() }}">
    @elseif($volumes->first()?->coverUrl())
        <meta property="og:image" content="{{ $volumes->first()->coverUrl() }}">
    @endif
@endsection

@section('content')
@php
    $volumeCount = $volumeCount ?? $volumes->total();
    $issueCount = $issueCount ?? 0;
@endphp

@include('public.journals.partials.page-styles')

<div class="jp">
    <div class="jp-intro">
        <div>
            <p class="jp-lead">
                Explore published volumes and issues of
                <strong style="color:var(--j-text);font-weight:700">{{ $journal->title }}</strong>.
                Select an issue to read its table of contents and articles.
            </p>
        </div>
        @if($volumeCount > 0)
            <div class="jp-stat" aria-label="{{ $volumeCount }} volumes, {{ $issueCount }} issues">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                </svg>
                {{ number_format($volumeCount) }} {{ Str::plural('volume', $volumeCount) }}
                · {{ number_format($issueCount) }} {{ Str::plural('issue', $issueCount) }}
            </div>
        @endif
    </div>

    @if($volumes->total() > 0)
        @include('public.journals.partials.list-controls', ['paginator' => $volumes, 'layout' => $layout])
    @endif

    <div @class(['jp-grid jp-grid--2', 'is-list' => ($layout ?? 'grid') === 'list'])>
        @forelse($volumes as $volume)
            @php
                $firstIssue = $volume->issues->first();
                $coverHref = $firstIssue ? route('journals.issues.show', [$journal, $firstIssue]) : null;
            @endphp
            <article class="jp-card">
                <div class="jp-volume">
                    @if($volume->coverUrl())
                        @if($coverHref)
                            <a href="{{ $coverHref }}" class="jp-volume__cover">
                                <img src="{{ $volume->coverUrl() }}" alt="Cover for Volume {{ $volume->volume_number }}">
                            </a>
                        @else
                            <div class="jp-volume__cover">
                                <img src="{{ $volume->coverUrl() }}" alt="Cover for Volume {{ $volume->volume_number }}">
                            </div>
                        @endif
                    @else
                        @if($coverHref)
                            <a href="{{ $coverHref }}" class="jp-volume__cover jp-volume__cover--placeholder" aria-label="Volume {{ $volume->volume_number }}">
                                V{{ $volume->volume_number }}
                            </a>
                        @else
                            <div class="jp-volume__cover jp-volume__cover--placeholder" aria-hidden="true">V{{ $volume->volume_number }}</div>
                        @endif
                    @endif

                    <div>
                        <h2 class="jp-volume__title">Volume {{ $volume->volume_number }} ({{ $volume->year }})</h2>
                        @if($volume->title)
                            <p class="jp-volume__subtitle">{{ $volume->title }}</p>
                        @endif

                        @if($volume->issues->isNotEmpty())
                            <ul class="jp-issues">
                                @foreach($volume->issues as $issue)
                                    <li>
                                        <a class="jp-issue-link" href="{{ route('journals.issues.show', [$journal, $issue]) }}">
                                            Issue {{ $issue->issue_number }}
                                            @if($issue->title)
                                                <span style="color:var(--j-muted);font-weight:600">· {{ $issue->title }}</span>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="jp-volume__subtitle" style="margin-top:.75rem">No published issues in this volume yet.</p>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="jp-empty">
                <div class="jp-empty__icon">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                    </svg>
                </div>
                <p class="jp-empty__title">No archives yet</p>
                <p class="jp-empty__text">
                    Published volumes and issues will appear here once content is released.
                </p>
            </div>
        @endforelse
    </div>

    @if($volumes->hasPages())
        {{ $volumes->links('vendor.pagination.journal') }}
    @endif
</div>
@endsection
