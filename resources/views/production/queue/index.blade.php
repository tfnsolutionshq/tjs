@extends('layouts.member')

@section('title', 'Production queue | '.config('tjs.name'))
@section('page_title', 'Production queue')
@section('page_subtitle', 'Format accepted manuscripts for publication')

@section('content')
<style>
    .pq-stats { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.65rem; margin-bottom:1rem; }
    @media (min-width:760px) { .pq-stats { grid-template-columns:repeat(4,minmax(0,1fr)); } }
    .pq-stat {
        background:#fff; border:1px solid var(--line); border-radius:.85rem; padding:.85rem 1rem;
        text-decoration:none; color:inherit; box-shadow:0 6px 18px rgba(15,23,42,.03);
    }
    .pq-stat.is-active { border-color:#93c5fd; background:#eff6ff; }
    .pq-stat__label { margin:0; font-size:.68rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--muted); }
    .pq-stat__value { margin:.3rem 0 0; font-size:1.35rem; font-weight:800; color:var(--ink); }
</style>

<div class="pq-stats">
    <a href="{{ route('production.queue.index', ['filter' => 'awaiting', 'journal_id' => $journalId]) }}" class="pq-stat @if($filter === 'awaiting') is-active @endif">
        <p class="pq-stat__label">Awaiting production</p>
        <p class="pq-stat__value">{{ number_format($stats['awaiting']) }}</p>
    </a>
    <a href="{{ route('production.queue.index', ['filter' => 'in_production', 'journal_id' => $journalId]) }}" class="pq-stat @if($filter === 'in_production') is-active @endif">
        <p class="pq-stat__label">In production</p>
        <p class="pq-stat__value">{{ number_format($stats['in_production']) }}</p>
    </a>
    <a href="{{ route('production.queue.index', ['filter' => 'ready', 'journal_id' => $journalId]) }}" class="pq-stat @if($filter === 'ready') is-active @endif">
        <p class="pq-stat__label">Ready for publication</p>
        <p class="pq-stat__value">{{ number_format($stats['ready']) }}</p>
    </a>
    <a href="{{ route('production.queue.index', ['filter' => 'completed', 'journal_id' => $journalId]) }}" class="pq-stat @if($filter === 'completed') is-active @endif">
        <p class="pq-stat__label">Published</p>
        <p class="pq-stat__value">{{ number_format($stats['completed']) }}</p>
    </a>
</div>

@if($journals->count() > 1)
    <form method="GET" action="{{ route('production.queue.index') }}" class="mp-card" style="margin-bottom:1rem;padding:.85rem 1rem">
        <input type="hidden" name="filter" value="{{ $filter }}">
        <label for="journal_id" style="font-size:.78rem;font-weight:700;color:#334155">Journal</label>
        <select id="journal_id" name="journal_id" class="mp-input" style="margin-top:.35rem" onchange="this.form.submit()">
            <option value="">All journals</option>
            @foreach($journals as $journal)
                <option value="{{ $journal->id }}" @selected((int) $journalId === (int) $journal->id)>{{ $journal->title }}</option>
            @endforeach
        </select>
    </form>
@endif

<section class="mp-card">
    <div class="mp-card__body" style="padding-top:1.1rem">
        @forelse($submissions as $submission)
            <a class="mp-row" href="{{ route('production.queue.show', $submission) }}">
                <div class="min-w-0" style="flex:1">
                    <p class="mp-row__title">{{ $submission->title }}</p>
                    <p class="mp-row__meta">
                        {{ $submission->journal?->title }}
                        · {{ $submission->author?->name }}
                        @if($submission->reviewed_at) · Accepted {{ $submission->reviewed_at->format('M j, Y') }} @endif
                    </p>
                </div>
                <span class="mp-badge mp-badge--{{ $submission->status }}">{{ \App\Support\SubmissionStatus::label($submission->status) }}</span>
            </a>
        @empty
            <div style="padding:1.5rem .25rem;text-align:center">
                <p class="mp-empty" style="font-size:.95rem">No manuscripts in this production list.</p>
            </div>
        @endforelse
    </div>
</section>

<div style="margin-top:1rem">{{ $submissions->links() }}</div>
@endsection
