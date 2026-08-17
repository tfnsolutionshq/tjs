@extends('layouts.journal')

@section('title', 'Announcements | '.$journal->title)
@section('meta_description', 'News and calls for submissions from '.$journal->title.'.')

@section('content')
@include('public.journals.partials.page-styles')

<div class="jp">
    <div class="jp-intro">
        <div>
            <h1 class="j-section-title" style="margin:0;font-size:1.75rem">Announcements</h1>
            <p class="jp-lead">News, updates, and calls for submissions from <strong style="color:var(--j-text)">{{ $journal->title }}</strong>.</p>
        </div>
    </div>

    @if($announcements->total() > 0)
        @include('public.journals.partials.list-controls', ['paginator' => $announcements, 'layout' => $layout, 'showLayoutToggle' => false])
    @endif

    <div class="jp-grid">
        @forelse($announcements as $announcement)
            <article class="jp-card">
                <div class="jp-card__head">
                    <div class="jp-card__who">
                        <div class="flex flex-wrap gap-2">
                            <span class="jp-tag">{{ \App\Support\AnnouncementType::label($announcement->type) }}</span>
                            @if($announcement->isCallForSubmissions())
                                <span class="jp-tag">{{ $announcement->submissionStatusLabel() }}</span>
                            @endif
                        </div>
                        <h2 class="jp-name">
                            <a href="{{ route('journals.announcements.show', [$journal, $announcement]) }}" style="color:inherit;text-decoration:none">
                                {{ $announcement->title }}
                            </a>
                        </h2>
                        @if($announcement->issueLabel())
                            <p class="jp-affiliation">{{ $announcement->issueLabel() }}</p>
                        @endif
                        @if($announcement->isCallForSubmissions() && $announcement->closes_at)
                            <p class="jp-affiliation">Closes {{ $announcement->closes_at->format('M j, Y g:i A') }}</p>
                        @endif
                    </div>
                </div>

                @if($announcement->summary)
                    <p class="jp-bio">{{ $announcement->summary }}</p>
                @elseif($announcement->body)
                    <p class="jp-bio">{{ Str::limit(strip_tags($announcement->body), 180) }}</p>
                @endif

                <div class="jp-card__foot">
                    <a class="jp-orcid" href="{{ route('journals.announcements.show', [$journal, $announcement]) }}">Read more</a>
                    @if($announcement->acceptsSubmissions())
                        @auth
                            <a class="j-btn" style="margin-left:auto" href="{{ route('author.submissions.create', ['announcement' => $announcement->id]) }}">Submit manuscript</a>
                        @else
                            <a class="j-btn" style="margin-left:auto" href="{{ route('login') }}">Log in to submit</a>
                        @endauth
                    @endif
                </div>
            </article>
        @empty
            <div class="jp-empty">
                <p class="jp-empty__title">No announcements yet</p>
                <p class="jp-empty__text">Published news and calls for submissions will appear here.</p>
            </div>
        @endforelse
    </div>

    @if($announcements->hasPages())
        {{ $announcements->links('vendor.pagination.journal') }}
    @endif
</div>
@endsection
