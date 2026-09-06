@extends('layouts.member')

@section('title', 'Review — '.$submission->title)
@section('page_title', 'Review decision')
@section('page_subtitle', $submission->journal?->title)

@section('page_actions')
    <a href="{{ route('reviewer.reviews.index') }}" class="admin-btn admin-btn-secondary">Review queue</a>
@endsection

@section('content')
<div class="mp-narrow" style="display:grid;gap:1rem">
    <header class="mp-hero">
        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem;margin-bottom:.55rem">
            <span class="mp-badge mp-badge--{{ $submission->status }}">{{ str_replace('_', ' ', $submission->status) }}</span>
            <span class="mp-badge">{{ \App\Support\ReviewType::label($reviewType) }}</span>
            @if($submission->status === 'resubmitted')
                <span class="mp-badge" style="background:#eff6ff;color:#1d4ed8">Resubmission</span>
            @endif
        </div>
        <h1 class="mp-hero__title">{{ $submission->title }}</h1>
        @if(\App\Support\ReviewType::isOpen($reviewType))
            <p class="mp-hero__sub">Author: {{ $submission->author?->name }}</p>
        @else
            <p class="mp-hero__sub">Author identity withheld for closed review.</p>
        @endif
    </header>

    <section class="mp-card">
        <div class="mp-card__head">
            <div>
                <h2 class="mp-card__title">Submission</h2>
                <p class="mp-card__desc">Current manuscript and metadata for this review round.</p>
            </div>
            @if($submission->document_path)
                <a href="{{ route('reviewer.reviews.download', $submission) }}" class="admin-btn admin-btn-secondary" style="padding:.45rem .75rem;font-size:.78rem">Download current file</a>
            @endif
        </div>
        <div class="mp-card__body" style="display:grid;gap:.85rem;font-size:.9rem">
            @if($assignment)
                <p style="margin:0;color:#334155">
                    <strong>Assignment:</strong> {{ str_replace('_', ' ', $assignment->status) }}
                    @if($assignment->due_at) · due {{ \Illuminate\Support\Carbon::parse($assignment->due_at)->format('M j, Y') }}@endif
                </p>
            @endif
            @if($submission->category || $submission->keywords)
                <p style="margin:0;color:var(--muted);font-size:.82rem">
                    @if($submission->category)<strong>Category:</strong> {{ $submission->category }}@endif
                    @if($submission->category && $submission->keywords) · @endif
                    @if($submission->keywords)<strong>Keywords:</strong> {{ $submission->keywords }}@endif
                </p>
            @endif
            @if($submission->issue)
                <p style="margin:0;color:var(--muted);font-size:.82rem">
                    <strong>Target issue:</strong> {{ $submission->issue->label() }}
                    @if($submission->announcement) · {{ $submission->announcement->title }}@endif
                </p>
            @endif
            @if($submission->abstract)
                <div>
                    <p style="margin:0;font-size:.72rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted)">Abstract</p>
                    <div class="tjs-prose" style="margin:.35rem 0 0;line-height:1.55;color:var(--muted)">{!! \App\Support\SafeHtml::display($submission->abstract) !!}</div>
                </div>
            @endif
        </div>
    </section>

    <section class="mp-card">
        <div class="mp-card__head">
            <div>
                <h2 class="mp-card__title">Revision history</h2>
                <p class="mp-card__desc">Every manuscript version uploaded for this submission.</p>
            </div>
        </div>
        <div class="mp-card__body">
            @forelse($submission->revisions->sortBy('revision_number') as $revision)
                <div class="mp-row" style="align-items:flex-start">
                    <div class="min-w-0" style="flex:1">
                        <p class="mp-row__title">
                            @if((int) $revision->revision_number === 0)
                                Original submission
                            @else
                                Revision {{ $revision->revision_number }}
                            @endif
                            @if($revision->document_path === $submission->document_path)
                                <span class="mp-badge" style="margin-left:.35rem">Current</span>
                            @endif
                        </p>
                        <p class="mp-row__meta">
                            {{ $revision->created_at?->format('M j, Y g:i A') }}
                            @if($revision->uploader) · {{ $revision->uploader->name }}@endif
                            @if($revision->document_path) · {{ basename($revision->document_path) }}@endif
                        </p>
                        @if($revision->notes)
                            <p style="margin:.45rem 0 0;font-size:.8rem;color:#475569;white-space:pre-wrap">{{ $revision->notes }}</p>
                        @endif
                    </div>
                    <a href="{{ route('reviewer.reviews.revisions.download', [$submission, $revision]) }}" class="admin-btn admin-btn-secondary" style="padding:.4rem .7rem;font-size:.74rem">Download</a>
                </div>
            @empty
                @if($submission->document_path)
                    <div class="mp-row" style="align-items:flex-start">
                        <div class="min-w-0" style="flex:1">
                            <p class="mp-row__title">Current manuscript <span class="mp-badge" style="margin-left:.35rem">Current</span></p>
                            <p class="mp-row__meta">{{ basename($submission->document_path) }}</p>
                        </div>
                        <a href="{{ route('reviewer.reviews.download', $submission) }}" class="admin-btn admin-btn-secondary" style="padding:.4rem .7rem;font-size:.74rem">Download</a>
                    </div>
                @else
                    <p class="mp-empty">No files recorded yet.</p>
                @endif
            @endforelse
        </div>
    </section>

    <section class="mp-card">
        <div class="mp-card__head">
            <div>
                <h2 class="mp-card__title">Full timeline</h2>
                <p class="mp-card__desc">Editorial and review events for this manuscript, oldest first.</p>
            </div>
        </div>
        <div class="mp-card__body">
            <div class="mp-timeline">
                @forelse($submission->timelines->sortBy('created_at') as $event)
                    @php
                        $meta = is_array($event->metadata) ? $event->metadata : [];
                        $decision = $meta['decision'] ?? null;
                        $comment = $meta['comment'] ?? null;
                        $rejection = $meta['rejection_reason'] ?? null;
                    @endphp
                    <div class="mp-timeline__item">
                        <span class="mp-timeline__dot"></span>
                        <div>
                            <p style="margin:0;font-size:.86rem;font-weight:750;color:var(--ink)">
                                {{ str_replace('_', ' ', $event->event) }}
                                @if($decision)
                                    — {{ str_replace('_', ' ', $decision) }}
                                @endif
                            </p>
                            <p style="margin:.2rem 0 0;font-size:.74rem;color:var(--muted)">
                                {{ $event->created_at?->format('M j, Y g:i A') }}
                                @if($event->user && \App\Support\ReviewType::isOpen($reviewType))
                                    · {{ $event->user->name }}
                                @elseif($event->user && $event->event === 'review_decision')
                                    · Reviewer
                                @endif
                            </p>
                            @if($comment)
                                <p style="margin:.45rem 0 0;padding:.65rem .75rem;border-radius:.65rem;background:#f8fafc;border:1px solid #e2e8f0;font-size:.78rem;color:#475569;white-space:pre-wrap">{{ $comment }}</p>
                            @endif
                            @if($rejection)
                                <p style="margin:.45rem 0 0;padding:.65rem .75rem;border-radius:.65rem;background:#fef2f2;border:1px solid #fecaca;font-size:.78rem;color:#991b1b;white-space:pre-wrap">{{ $rejection }}</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="mp-empty">No timeline events yet.</p>
                @endforelse
            </div>
        </div>
    </section>

    @if($canDecide)
        <section class="mp-card">
            <div class="mp-card__head">
                <div>
                    <h2 class="mp-card__title">Decision</h2>
                    <p class="mp-card__desc">Record your recommendation for the editors. Accepting sends this to editors for publish — no reassignment needed after a resubmission.</p>
                </div>
            </div>
            <form
                method="POST"
                action="{{ route('reviewer.reviews.decide', $submission) }}"
                class="mp-card__body"
                style="display:grid;gap:.9rem"
                x-data="{ submitting: false, decision: @js(old('decision', '')) }"
                @submit="if (submitting) { $event.preventDefault() } else { submitting = true }"
            >
                @csrf
                <div class="mp-field">
                    <x-form-label for="decision" field="review.decision">Decision</x-form-label>
                    <select id="decision" name="decision" required class="mp-select" x-model="decision">
                        <option value="">Select</option>
                        <option value="accept" @selected(old('decision') === 'accept')>Accept</option>
                        <option value="reject" @selected(old('decision') === 'reject')>Reject</option>
                        <option value="revision_requested" @selected(old('decision') === 'revision_requested')>Request revision</option>
                    </select>
                    @error('decision')<p class="mp-error">{{ $message }}</p>@enderror
                </div>
                <div class="mp-field">
                    <x-form-label for="comment" field="review.comment">Comment</x-form-label>
                    <textarea id="comment" name="comment" rows="4" class="mp-textarea">{{ old('comment') }}</textarea>
                    @error('comment')<p class="mp-error">{{ $message }}</p>@enderror
                </div>
                <div class="mp-field" x-show="decision === 'reject'" x-cloak>
                    <x-form-label for="rejection_reason" field="review.rejection_reason">Rejection reason</x-form-label>
                    <textarea id="rejection_reason" name="rejection_reason" rows="3" class="mp-textarea">{{ old('rejection_reason') }}</textarea>
                    @error('rejection_reason')<p class="mp-error">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="mp-btn mp-btn-primary" style="width:fit-content" :disabled="submitting">
                    <span class="mp-spinner" x-show="submitting" x-cloak></span>
                    <span x-text="submitting ? 'Submitting…' : 'Submit decision'"></span>
                </button>
            </form>
        </section>
    @else
        <section class="mp-card">
            <div class="mp-card__body">
                <p class="mp-empty" style="padding:0">This assignment is not currently awaiting a decision. You can still review the full history above.</p>
            </div>
        </section>
    @endif
</div>
@endsection
