@extends('layouts.journal')

@section('title', $announcement->title.' | '.$journal->title)
@section('meta_description', $announcement->summary ?: Str::limit(strip_tags($announcement->body ?? ''), 160))

@section('content')
@include('public.journals.partials.page-styles')

<div class="jp">
    <div class="jp-intro">
        <div>
            <div class="flex flex-wrap gap-2" style="margin-bottom:.65rem">
                <span class="jp-tag">{{ \App\Support\AnnouncementType::label($announcement->type) }}</span>
                @if($announcement->isCallForSubmissions())
                    <span class="jp-tag">{{ $announcement->submissionStatusLabel() }}</span>
                @endif
            </div>
            <h1 class="j-section-title" style="margin:0;font-size:1.85rem">{{ $announcement->title }}</h1>
            @if($announcement->issueLabel())
                <p class="jp-lead" style="margin-top:.75rem">Target issue: <strong style="color:var(--j-text)">{{ $announcement->issueLabel() }}</strong></p>
            @endif
            @if($announcement->isCallForSubmissions())
                <p class="jp-lead" style="margin-top:.45rem">
                    @if($announcement->opens_at)
                        Opens {{ $announcement->opens_at->format('M j, Y g:i A') }}
                    @endif
                    @if($announcement->closes_at)
                        · Closes {{ $announcement->closes_at->format('M j, Y g:i A') }}
                    @endif
                </p>
            @endif
        </div>
        @if($announcement->acceptsSubmissions())
            <div style="display:flex;flex-wrap:wrap;gap:.55rem">
                @auth
                    <a href="{{ route('author.submissions.create', ['announcement' => $announcement->id]) }}" class="j-btn">Submit manuscript</a>
                @else
                    <a href="{{ route('login') }}" class="j-btn">Log in to submit</a>
                @endauth
            </div>
        @endif
    </div>

    @if($announcement->body)
        <div class="jp-card">
            <div class="jp-card__body" style="white-space:pre-wrap;line-height:1.65;color:var(--j-text)">{{ $announcement->body }}</div>
        </div>
    @endif

    <p style="margin-top:1.25rem">
        <a href="{{ route('journals.announcements', $journal) }}" class="j-link">&larr; All announcements</a>
    </p>
</div>
@endsection
