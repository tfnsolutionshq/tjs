@extends('layouts.member')

@section('title', 'My submissions | '.config('tjs.name'))
@section('page_title', 'My submissions')
@section('page_subtitle', 'Track manuscripts through peer review')

@section('page_actions')
    <a href="{{ route('author.submissions.create') }}" class="admin-btn admin-btn-primary">
        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 8v8M8 12h8"/></svg>
        New submission
    </a>
@endsection

@section('content')
@php
    $status = $status ?? request('status');
    $indexUrl = route('author.submissions.index');
@endphp

<style>
    .as-stats { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; margin-bottom:1rem; }
    @media (min-width:860px){ .as-stats{ grid-template-columns:repeat(4,minmax(0,1fr)); } }
    .as-stat {
        background:#fff; border:1px solid var(--line); border-radius:.95rem; padding:.9rem 1rem;
        box-shadow:0 8px 24px rgba(15,23,42,.035); text-decoration:none; color:inherit;
        transition:border-color .15s ease, box-shadow .15s ease;
    }
    .as-stat:hover { border-color:#93c5fd; }
    .as-stat.is-active { border-color:#93c5fd; box-shadow:0 0 0 3px rgba(37,99,235,.12); }
    .as-stat__label { margin:0; font-size:.68rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--muted); }
    .as-stat__value { margin:.35rem 0 0; font-size:1.5rem; font-weight:800; letter-spacing:-.03em; color:var(--ink); line-height:1; }
    .as-toolbar {
        display:flex; flex-direction:column; gap:.75rem; background:#fff; border:1px solid var(--line);
        border-radius:1rem; padding:.85rem .95rem; margin-bottom:1rem;
        box-shadow:0 8px 24px rgba(15,23,42,.035);
    }
    @media (min-width:800px){ .as-toolbar{ flex-direction:row; align-items:center; } }
    .as-search {
        flex:1; display:flex; align-items:center; gap:.55rem; border:1px solid var(--line);
        border-radius:.75rem; background:#f8fafc; padding:.62rem .85rem; min-width:0;
    }
    .as-search:focus-within { border-color:#93c5fd; background:#fff; box-shadow:0 0 0 3px rgba(37,99,235,.12); }
    .as-search svg { width:1rem; height:1rem; color:var(--muted); flex-shrink:0; }
    .as-search input { width:100%; border:0; outline:0; background:transparent; font:inherit; font-size:.9rem; color:var(--ink); }
    .as-select {
        border:1px solid var(--line); border-radius:.75rem; background:#fff; padding:.62rem .75rem;
        font:inherit; font-size:.84rem; font-weight:600; color:var(--ink);
    }
    .as-list { display:grid; gap:.75rem; }
    .as-item {
        display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:.85rem;
        background:#fff; border:1px solid var(--line); border-radius:1rem; padding:1.05rem 1.15rem;
        box-shadow:0 8px 24px rgba(15,23,42,.035); text-decoration:none; color:inherit;
        transition:transform .15s ease, border-color .15s ease, box-shadow .15s ease;
    }
    .as-item:hover { transform:translateY(-1px); border-color:#93c5fd; box-shadow:0 12px 28px rgba(15,23,42,.07); }
    .as-item__title { margin:0; font-size:1.02rem; font-weight:800; color:var(--ink); letter-spacing:-.01em; line-height:1.35; }
    .as-item:hover .as-item__title { color:#1d4ed8; }
    .as-item__meta { margin:.35rem 0 0; font-size:.8rem; color:var(--muted); display:flex; flex-wrap:wrap; gap:.35rem .65rem; align-items:center; }
    .as-item__side { display:flex; flex-direction:column; align-items:flex-end; gap:.45rem; }
    .as-empty {
        background:#fff; border:1px dashed #cbd5e1; border-radius:1.05rem; padding:2.5rem 1.5rem; text-align:center;
    }
    .as-empty__title { margin:0; font-size:1.05rem; font-weight:800; color:var(--ink); }
    .as-empty__text { margin:.4rem auto 0; max-width:26rem; font-size:.88rem; color:var(--muted); line-height:1.5; }
</style>

@if (session('status'))
    <div class="mp-flash">{{ session('status') }}</div>
@endif

<div class="as-stats">
    <a href="{{ $indexUrl }}" class="as-stat @if(! $status) is-active @endif">
        <p class="as-stat__label">Total</p>
        <p class="as-stat__value">{{ number_format($stats['total']) }}</p>
    </a>
    <a href="{{ route('author.submissions.index', ['status' => 'in_review']) }}" class="as-stat @if($status === 'in_review') is-active @endif">
        <p class="as-stat__label">In review</p>
        <p class="as-stat__value">{{ number_format($stats['in_review']) }}</p>
    </a>
    <a href="{{ route('author.submissions.index', ['status' => 'accepted']) }}" class="as-stat @if($status === 'accepted') is-active @endif">
        <p class="as-stat__label">Accepted</p>
        <p class="as-stat__value">{{ number_format($stats['accepted']) }}</p>
    </a>
    <a href="{{ route('author.submissions.index', ['status' => 'rejected']) }}" class="as-stat @if($status === 'rejected') is-active @endif">
        <p class="as-stat__label">Rejected</p>
        <p class="as-stat__value">{{ number_format($stats['rejected']) }}</p>
    </a>
</div>

<form method="GET" action="{{ $indexUrl }}" class="as-toolbar">
    <div class="as-search">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M20 20l-3.5-3.5"/></svg>
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search title, journal, keywords…">
    </div>
    <select name="status" class="as-select" onchange="this.form.submit()">
        <option value="">All statuses</option>
        <option value="in_review" @selected($status === 'in_review')>In review</option>
        <option value="revision_requested" @selected($status === 'revision_requested')>Revision requested</option>
        <option value="accepted" @selected($status === 'accepted')>Accepted</option>
        <option value="rejected" @selected($status === 'rejected')>Rejected</option>
        <option value="submitted" @selected($status === 'submitted')>Submitted</option>
        <option value="under_review" @selected($status === 'under_review')>Under review</option>
    </select>
    <button type="submit" class="mp-btn mp-btn-secondary">Search</button>
</form>

@if($submissions->isEmpty())
    <div class="as-empty">
        <p class="as-empty__title">{{ request('q') || $status ? 'No matching submissions' : 'No submissions yet' }}</p>
        <p class="as-empty__text">
            @if(request('q') || $status)
                Try clearing filters or searching with a different term.
            @else
                Submit your first manuscript to an active TJS journal to start peer review.
            @endif
        </p>
        @if(! request('q') && ! $status)
            <a href="{{ route('author.submissions.create') }}" class="mp-btn mp-btn-primary" style="margin-top:1.1rem">Start a submission</a>
        @else
            <a href="{{ $indexUrl }}" class="mp-btn mp-btn-secondary" style="margin-top:1.1rem">Clear filters</a>
        @endif
    </div>
@else
    <div class="as-list">
        @foreach($submissions as $submission)
            <a class="as-item" href="{{ route('author.submissions.show', $submission) }}">
                <div class="min-w-0" style="flex:1">
                    <h2 class="as-item__title">{{ $submission->title }}</h2>
                    <p class="as-item__meta">
                        <span>{{ $submission->journal?->title ?? 'Journal' }}</span>
                        <span>·</span>
                        <span>Submitted {{ optional($submission->created_at)->format('M j, Y') }}</span>
                        @if($submission->updated_at && ! $submission->updated_at->eq($submission->created_at))
                            <span>·</span>
                            <span>Updated {{ $submission->updated_at->diffForHumans() }}</span>
                        @endif
                        @if($submission->category)
                            <span>·</span>
                            <span>{{ $submission->category }}</span>
                        @endif
                    </p>
                </div>
                <div class="as-item__side">
                    <span class="mp-badge mp-badge--{{ $submission->status }}">{{ str_replace('_', ' ', $submission->status) }}</span>
                    <span style="font-size:.74rem;font-weight:700;color:#1d4ed8">View details →</span>
                </div>
            </a>
        @endforeach
    </div>

    @if($submissions->hasPages())
        <div style="margin-top:1rem">{{ $submissions->links() }}</div>
    @endif
@endif
@endsection
