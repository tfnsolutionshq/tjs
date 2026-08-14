@extends('layouts.journal')
@section('title', 'Editorial Board | '.$journal->title)
@section('content')
<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
    <h1 class="j-section-title">Editorial Board</h1>
    <div class="mt-8 space-y-3">
        @forelse($members as $member)
            <div class="j-card p-5">
                <p class="font-semibold">{{ $member->name }}</p>
                <p class="j-meta mt-1">{{ $member->role_title }}@if($member->affiliation) — {{ $member->affiliation }}@endif</p>
            </div>
        @empty
            <div class="j-card p-6 text-sm" style="color: var(--j-muted)">Editorial board to be announced.</div>
        @endforelse
    </div>
</div>
@endsection
