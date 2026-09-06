@extends('layouts.journal')

@section('title', 'Reviewers | '.$journal->title)
@section('meta_description', 'Peer reviewers who support the editorial process at '.$journal->title.'.')
@section('canonical', route('journals.reviewers', $journal))
@section('seo')
    @include('seo.page-meta', ['meta' => app(\App\Services\Seo\PageMeta::class)->journal(
        $journal,
        'Reviewers | '.$journal->title,
        'Peer reviewers who support the editorial process at '.$journal->title.'.',
        route('journals.reviewers', $journal)
    )])
@endsection

@section('content')
@php
    $reviewerCount = $reviewers->total();
@endphp

@include('public.journals.partials.page-styles')

<div class="jp">
    <div class="jp-intro">
        <div>
            <p class="jp-lead">
                These researchers volunteer their expertise to evaluate submissions for
                <strong style="color:var(--j-text);font-weight:700">{{ $journal->title }}</strong>.
                Their recommendations help editors maintain quality and fairness in the peer-review process.
            </p>
        </div>
        @if($reviewerCount > 0)
            <div class="jp-stat" aria-label="{{ $reviewerCount }} {{ Str::plural('reviewer', $reviewerCount) }} listed">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
                {{ number_format($reviewerCount) }} {{ Str::plural('reviewer', $reviewerCount) }}
            </div>
        @endif
    </div>

    @if($reviewers->total() > 0)
        @include('public.journals.partials.list-controls', ['paginator' => $reviewers, 'layout' => $layout])
    @endif

    <div @class(['jp-grid', 'is-list' => ($layout ?? 'grid') === 'list'])>
        @forelse($reviewers as $reviewer)
            @php
                $parts = preg_split('/\s+/', trim($reviewer->name)) ?: [];
                $initials = collect($parts)
                    ->filter()
                    ->take(2)
                    ->map(fn (string $part) => strtoupper(substr($part, 0, 1)))
                    ->implode('');
                $orcid = trim((string) ($reviewer->orcid ?? ''));
                $orcidUrl = $orcid !== '' ? 'https://orcid.org/'.preg_replace('/^https?:\/\/(www\.)?orcid\.org\//i', '', $orcid) : null;
            @endphp
            <article class="jp-card">
                <div class="jp-card__head">
                    <div class="jp-avatar" aria-hidden="true">
                        @if($reviewer->avatarUrl())
                            <img src="{{ $reviewer->avatarUrl() }}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;display:block">
                        @else
                            {{ $initials ?: '?' }}
                        @endif
                    </div>
                    <div class="jp-card__who">
                        <h2 class="jp-name">{{ $reviewer->name }}</h2>
                        @if($reviewer->position)
                            <p class="jp-role">{{ $reviewer->position }}</p>
                        @endif
                        @if($reviewer->affiliation)
                            <p class="jp-affiliation">{{ $reviewer->affiliation }}</p>
                        @endif
                    </div>
                </div>

                @if($reviewer->bio)
                    <p class="jp-bio">{{ $reviewer->bio }}</p>
                @endif

                <div class="jp-card__foot">
                    <span class="jp-tag">Peer reviewer</span>
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                    </svg>
                </div>
                <p class="jp-empty__title">Reviewers coming soon</p>
                <p class="jp-empty__text">
                    No public reviewers are listed for this journal yet. Reviewers who opt in to be visible will appear here.
                </p>
            </div>
        @endforelse
    </div>

    @if($reviewers->hasPages())
        {{ $reviewers->links('vendor.pagination.journal') }}
    @endif

    @auth
        @if($reviewerRequest && (auth()->user()->isJournalMember($journal) || $reviewerRequest['pending']))
            <div style="margin-top:1.25rem">
                <x-reviewer-request-panel
                    :journal="$journal"
                    :can-request="$reviewerRequest['canRequest']"
                    :reason="$reviewerRequest['reason']"
                    :pending="$reviewerRequest['pending']"
                    :latest="$reviewerRequest['latest']"
                    compact
                />
            </div>
        @endif
    @endauth
</div>
@endsection
