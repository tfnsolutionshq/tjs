@extends('layouts.journal')
@section('title', 'Reviewers | '.$journal->title)
@section('content')
<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
    <h1 class="j-section-title">Reviewers</h1>
    <div class="mt-8 space-y-3">
        @forelse($reviewers as $reviewer)
            <div class="j-card p-5">
                <p class="font-semibold">{{ $reviewer->name }}</p>
                <p class="j-meta mt-1">{{ $reviewer->position }}@if($reviewer->affiliation) — {{ $reviewer->affiliation }}@endif</p>
                @if($reviewer->bio)<p class="mt-2 text-sm" style="color: var(--j-muted)">{{ $reviewer->bio }}</p>@endif
            </div>
        @empty
            <div class="j-card p-6 text-sm" style="color: var(--j-muted)">No public reviewers listed yet.</div>
        @endforelse
    </div>
</div>
@endsection
