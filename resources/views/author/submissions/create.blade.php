@extends('layouts.member')

@section('title', 'New submission | '.config('tjs.name'))
@section('page_title', 'New submission')
@section('page_subtitle', 'Submit to an open call for submissions')

@section('page_actions')
    <a href="{{ route('author.submissions.index') }}" class="admin-btn admin-btn-secondary">My submissions</a>
@endsection

@section('content')
@php
    $upcomingCalls = $upcomingCalls ?? collect();
    $recentlyClosedCalls = $recentlyClosedCalls ?? collect();
@endphp

<style>
    .ns { display:grid; gap:1rem; max-width:72rem; }
    @media (min-width:1080px) {
        .ns { grid-template-columns:minmax(0,1fr) 17.5rem; align-items:start; }
        .ns-side { position:sticky; top:1rem; }
    }
    .ns-main, .ns-side { display:grid; gap:1rem; min-width:0; }
    .ns-steps { display:flex; flex-wrap:wrap; gap:.45rem; }
    .ns-step {
        display:inline-flex; align-items:center; gap:.4rem;
        padding:.4rem .7rem; border-radius:999px; font-size:.74rem; font-weight:700;
        background:#f1f5f9; color:#64748b;
    }
    .ns-step span {
        width:1.2rem; height:1.2rem; border-radius:999px; display:inline-flex;
        align-items:center; justify-content:center; background:#2f7de1; color:#fff; font-size:.68rem;
    }
    .ns-step.is-on { background:#e8f1fc; color:#1d4ed8; }
    .ns-two { display:grid; gap:.95rem; }
    @media (min-width:720px) { .ns-two { grid-template-columns:1fr 1fr; } }
    .ns-file {
        border:1px dashed #cbd5e1; border-radius:.9rem; background:#f8fafc; padding:1rem;
        transition:border-color .15s ease, background .15s ease;
    }
    .ns-file.is-ready { border-color:#86efac; background:#f0fdf4; }
    .ns-file.is-bad { border-color:#fca5a5; background:#fef2f2; }
    .ns-file__name { margin:.55rem 0 0; font-size:.82rem; font-weight:700; color:#15803d; }
    .ns-file__rules {
        display:flex; flex-wrap:wrap; gap:.4rem; margin-top:.7rem;
    }
    .ns-chip {
        display:inline-flex; align-items:center; gap:.3rem;
        padding:.28rem .55rem; border-radius:999px; font-size:.7rem; font-weight:700;
        background:#f1f5f9; color:#475569;
    }
    .ns-chip--ok { background:#ecfdf5; color:#047857; }
    .ns-chip--no { background:#fef2f2; color:#b91c1c; }
    .ns-tip { margin:0; font-size:.8rem; color:var(--muted); line-height:1.5; }
    .ns-tip + .ns-tip { margin-top:.65rem; }
    .ns-tip strong { color:var(--ink); }
    .ns-call-link {
        font-size:.84rem; font-weight:700; color:#1d4ed8; text-decoration:none;
        display:block; padding:.35rem 0;
    }
    .ns-call-link:hover { text-decoration:underline; }
    .ns-empty {
        display:grid; gap:1rem; max-width:72rem;
    }
    @media (min-width:960px) {
        .ns-empty { grid-template-columns:minmax(0,1.2fr) .8fr; align-items:start; }
    }
    .ns-hero {
        position:relative; overflow:hidden; border-radius:1.15rem; padding:1.6rem 1.4rem;
        background:linear-gradient(135deg, #0b1220 0%, #132a52 55%, #1d4ed8 120%);
        color:#fff; box-shadow:0 18px 40px rgba(15,23,42,.18);
    }
    .ns-hero::after {
        content:''; position:absolute; inset:auto -10% -40% auto; width:18rem; height:18rem;
        border-radius:999px; background:rgba(147,197,253,.22); pointer-events:none;
    }
    .ns-hero__icon {
        width:2.6rem; height:2.6rem; border-radius:.85rem; display:inline-flex;
        align-items:center; justify-content:center; background:rgba(255,255,255,.12);
        border:1px solid rgba(255,255,255,.14); margin-bottom:.9rem;
    }
    .ns-hero__icon svg { width:1.2rem; height:1.2rem; }
    .ns-hero__title { margin:0; font-size:1.35rem; font-weight:800; letter-spacing:-.02em; }
    .ns-hero__text { margin:.55rem 0 0; max-width:34rem; font-size:.9rem; line-height:1.55; color:rgba(255,255,255,.75); }
    .ns-hero__actions { display:flex; flex-wrap:wrap; gap:.55rem; margin-top:1.15rem; position:relative; z-index:1; }
    .ns-hero__btn {
        display:inline-flex; align-items:center; gap:.4rem; padding:.68rem 1rem; border-radius:.7rem;
        font:inherit; font-size:.86rem; font-weight:700; text-decoration:none; border:0; cursor:pointer;
    }
    .ns-hero__btn--primary { background:#fff; color:#0f172a; }
    .ns-hero__btn--ghost { background:rgba(255,255,255,.1); color:#fff; border:1px solid rgba(255,255,255,.18); }
    .ns-list-card {
        background:#fff; border:1px solid var(--line); border-radius:1.05rem;
        box-shadow:0 8px 24px rgba(15,23,42,.035); overflow:hidden;
    }
    .ns-list-card__head { padding:1rem 1.1rem .25rem; }
    .ns-list-card__title { margin:0; font-size:.95rem; font-weight:800; color:var(--ink); }
    .ns-list-card__desc { margin:.25rem 0 0; font-size:.78rem; color:var(--muted); }
    .ns-list-card__body { padding:.35rem 1.1rem 1.05rem; }
    .ns-call {
        display:flex; gap:.75rem; align-items:flex-start; justify-content:space-between;
        padding:.85rem 0; border-top:1px solid #f1f5f9; text-decoration:none; color:inherit;
    }
    .ns-call:first-child { border-top:0; }
    .ns-call:hover .ns-call__title { color:#1d4ed8; }
    .ns-call__title { margin:0; font-size:.88rem; font-weight:800; color:var(--ink); line-height:1.35; }
    .ns-call__meta { margin:.25rem 0 0; font-size:.74rem; color:var(--muted); line-height:1.4; }
    .ns-badge {
        flex-shrink:0; display:inline-flex; align-items:center; border-radius:999px;
        padding:.22rem .55rem; font-size:.68rem; font-weight:800;
    }
    .ns-badge--soon { background:#fef3c7; color:#92400e; }
    .ns-badge--closed { background:#f1f5f9; color:#64748b; }
    .ns-guide {
        display:grid; gap:.65rem; margin-top:0;
    }
    @media (min-width:720px) {
        .ns-guide { grid-template-columns:repeat(3,minmax(0,1fr)); }
    }
    .ns-guide__item {
        background:#fff; border:1px solid var(--line); border-radius:.95rem; padding:.95rem 1rem;
        box-shadow:0 8px 24px rgba(15,23,42,.035);
    }
    .ns-guide__n {
        width:1.55rem; height:1.55rem; border-radius:999px; display:inline-flex;
        align-items:center; justify-content:center; background:#eff6ff; color:#1d4ed8;
        font-size:.72rem; font-weight:800; margin-bottom:.55rem;
    }
    .ns-guide__title { margin:0; font-size:.84rem; font-weight:800; color:var(--ink); }
    .ns-guide__text { margin:.3rem 0 0; font-size:.76rem; color:var(--muted); line-height:1.45; }
    .ns-alert {
        display:flex; gap:.7rem; align-items:flex-start; padding:.85rem .95rem;
        border-radius:.85rem; border:1px solid #dbeafe; background:linear-gradient(135deg,#f8fbff,#eff6ff);
        margin-bottom:1rem;
    }
    .ns-alert__icon {
        flex-shrink:0; width:2rem; height:2rem; border-radius:.65rem; display:inline-flex;
        align-items:center; justify-content:center; background:#dbeafe; color:#2563eb;
    }
    .ns-alert__icon svg { width:.95rem; height:.95rem; }
    .ns-alert__title { margin:0; font-size:.84rem; font-weight:800; color:var(--ink); }
    .ns-alert__text { margin:.25rem 0 0; font-size:.78rem; color:#475569; line-height:1.45; }
    .ns-guide-panel { border:1px solid #dbeafe; background:linear-gradient(135deg,#f8fbff,#eff6ff); }
    .ns-guide-panel .mp-card__head { border-bottom:1px solid #dbeafe; }
    .ns-guide-panel .mp-card__title { color:#1e3a8a; }
    .ns-guide-panel .mp-card__desc { color:#475569; }
    .ns-guide-prose { font-size:.84rem; line-height:1.6; color:#334155; }
    .ns-guide-prose :where(p,ul,ol) { margin:.55rem 0; }
    .ns-guide-prose :where(h2,h3,h4) { margin:1rem 0 .45rem; font-size:.92rem; font-weight:800; color:var(--ink); }
    .ns-fees { display:grid; gap:.85rem; margin-top:.15rem; }
    .ns-fee-group__label {
        margin:0 0 .45rem; font-size:.68rem; font-weight:800; letter-spacing:.07em;
        text-transform:uppercase; color:#64748b;
    }
    .ns-fee-list { display:grid; gap:.5rem; }
    .ns-fee {
        padding:.72rem .82rem; border:1px solid #e2e8f0; border-radius:.75rem; background:#fff;
    }
    .ns-fee__name { margin:0; font-size:.84rem; font-weight:800; color:var(--ink); }
    .ns-fee__amount { color:#1d4ed8; }
    .ns-fee__desc { margin:.28rem 0 0; font-size:.74rem; color:var(--muted); line-height:1.45; }
</style>

@if ($errors->any())
    <div class="mp-flash" style="background:#fef2f2;border-color:#fecaca;color:#b91c1c">
        Please fix the highlighted fields before submitting.
    </div>
@endif

@if($openCalls->isEmpty())
    <div class="ns-empty">
        <div>
            <section class="ns-hero">
                <div class="ns-hero__icon" aria-hidden="true">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                </div>
                <h2 class="ns-hero__title">No open calls right now</h2>
                <p class="ns-hero__text">
                    Manuscripts can only be submitted through an active call for submissions tied to a specific issue.
                    Browse journals for announcements, or check back when a new call opens.
                </p>
                <div class="ns-hero__actions">
                    <a href="{{ route('journals.index') }}" class="ns-hero__btn ns-hero__btn--primary">Browse journals</a>
                    <a href="{{ route('author.submissions.index') }}" class="ns-hero__btn ns-hero__btn--ghost">My submissions</a>
                </div>
            </section>

            <div class="ns-guide" style="margin-top:1rem">
                <div class="ns-guide__item">
                    <div class="ns-guide__n">1</div>
                    <p class="ns-guide__title">Find a call</p>
                    <p class="ns-guide__text">Open a journal’s announcements page and look for a Call for submissions.</p>
                </div>
                <div class="ns-guide__item">
                    <div class="ns-guide__n">2</div>
                    <p class="ns-guide__title">Prepare DOC/DOCX</p>
                    <p class="ns-guide__text">Upload an editable Word file only — PDFs are not accepted for editorial editing.</p>
                </div>
                <div class="ns-guide__item">
                    <div class="ns-guide__n">3</div>
                    <p class="ns-guide__title">Track review</p>
                    <p class="ns-guide__text">After you submit, follow status and revision requests from My submissions.</p>
                </div>
            </div>
        </div>

        <div style="display:grid;gap:1rem">
            @if($upcomingCalls->isNotEmpty())
                <section class="ns-list-card">
                    <div class="ns-list-card__head">
                        <h3 class="ns-list-card__title">Opening soon</h3>
                        <p class="ns-list-card__desc">Published calls that are not accepting manuscripts yet.</p>
                    </div>
                    <div class="ns-list-card__body">
                        @foreach($upcomingCalls as $call)
                            <a class="ns-call" href="{{ route('journals.announcements.show', [$call->journal, $call]) }}" target="_blank" rel="noopener">
                                <div class="min-w-0">
                                    <p class="ns-call__title">{{ $call->title }}</p>
                                    <p class="ns-call__meta">
                                        {{ $call->journal?->title }}
                                        @if($call->issueLabel()) · {{ $call->issueLabel() }}@endif
                                        · opens {{ $call->opens_at?->format('M j, Y') }}
                                    </p>
                                </div>
                                <span class="ns-badge ns-badge--soon">Soon</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($recentlyClosedCalls->isNotEmpty())
                <section class="ns-list-card">
                    <div class="ns-list-card__head">
                        <h3 class="ns-list-card__title">Recently closed</h3>
                        <p class="ns-list-card__desc">These calls no longer accept new manuscripts.</p>
                    </div>
                    <div class="ns-list-card__body">
                        @foreach($recentlyClosedCalls as $call)
                            <a class="ns-call" href="{{ route('journals.announcements.show', [$call->journal, $call]) }}" target="_blank" rel="noopener">
                                <div class="min-w-0">
                                    <p class="ns-call__title">{{ $call->title }}</p>
                                    <p class="ns-call__meta">
                                        {{ $call->journal?->title }}
                                        @if($call->issueLabel()) · {{ $call->issueLabel() }}@endif
                                        · closed {{ $call->closes_at?->format('M j, Y') }}
                                    </p>
                                </div>
                                <span class="ns-badge ns-badge--closed">Closed</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($upcomingCalls->isEmpty() && $recentlyClosedCalls->isEmpty())
                <section class="ns-list-card">
                    <div class="ns-list-card__body" style="padding:1.25rem 1.1rem">
                        <p style="margin:0;font-size:.88rem;font-weight:750;color:var(--ink)">Looking for updates?</p>
                        <p style="margin:.35rem 0 0;font-size:.8rem;color:var(--muted);line-height:1.45">
                            Visit a journal home page and open <strong>Announcements</strong> to see news and future calls.
                        </p>
                        <a href="{{ route('journals.index') }}" class="mp-btn mp-btn-primary" style="margin-top:.9rem;width:fit-content">Browse journals</a>
                    </div>
                </section>
            @endif
        </div>
    </div>
@else
@php
    $reviewTips = $openCalls->mapWithKeys(fn ($call) => [
        (string) $call->id => \App\Support\ReviewType::authorTip($call->journal?->review_type),
    ]);
    $callJournalMap = $openCalls->mapWithKeys(fn ($call) => [
        (string) $call->id => (string) $call->journal_id,
    ]);
    $initialJournalId = $preselectedCall && $callJournalMap->has($preselectedCall)
        ? $callJournalMap[$preselectedCall]
        : '';
    $feesByJournal = $feesByJournal ?? [];
    $issuePublicationFeeByCall = $issuePublicationFeeByCall ?? [];
    $guidelinesByCall = $guidelinesByCall ?? [];
@endphp

<div class="ns-alert">
    <span class="ns-alert__icon" aria-hidden="true">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
    </span>
    <div>
        <p class="ns-alert__title">{{ $openCalls->count() }} open {{ \Illuminate\Support\Str::plural('call', $openCalls->count()) }} available</p>
        <p class="ns-alert__text">Choose a call, add your manuscript details, then upload a DOC or DOCX file. PDFs are blocked so editors can revise the source document.</p>
    </div>
</div>

<form
    method="POST"
    action="{{ route('author.submissions.store') }}"
    enctype="multipart/form-data"
    class="ns"
    x-data="{
        submitting: false,
        fileName: '',
        fileOk: true,
        selectedCallId: @js((string) $preselectedCall),
        journalId: @js($initialJournalId),
        categoriesByJournal: @js($categoriesByJournal),
        reviewTip: @js(
            $preselectedCall && $reviewTips->has($preselectedCall)
                ? $reviewTips[$preselectedCall]
                : 'Choose an open call to see its review policy.'
        ),
        reviewTips: @js($reviewTips),
        callJournalMap: @js($callJournalMap),
        feesByJournal: @js($feesByJournal),
        issuePublicationFeeByCall: @js($issuePublicationFeeByCall),
        guidelinesByCall: @js($guidelinesByCall),
        get submissionFees() {
            if (!this.journalId) return [];
            return this.feesByJournal[this.journalId] || this.feesByJournal[String(this.journalId)] || [];
        },
        get requiredSubmissionFee() {
            const paid = this.submissionFees.filter((fee) => Number(fee.amount) >= 1);
            if (!paid.length) return null;
            return paid.sort((a, b) => Number(a.amount) - Number(b.amount) || a.name.localeCompare(b.name))[0];
        },
        get requiresSubmissionPayment() {
            return this.requiredSubmissionFee !== null;
        },
        get issuePublicationFee() {
            if (!this.selectedCallId) return null;
            return this.issuePublicationFeeByCall[this.selectedCallId]
                || this.issuePublicationFeeByCall[String(this.selectedCallId)]
                || null;
        },
        get guidelinesHtml() {
            if (!this.selectedCallId) return '';
            return this.guidelinesByCall[this.selectedCallId] || this.guidelinesByCall[String(this.selectedCallId)] || '';
        },
        get showGuidelines() {
            return !!this.selectedCallId && (this.guidelinesHtml || this.submissionFees.length || this.issuePublicationFee);
        },
        get categories() {
            if (!this.journalId) return [];
            return this.categoriesByJournal[this.journalId] || this.categoriesByJournal[String(this.journalId)] || [];
        },
        onFile(e) {
            const file = e.target.files?.[0];
            this.fileName = file ? file.name : '';
            if (!file) { this.fileOk = true; return; }
            const name = (file.name || '').toLowerCase();
            this.fileOk = name.endsWith('.doc') || name.endsWith('.docx');
            if (!this.fileOk) {
                e.target.value = '';
                this.fileName = '';
            }
        },
        onCallSelected(detail) {
            this.selectedCallId = detail?.id || '';
            this.journalId = detail?.journal_id || this.callJournalMap[this.selectedCallId] || '';
            this.reviewTip = this.reviewTips[this.selectedCallId] || 'Choose an open call to see its review policy.';
        }
    }"
    @submit="if (submitting) { $event.preventDefault() } else { submitting = true }"
>
    @csrf

    <div class="ns-main">
        <div class="ns-steps" aria-hidden="true">
            <div class="ns-step is-on"><span>1</span> Call &amp; title</div>
            <div class="ns-step is-on"><span>2</span> Abstract</div>
            <div class="ns-step is-on"><span>3</span> Upload file</div>
        </div>

        <section class="mp-card">
            <div class="mp-card__head">
                <div>
                    <h2 class="mp-card__title">Call for submissions</h2>
                    <p class="mp-card__desc">Select the open call and issue you are submitting to.</p>
                </div>
            </div>
            <div class="mp-card__body" style="display:grid;gap:.95rem">
                <div class="mp-field">
                    <x-form-label for="announcement_id" field="submission.announcement_id" required>Open call</x-form-label>
                    <x-call-for-submission-picker
                        :calls="$openCalls"
                        :value="$preselectedCall"
                        required
                        @call-selected="onCallSelected($event.detail)"
                    />
                    @error('announcement_id')<p class="mp-error">{{ $message }}</p>@enderror
                </div>

                <div class="mp-field">
                    <x-form-label for="title" field="submission.title" required>Manuscript title</x-form-label>
                    <input id="title" name="title" type="text" required value="{{ old('title') }}" class="mp-input" placeholder="Full article title" autofocus>
                    @error('title')<p class="mp-error">{{ $message }}</p>@enderror
                </div>

                <div class="mp-field" x-show="requiresSubmissionPayment" x-cloak>
                    <x-form-label field="submission.fee">Submission fee</x-form-label>
                    <div class="ns-fee ns-fee--required" style="margin-top:.15rem">
                        <p class="ns-fee__name">
                            <span x-text="requiredSubmissionFee?.name"></span>
                            <span class="ns-fee__amount" x-text="requiredSubmissionFee ? ` — ${Number(requiredSubmissionFee.amount).toLocaleString()} ${requiredSubmissionFee.currency}` : ''"></span>
                        </p>
                        <p class="mp-hint" style="margin-top:.35rem">This journal requires payment when you submit to this call. After upload, you will be redirected to Paystack to complete checkout before editors receive your manuscript.</p>
                    </div>
                    <input type="hidden" name="journal_fee_id" :value="requiredSubmissionFee?.id || ''">
                </div>
            </div>
        </section>

        <section class="mp-card ns-guide-panel" x-show="showGuidelines" x-cloak>
            <div class="mp-card__head">
                <div>
                    <h2 class="mp-card__title">Submission guidelines</h2>
                    <p class="mp-card__desc">Requirements and fees for the selected call.</p>
                </div>
            </div>
            <div class="mp-card__body" style="display:grid;gap:1rem">
                <div class="ns-guide-prose tjs-prose" x-show="guidelinesHtml" x-html="guidelinesHtml"></div>

                <div class="ns-fees" x-show="submissionFees.length || issuePublicationFee">
                    <div x-show="submissionFees.length">
                        <p class="ns-fee-group__label">Submission fees</p>
                        <div class="ns-fee-list">
                            <template x-for="fee in submissionFees" :key="'sub-' + fee.id">
                                <article class="ns-fee">
                                    <p class="ns-fee__name">
                                        <span x-text="fee.name"></span>
                                        <span class="ns-fee__amount" x-text="` — ${fee.amount.toLocaleString()} ${fee.currency}`"></span>
                                    </p>
                                    <p class="ns-fee__desc" x-show="fee.description" x-text="fee.description"></p>
                                </article>
                            </template>
                        </div>
                    </div>
                    <div x-show="issuePublicationFee">
                        <p class="ns-fee-group__label">Publication fee (APC)</p>
                        <div class="ns-fee-list">
                            <article class="ns-fee">
                                <p class="ns-fee__name">
                                    <span x-text="issuePublicationFee.name"></span>
                                    <span class="ns-fee__amount" x-text="` — ${issuePublicationFee.amount.toLocaleString()} ${issuePublicationFee.currency}`"></span>
                                </p>
                                <p class="ns-fee__desc">Due after review and acceptance, before publication in the issue.</p>
                                <p class="ns-fee__desc" x-show="issuePublicationFee.description" x-text="issuePublicationFee.description"></p>
                            </article>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mp-card">
            <div class="mp-card__head">
                <div>
                    <h2 class="mp-card__title">Abstract &amp; details</h2>
                    <p class="mp-card__desc">Help editors and reviewers understand the work at a glance.</p>
                </div>
            </div>
            <div class="mp-card__body" style="display:grid;gap:.95rem">
                <div class="mp-field">
                    <x-form-label for="abstract" field="submission.abstract">Abstract</x-form-label>
                    <x-rich-text
                        id="abstract"
                        name="abstract"
                        :value="old('abstract')"
                        placeholder="Summarize the problem, methods, and key findings…"
                        :rows="7"
                    />
                    @error('abstract')<p class="mp-error">{{ $message }}</p>@enderror
                </div>

                <div class="ns-two">
                    <div class="mp-field">
                        <x-form-label for="category" field="submission.category">Category</x-form-label>
                        <select id="category" name="category" class="mp-select" :disabled="!journalId || categories.length === 0">
                            <option value="">Select category (optional)</option>
                            <template x-for="category in categories" :key="category.id">
                                <option :value="category.name" x-text="category.name" :selected="category.name === @js(old('category'))"></option>
                            </template>
                        </select>
                        <p class="mp-hint" x-show="journalId && categories.length === 0" x-cloak>This journal has no active categories yet.</p>
                        <p class="mp-hint" x-show="!journalId" x-cloak>Choose a call to load that journal’s categories.</p>
                        @error('category')<p class="mp-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="mp-field">
                        <x-form-label for="keywords" field="submission.keywords">Keywords</x-form-label>
                        <input id="keywords" name="keywords" type="text" value="{{ old('keywords') }}" class="mp-input" placeholder="Comma-separated">
                    </div>
                </div>
            </div>
        </section>

        <section class="mp-card">
            <div class="mp-card__head">
                <div>
                    <h2 class="mp-card__title">Manuscript file</h2>
                    <p class="mp-card__desc">Upload an editable Word document for editorial and peer review.</p>
                </div>
            </div>
            <div class="mp-card__body" style="display:grid;gap:.95rem">
                <div class="mp-field">
                    <x-form-label for="document" field="submission.document" required>File</x-form-label>
                    <div class="ns-file" :class="{ 'is-ready': fileName, 'is-bad': !fileOk && !fileName }">
                        <input
                            id="document"
                            name="document"
                            type="file"
                            required
                            accept=".doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                            class="mp-file"
                            style="border:0;background:transparent;padding:0"
                            @change="onFile($event)"
                        >
                        <p class="ns-file__name" x-show="fileName" x-cloak x-text="fileName"></p>
                        <div class="ns-file__rules" x-show="!fileName">
                            <span class="ns-chip ns-chip--ok">DOC / DOCX allowed</span>
                            <span class="ns-chip ns-chip--no">PDF not accepted</span>
                            <span class="ns-chip">Max 50MB</span>
                        </div>
                        <p class="mp-hint" style="color:#b91c1c" x-show="!fileOk" x-cloak>Please choose a .doc or .docx file. PDFs cannot be submitted.</p>
                    </div>
                    @error('document')<p class="mp-error">{{ $message }}</p>@enderror
                </div>

                <div style="display:flex;flex-wrap:wrap;gap:.55rem;padding-top:.15rem">
                    <button type="submit" class="mp-btn mp-btn-primary" :disabled="submitting" :aria-busy="submitting">
                        <span class="mp-spinner" x-show="submitting" x-cloak></span>
                        <span x-text="submitting ? 'Submitting…' : 'Submit manuscript'"></span>
                    </button>
                    <a href="{{ route('author.submissions.index') }}" class="mp-btn mp-btn-secondary">Cancel</a>
                </div>
            </div>
        </section>
    </div>

    <aside class="ns-side">
        <section class="mp-card">
            <div class="mp-card__head">
                <h2 class="mp-card__title">Before you submit</h2>
            </div>
            <div class="mp-card__body">
                <p class="ns-tip"><strong>Issue-specific.</strong> Every submission must be tied to an open call and target issue.</p>
                <p class="ns-tip"><strong>Review policy.</strong> <span x-text="reviewTip"></span></p>
                <p class="ns-tip"><strong>Editable file.</strong> Use DOC or DOCX so editors can request changes without PDF lock-in.</p>
                <p class="ns-tip"><strong>After submit.</strong> Track status and upload revisions from My submissions.</p>
            </div>
        </section>

        <section class="mp-card">
            <div class="mp-card__head">
                <h2 class="mp-card__title">Open calls</h2>
            </div>
            <div class="mp-card__body" style="display:grid;gap:.15rem">
                @foreach($openCalls->take(5) as $call)
                    <a href="{{ route('journals.announcements.show', [$call->journal, $call]) }}" class="ns-call-link" target="_blank" rel="noopener">
                        {{ $call->title }} ↗
                    </a>
                @endforeach
            </div>
        </section>
    </aside>
</form>
@endif
@endsection
