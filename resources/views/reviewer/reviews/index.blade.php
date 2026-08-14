@extends('layouts.member')

@section('title', 'Review queue | '.config('tjs.name'))
@section('page_title', 'Review queue')
@section('page_subtitle', 'Assignments awaiting your decision')

@section('content')
<section class="mp-card">
    <div class="mp-card__body" style="padding-top:1.1rem">
        @forelse($assignments as $assignment)
            @php $submission = $assignment->submission; @endphp
            <a class="mp-row" href="{{ route('reviewer.reviews.show', $submission) }}">
                <div class="min-w-0" style="flex:1">
                    <p class="mp-row__title">{{ $submission?->title }}</p>
                    <p class="mp-row__meta">
                        {{ $submission?->journal?->title }}
                        · {{ $submission?->author?->name }}
                        @if($assignment->due_at) · Due {{ \Illuminate\Support\Carbon::parse($assignment->due_at)->format('M j, Y') }}@endif
                    </p>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:.35rem">
                    <span class="mp-badge">Priority {{ $assignment->priority }}</span>
                    <span class="mp-badge">{{ str_replace('_', ' ', $assignment->status) }}</span>
                </div>
            </a>
        @empty
            <div style="padding:1.5rem .25rem;text-align:center">
                <p class="mp-empty" style="font-size:.95rem">No open review assignments.</p>
            </div>
        @endforelse
    </div>
</section>
@endsection
