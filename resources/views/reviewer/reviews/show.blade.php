@extends('layouts.member')

@section('title', 'Review — '.$submission->title)
@section('page_title', 'Review decision')
@section('page_subtitle', $submission->journal?->title)

@section('page_actions')
    <a href="{{ route('reviewer.reviews.index') }}" class="admin-btn admin-btn-secondary">Review queue</a>
@endsection

@section('content')
<div class="mp-narrow">
    <header class="mp-hero">
        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem;margin-bottom:.55rem">
            <span class="mp-badge mp-badge--{{ $submission->status }}">{{ str_replace('_', ' ', $submission->status) }}</span>
        </div>
        <h1 class="mp-hero__title">{{ $submission->title }}</h1>
        <p class="mp-hero__sub">Author: {{ $submission->author?->name }}</p>
    </header>

    <section class="mp-card" style="margin-bottom:1rem">
        <div class="mp-card__head">
            <h2 class="mp-card__title">Submission</h2>
        </div>
        <div class="mp-card__body" style="display:grid;gap:.85rem;font-size:.9rem">
            @if($assignment)
                <p style="margin:0;color:#334155">
                    <strong>Assignment:</strong> {{ str_replace('_', ' ', $assignment->status) }}
                    @if($assignment->due_at) · due {{ \Illuminate\Support\Carbon::parse($assignment->due_at)->format('M j, Y') }}@endif
                </p>
            @endif
            @if($submission->abstract)
                <div>
                    <p style="margin:0;font-size:.72rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted)">Abstract</p>
                    <p style="margin:.35rem 0 0;white-space:pre-wrap;line-height:1.55;color:var(--muted)">{{ $submission->abstract }}</p>
                </div>
            @endif
        </div>
    </section>

    <section class="mp-card">
        <div class="mp-card__head">
            <div>
                <h2 class="mp-card__title">Decision</h2>
                <p class="mp-card__desc">Record your recommendation for the editors.</p>
            </div>
        </div>
        <form
            method="POST"
            action="{{ route('reviewer.reviews.decide', $submission) }}"
            class="mp-card__body"
            style="display:grid;gap:.9rem"
            x-data="{ submitting: false }"
            @submit="if (submitting) { $event.preventDefault() } else { submitting = true }"
        >
            @csrf
            <div class="mp-field">
                <label for="decision">Decision</label>
                <select id="decision" name="decision" required class="mp-select" :disabled="submitting">
                    <option value="">Select</option>
                    <option value="accept" @selected(old('decision') === 'accept')>Accept</option>
                    <option value="reject" @selected(old('decision') === 'reject')>Reject</option>
                    <option value="revision_requested" @selected(old('decision') === 'revision_requested')>Request revision</option>
                </select>
                @error('decision')<p class="mp-error">{{ $message }}</p>@enderror
            </div>
            <div class="mp-field">
                <label for="comment">Comment</label>
                <textarea id="comment" name="comment" rows="4" class="mp-textarea" :readonly="submitting">{{ old('comment') }}</textarea>
                @error('comment')<p class="mp-error">{{ $message }}</p>@enderror
            </div>
            <div class="mp-field">
                <label for="rejection_reason">Rejection reason</label>
                <textarea id="rejection_reason" name="rejection_reason" rows="3" class="mp-textarea" :readonly="submitting">{{ old('rejection_reason') }}</textarea>
                <p class="mp-hint">Required when deciding to reject.</p>
                @error('rejection_reason')<p class="mp-error">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="mp-btn mp-btn-primary" style="width:fit-content" :disabled="submitting">
                <span class="mp-spinner" x-show="submitting" x-cloak></span>
                <span x-text="submitting ? 'Submitting…' : 'Submit decision'"></span>
            </button>
        </form>
    </section>
</div>
@endsection
