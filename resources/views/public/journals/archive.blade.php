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
<div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
    <h1 class="j-section-title">Archives</h1>
    <p class="j-meta mt-2">Browse published volumes and issues.</p>
    <div class="mt-8 space-y-6">
        @forelse($volumes as $volume)
            <section class="j-card p-5 sm:p-6">
                <div style="display:flex;gap:1rem;align-items:flex-start">
                    @if($volume->coverUrl())
                        <a href="{{ $volume->issues->first() ? route('journals.issues.show', [$journal, $volume->issues->first()]) : '#' }}" style="flex-shrink:0">
                            <img
                                src="{{ $volume->coverUrl() }}"
                                alt="Cover for Volume {{ $volume->volume_number }}"
                                width="96"
                                height="128"
                                style="width:6rem;height:8rem;object-fit:cover;border-radius:.65rem;border:1px solid color-mix(in srgb, var(--j-text) 12%, transparent)"
                            >
                        </a>
                    @endif
                    <div style="min-width:0;flex:1">
                        <h2 class="j-display text-xl font-semibold">Volume {{ $volume->volume_number }} ({{ $volume->year }})</h2>
                        @if($volume->title)<p class="j-meta mt-1">{{ $volume->title }}</p>@endif
                        <ul class="mt-4 space-y-2">
                            @foreach($volume->issues as $issue)
                                <li>
                                    <a class="j-link" href="{{ route('journals.issues.show', [$journal, $issue]) }}">
                                        Issue {{ $issue->issue_number }}@if($issue->title) — {{ $issue->title }}@endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </section>
        @empty
            <div class="j-card p-6 text-sm" style="color: var(--j-muted)">No archived volumes yet.</div>
        @endforelse
    </div>
</div>
@endsection
