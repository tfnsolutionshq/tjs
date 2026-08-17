@extends('layouts.member')

@section('title', $submission->title.' | My submissions')
@section('page_title', 'Submission')
@section('page_subtitle', $submission->journal?->title)

@section('page_actions')
    <a href="{{ route('author.submissions.index') }}" class="admin-btn admin-btn-secondary">All submissions</a>
@endsection

@section('content')
<style>
    .sd { display:grid; gap:1rem; max-width:72rem; }
    @media (min-width:980px) {
        .sd { grid-template-columns:minmax(0,1fr) 18rem; align-items:start; }
        .sd-side { position:sticky; top:1rem; }
    }
    .sd-main, .sd-side { display:grid; gap:1rem; min-width:0; }
    .sd-hero {
        background:#fff; border:1px solid var(--line); border-radius:1.05rem; padding:1.25rem 1.3rem;
        box-shadow:0 8px 24px rgba(15,23,42,.035);
    }
    .sd-hero__title { margin:.55rem 0 0; font-size:1.35rem; font-weight:800; color:var(--ink); letter-spacing:-.02em; line-height:1.3; }
    .sd-meta { margin:.45rem 0 0; font-size:.82rem; color:var(--muted); display:flex; flex-wrap:wrap; gap:.35rem .7rem; }
    .sd-label { margin:0; font-size:.72rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--muted); }
    .sd-value { margin:.25rem 0 0; font-size:.9rem; color:#334155; line-height:1.5; }
    .sd-note { padding:.85rem 1rem; border-radius:.85rem; }
    .sd-note--info { background:#eff6ff; border:1px solid #bfdbfe; }
    .sd-note--danger { background:#fef2f2; border:1px solid #fecaca; }
    .sd-note--warn { background:#fff7ed; border:1px solid #fed7aa; }
    .sd-kv { display:grid; gap:.55rem; }
    .sd-kv__row { display:flex; justify-content:space-between; gap:.75rem; font-size:.84rem; }
    .sd-kv__row span:first-child { color:var(--muted); }
    .sd-kv__row span:last-child { font-weight:700; color:var(--ink); text-align:right; }
    .sd-file {
        display:flex; align-items:center; gap:.65rem; padding:.75rem .9rem; border-radius:.85rem;
        background:#f8fafc; border:1px solid var(--line); text-decoration:none; color:inherit;
    }
    .sd-file:hover { border-color:#93c5fd; }
    .sd-file__icon {
        width:2.1rem; height:2.1rem; border-radius:.65rem; background:#e8f1fc; color:#1d4ed8;
        display:inline-flex; align-items:center; justify-content:center; flex-shrink:0;
    }
</style>

@php
    $canRevise = in_array($submission->status, ['revision_requested', 'resubmitted'], true);
    $reviewType = $submission->effectiveReviewType();
@endphp

<div class="sd">
    <div class="sd-main">
        @if (session('status'))
            <div class="mp-flash">{{ session('status') }}</div>
        @endif

        <header class="sd-hero">
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem">
                <span class="mp-badge mp-badge--{{ $submission->status }}">{{ str_replace('_', ' ', $submission->status) }}</span>
                <span class="mp-badge">{{ \App\Support\ReviewType::label($reviewType) }}</span>
                @if($submission->journal)
                    <a href="{{ route('journals.show', $submission->journal) }}" style="font-size:.84rem;font-weight:700;color:#1d4ed8;text-decoration:none" target="_blank" rel="noopener">
                        {{ $submission->journal->title }} ↗
                    </a>
                @endif
            </div>
            <h1 class="sd-hero__title">{{ $submission->title }}</h1>
            <p class="sd-meta">
                <span>Submitted {{ optional($submission->created_at)->format('M j, Y') }}</span>
                @if($submission->updated_at && ! $submission->updated_at->eq($submission->created_at))
                    <span>· Updated {{ $submission->updated_at->diffForHumans() }}</span>
                @endif
            </p>
        </header>

        @if($submission->review_comment)
            <div class="sd-note sd-note--info">
                <p style="margin:0;font-weight:700;color:#1e40af">Editorial / reviewer comment</p>
                <p style="margin:.35rem 0 0;color:#1e3a8a;line-height:1.5">{{ $submission->review_comment }}</p>
            </div>
        @endif

        @if($submission->rejection_reason)
            <div class="sd-note sd-note--danger">
                <p style="margin:0;font-weight:700;color:#991b1b">Rejection reason</p>
                <p style="margin:.35rem 0 0;color:#7f1d1d;line-height:1.5">{{ $submission->rejection_reason }}</p>
            </div>
        @endif

        @if($canRevise && $submission->status === 'revision_requested')
            <div class="sd-note sd-note--warn">
                <p style="margin:0;font-weight:700;color:#9a3412">Revision requested</p>
                <p style="margin:.35rem 0 0;color:#9a3412;line-height:1.5;font-size:.88rem">Upload an updated manuscript below. Your submission will move to resubmitted.</p>
            </div>
        @endif

        <section class="mp-card">
            <div class="mp-card__head">
                <h2 class="mp-card__title">Manuscript details</h2>
            </div>
            <div class="mp-card__body" style="display:grid;gap:1rem">
                @if($submission->category)
                    <div>
                        <p class="sd-label">Category</p>
                        <p class="sd-value">{{ $submission->category }}</p>
                    </div>
                @endif
                @if($submission->keywords)
                    <div>
                        <p class="sd-label">Keywords</p>
                        <p class="sd-value">{{ $submission->keywords }}</p>
                    </div>
                @endif
                @if($submission->abstract)
                    <div>
                        <p class="sd-label">Abstract</p>
                        <p class="sd-value" style="white-space:pre-wrap;color:var(--muted)">{{ $submission->abstract }}</p>
                    </div>
                @else
                    <p style="margin:0;font-size:.88rem;color:var(--muted)">No abstract provided.</p>
                @endif
            </div>
        </section>

        @if($canRevise)
            <section class="mp-card">
                <div class="mp-card__head">
                    <div>
                        <h2 class="mp-card__title">Upload revision</h2>
                        <p class="mp-card__desc">Replace the manuscript file and optionally add notes for editors.</p>
                    </div>
                </div>
                <form
                    method="POST"
                    action="{{ route('author.submissions.resubmit', $submission) }}"
                    enctype="multipart/form-data"
                    class="mp-card__body"
                    style="display:grid;gap:.9rem"
                    x-data="{ submitting: false, fileName: '' }"
                    @submit="if (submitting) { $event.preventDefault() } else { submitting = true }"
                >
                    @csrf
                    <div class="mp-field">
                        <x-form-label for="document" field="submission.revision_file" required>Revised document</x-form-label>
                        <input
                            id="document"
                            name="document"
                            type="file"
                            required
                            accept=".doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                            class="mp-file"
                            @change="fileName = $event.target.files?.[0]?.name || ''"
                        >
                        <p class="mp-hint">DOC or DOCX only · max 50MB · PDFs are not accepted</p>
                        <p class="mp-hint" x-show="fileName" x-cloak x-text="fileName"></p>
                        @error('document')<p class="mp-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="mp-field">
                        <x-form-label for="notes" field="submission.revision_notes">Notes for editors</x-form-label>
                        <textarea id="notes" name="notes" rows="3" class="mp-textarea" placeholder="Summarize what changed…">{{ old('notes') }}</textarea>
                        @error('notes')<p class="mp-error">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="mp-btn mp-btn-primary" style="width:fit-content" :disabled="submitting">
                        <span class="mp-spinner" x-show="submitting" x-cloak></span>
                        <span x-text="submitting ? 'Uploading…' : 'Resubmit manuscript'"></span>
                    </button>
                </form>
            </section>
        @endif

        @if($submission->timelines->isNotEmpty())
            <section class="mp-card">
                <div class="mp-card__head">
                    <h2 class="mp-card__title">Timeline</h2>
                </div>
                <div class="mp-card__body">
                    <div class="mp-timeline">
                        @foreach($submission->timelines->sortByDesc('created_at') as $event)
                            <div class="mp-timeline__item">
                                <span class="mp-timeline__dot"></span>
                                <div>
                                    <p style="margin:0;font-size:.88rem;font-weight:700;color:var(--ink)">{{ str_replace('_', ' ', $event->event) }}</p>
                                    <p style="margin:.15rem 0 0;font-size:.76rem;color:var(--muted)">
                                        {{ optional($event->created_at)->format('M j, Y g:ia') }}
                                        @if($event->user)
                                            · {{ $event->user->name }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    </div>

    <aside class="sd-side">
        <section class="mp-card">
            <div class="mp-card__head">
                <h2 class="mp-card__title">Status</h2>
            </div>
            <div class="mp-card__body">
                <div class="sd-kv">
                    <div class="sd-kv__row">
                        <span>Current</span>
                        <span>{{ str_replace('_', ' ', $submission->status) }}</span>
                    </div>
                    <div class="sd-kv__row">
                        <span>Target issue</span>
                        <span>{{ $submission->issue?->label() ?? '—' }}</span>
                    </div>
                    @if($submission->announcement)
                        <div class="sd-kv__row">
                            <span>Call</span>
                            <span>{{ $submission->announcement->title }}</span>
                        </div>
                    @endif
                    <div class="sd-kv__row">
                        <span>Review type</span>
                        <span>{{ \App\Support\ReviewType::label($reviewType) }}</span>
                    </div>
                    <div class="sd-kv__row">
                        <span>Submitted</span>
                        <span>{{ optional($submission->created_at)->format('M j, Y') }}</span>
                    </div>
                    <div class="sd-kv__row">
                        <span>Last update</span>
                        <span>{{ optional($submission->updated_at)->diffForHumans() }}</span>
                    </div>
                    @if($submission->revisions->isNotEmpty())
                        <div class="sd-kv__row">
                            <span>Revisions</span>
                            <span>{{ $submission->revisions->count() }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        @if($submission->document_path)
            <section class="mp-card">
                <div class="mp-card__head">
                    <h2 class="mp-card__title">Current file</h2>
                </div>
                <div class="mp-card__body">
                    <div class="sd-file">
                        <span class="sd-file__icon">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path stroke-linecap="round" d="M14 3v5h5"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p style="margin:0;font-size:.84rem;font-weight:700;color:var(--ink)">Manuscript on file</p>
                            <p style="margin:.15rem 0 0;font-size:.74rem;color:var(--muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ basename($submission->document_path) }}</p>
                        </div>
                    </div>
                    @if($submission->revisions->isNotEmpty())
                        <p class="mp-hint" style="margin-top:.75rem">{{ $submission->revisions->count() }} revision{{ $submission->revisions->count() === 1 ? '' : 's' }} uploaded</p>
                    @endif
                </div>
            </section>
        @endif
    </aside>
</div>
@endsection
