@extends('layouts.journal')

@section('title', 'Editorial Board | '.$journal->title)
@section('meta_description', 'Editorial leadership and board members for '.$journal->title.'.')

@section('content')
@php
    $memberCount = $members->total();
@endphp

@include('public.journals.partials.page-styles')

<div class="jp">
    <div class="jp-intro">
        <div>
            <p class="jp-lead">
                The editorial board guides the scope, standards, and strategic direction of
                <strong style="color:var(--j-text);font-weight:700">{{ $journal->title }}</strong>.
                Members oversee peer review, editorial policy, and the quality of published work.
            </p>
        </div>
        @if($memberCount > 0)
            <div class="jp-stat" aria-label="{{ $memberCount }} {{ Str::plural('member', $memberCount) }} listed">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.09 9.09 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                </svg>
                {{ number_format($memberCount) }} {{ Str::plural('member', $memberCount) }}
            </div>
        @endif
    </div>

    @if($members->total() > 0)
        @include('public.journals.partials.list-controls', ['paginator' => $members, 'layout' => $layout])
    @endif

    <div @class(['jp-grid', 'is-list' => ($layout ?? 'grid') === 'list'])>
        @forelse($members as $member)
            @php
                $parts = preg_split('/\s+/', trim($member->name)) ?: [];
                $initials = collect($parts)
                    ->filter()
                    ->take(2)
                    ->map(fn (string $part) => strtoupper(substr($part, 0, 1)))
                    ->implode('');
                $orcid = trim((string) ($member->orcid ?? ''));
                $orcidUrl = $orcid !== '' ? 'https://orcid.org/'.preg_replace('/^https?:\/\/(www\.)?orcid\.org\//i', '', $orcid) : null;
            @endphp
            <article class="jp-card">
                <div class="jp-card__head">
                    <div class="jp-avatar" aria-hidden="true">{{ $initials ?: '?' }}</div>
                    <div class="jp-card__who">
                        <h2 class="jp-name">{{ $member->name }}</h2>
                        @if($member->role_title)
                            <p class="jp-role">{{ $member->role_title }}</p>
                        @endif
                        @if($member->affiliation)
                            <p class="jp-affiliation">{{ $member->affiliation }}</p>
                        @endif
                    </div>
                </div>

                <div class="jp-card__foot">
                    <span class="jp-tag">Editorial board</span>
                    @if($orcidUrl)
                        <a class="jp-orcid" href="{{ $orcidUrl }}" target="_blank" rel="noopener noreferrer">
                            ORCID
                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                        </a>
                    @endif
                </div>
            </article>
        @empty
            <div class="jp-empty">
                <div class="jp-empty__icon">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.09 9.09 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                    </svg>
                </div>
                <p class="jp-empty__title">Editorial board coming soon</p>
                <p class="jp-empty__text">
                    Board members will be listed here once the journal publishes its editorial leadership.
                </p>
            </div>
        @endforelse
    </div>

    @if($members->hasPages())
        {{ $members->links('vendor.pagination.journal') }}
    @endif
</div>
@endsection
