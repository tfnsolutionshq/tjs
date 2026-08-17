@props([
    'journal',
    'canRequest' => false,
    'reason' => null,
    'pending' => null,
    'latest' => null,
    'compact' => false,
])

@php
    $status = $pending?->status ?? $latest?->status;
@endphp

<div @class([
    'rr-panel',
    'rr-panel--compact' => $compact,
])>
    <div class="rr-panel__head">
        <h3 class="rr-panel__title">{{ $compact ? 'Volunteer as reviewer' : 'Request reviewer access' }}</h3>
        @unless($compact)
            <p class="rr-panel__desc">Journal members can ask to join the peer-review team for {{ $journal->title }}.</p>
        @endunless
    </div>

    @if($pending)
        <div class="rr-panel__status rr-panel__status--pending">
            <strong>Request pending</strong>
            <span>Submitted {{ $pending->created_at?->diffForHumans() }}. An editor will review your application.</span>
        </div>
        @if($pending->message)
            <p class="rr-panel__message">{{ $pending->message }}</p>
        @endif
        <form method="POST" action="{{ route('reviewer-requests.destroy', $journal) }}" class="rr-panel__actions">
            @csrf
            @method('DELETE')
            <button type="submit" class="rr-panel__btn rr-panel__btn--ghost">Withdraw request</button>
        </form>
    @elseif($canRequest)
        <form method="POST" action="{{ route('reviewer-requests.store', $journal) }}" class="rr-panel__form">
            @csrf
            <label class="rr-panel__label" for="reviewer-request-message-{{ $journal->id }}">Optional note to the editorial team</label>
            <textarea
                id="reviewer-request-message-{{ $journal->id }}"
                name="message"
                rows="{{ $compact ? 2 : 3 }}"
                class="rr-panel__textarea"
                placeholder="Briefly describe your expertise or areas you can review…"
            >{{ old('message') }}</textarea>
            @error('reviewer_request')<p class="rr-panel__error">{{ $message }}</p>@enderror
            @error('message')<p class="rr-panel__error">{{ $message }}</p>@enderror
            <button type="submit" class="rr-panel__btn rr-panel__btn--primary">Request to be a reviewer</button>
        </form>
    @else
        <div class="rr-panel__status rr-panel__status--muted">
            @if($status === \App\Models\JournalReviewerRequest::STATUS_APPROVED)
                <strong>You are approved</strong>
                <span>You can access assigned reviews from your dashboard.</span>
            @elseif($status === \App\Models\JournalReviewerRequest::STATUS_REJECTED)
                <strong>Previous request declined</strong>
                <span>{{ $latest?->admin_note ?: ($reason ?: 'Contact the journal team if you would like to reapply.') }}</span>
            @else
                <span>{{ $reason ?: 'You cannot request reviewer access for this journal right now.' }}</span>
            @endif
        </div>
    @endif
</div>

@once
<style>
    .rr-panel {
        border: 1px solid #dbeafe;
        border-radius: .95rem;
        background: linear-gradient(180deg, #f8fbff 0%, #fff 100%);
        padding: 1rem 1.05rem;
    }
    .rr-panel--compact { padding: .9rem; }
    .rr-panel__head { margin-bottom: .75rem; }
    .rr-panel__title {
        margin: 0;
        font-size: .92rem;
        font-weight: 800;
        color: var(--ink, #0f172a);
    }
    .rr-panel__desc {
        margin: .35rem 0 0;
        font-size: .78rem;
        line-height: 1.45;
        color: var(--muted, #64748b);
    }
    .rr-panel__status {
        display: grid;
        gap: .25rem;
        padding: .7rem .8rem;
        border-radius: .75rem;
        font-size: .78rem;
        line-height: 1.45;
        color: #475569;
    }
    .rr-panel__status strong {
        font-size: .82rem;
        color: #0f172a;
    }
    .rr-panel__status--pending {
        border: 1px solid #fde68a;
        background: #fffbeb;
        color: #92400e;
    }
    .rr-panel__status--pending strong { color: #92400e; }
    .rr-panel__status--muted {
        border: 1px solid #e2e8f0;
        background: #f8fafc;
    }
    .rr-panel__message {
        margin: .55rem 0 0;
        padding: .65rem .75rem;
        border-radius: .65rem;
        background: #fff;
        border: 1px solid #e2e8f0;
        font-size: .78rem;
        color: #475569;
        white-space: pre-wrap;
    }
    .rr-panel__form { display: grid; gap: .55rem; }
    .rr-panel__label {
        font-size: .74rem;
        font-weight: 700;
        color: #475569;
    }
    .rr-panel__textarea {
        width: 100%;
        border: 1px solid #e2e8f0;
        border-radius: .65rem;
        padding: .65rem .75rem;
        font: inherit;
        font-size: .82rem;
        resize: vertical;
        min-height: 4.5rem;
    }
    .rr-panel__textarea:focus {
        outline: none;
        border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    }
    .rr-panel__error {
        margin: 0;
        font-size: .74rem;
        font-weight: 600;
        color: #b91c1c;
    }
    .rr-panel__actions { margin-top: .65rem; }
    .rr-panel__btn {
        appearance: none;
        border: 0;
        border-radius: .65rem;
        padding: .62rem .95rem;
        font: inherit;
        font-size: .8rem;
        font-weight: 800;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .rr-panel__btn--primary {
        background: #2563eb;
        color: #fff;
        box-shadow: 0 8px 18px rgba(37,99,235,.22);
    }
    .rr-panel__btn--primary:hover { filter: brightness(1.05); }
    .rr-panel__btn--ghost {
        background: #fff;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }
    .rr-panel__btn--ghost:hover { color: #334155; border-color: #cbd5e1; }
</style>
@endonce
