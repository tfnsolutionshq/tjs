@extends('layouts.member')

@section('title', 'New submission | '.config('tjs.name'))
@section('page_title', 'New submission')
@section('page_subtitle', 'Send a manuscript to an active journal for peer review')

@section('page_actions')
    <a href="{{ route('author.submissions.index') }}" class="admin-btn admin-btn-secondary">My submissions</a>
@endsection

@section('content')
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
    .ns-file__name { margin:.55rem 0 0; font-size:.82rem; font-weight:700; color:#15803d; }
    .ns-tip { margin:0; font-size:.8rem; color:var(--muted); line-height:1.5; }
    .ns-tip + .ns-tip { margin-top:.65rem; }
    .ns-tip strong { color:var(--ink); }
    .ns-journal-link {
        font-size:.84rem; font-weight:700; color:#1d4ed8; text-decoration:none;
        display:block; padding:.35rem 0;
    }
    .ns-journal-link:hover { text-decoration:underline; }
</style>

@if ($errors->any())
    <div class="mp-flash" style="background:#fef2f2;border-color:#fecaca;color:#b91c1c">
        Please fix the highlighted fields before submitting.
    </div>
@endif

@if($journals->isEmpty())
    <section class="mp-card">
        <div class="mp-card__body" style="padding:2rem 1.25rem;text-align:center">
            <p style="margin:0;font-size:1.05rem;font-weight:800;color:var(--ink)">No journals are accepting submissions</p>
            <p style="margin:.4rem 0 0;font-size:.88rem;color:var(--muted)">Check back later or browse the public catalog.</p>
            <a href="{{ route('journals.index') }}" class="mp-btn mp-btn-secondary" style="margin-top:1rem" target="_blank" rel="noopener">Browse journals</a>
        </div>
    </section>
@else
<form
    method="POST"
    action="{{ route('author.submissions.store') }}"
    enctype="multipart/form-data"
    class="ns"
    x-data="{
        submitting: false,
        fileName: '',
        onFile(e) {
            const file = e.target.files?.[0];
            this.fileName = file ? file.name : '';
        }
    }"
    @submit="if (submitting) { $event.preventDefault() } else { submitting = true }"
>
    @csrf

    <div class="ns-main">
        <div class="ns-steps" aria-hidden="true">
            <div class="ns-step is-on"><span>1</span> Journal &amp; title</div>
            <div class="ns-step is-on"><span>2</span> Abstract</div>
            <div class="ns-step is-on"><span>3</span> Upload file</div>
        </div>

        <section class="mp-card">
            <div class="mp-card__head">
                <div>
                    <h2 class="mp-card__title">Journal &amp; title</h2>
                    <p class="mp-card__desc">Choose where to submit and name the manuscript.</p>
                </div>
            </div>
            <div class="mp-card__body" style="display:grid;gap:.95rem">
                <div class="mp-field">
                    <label for="journal_id">Journal <span style="color:#b91c1c">*</span></label>
                    <select id="journal_id" name="journal_id" required class="mp-select" :disabled="submitting">
                        <option value="">Select journal</option>
                        @foreach($journals as $journal)
                            <option value="{{ $journal->id }}" @selected((string) old('journal_id') === (string) $journal->id)>
                                {{ $journal->title }}@if($journal->subtitle) — {{ $journal->subtitle }}@endif
                            </option>
                        @endforeach
                    </select>
                    @error('journal_id')<p class="mp-error">{{ $message }}</p>@enderror
                </div>

                <div class="mp-field">
                    <label for="title">Manuscript title <span style="color:#b91c1c">*</span></label>
                    <input id="title" name="title" type="text" required value="{{ old('title') }}" class="mp-input" placeholder="Full article title" :readonly="submitting" autofocus>
                    @error('title')<p class="mp-error">{{ $message }}</p>@enderror
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
                    <label for="abstract">Abstract</label>
                    <textarea id="abstract" name="abstract" rows="7" class="mp-textarea" placeholder="Summarize the problem, methods, and key findings…" :readonly="submitting">{{ old('abstract') }}</textarea>
                    @error('abstract')<p class="mp-error">{{ $message }}</p>@enderror
                </div>

                <div class="ns-two">
                    <div class="mp-field">
                        <label for="category">Category</label>
                        <input id="category" name="category" type="text" value="{{ old('category') }}" class="mp-input" placeholder="e.g. Research article" :readonly="submitting">
                    </div>
                    <div class="mp-field">
                        <label for="keywords">Keywords</label>
                        <input id="keywords" name="keywords" type="text" value="{{ old('keywords') }}" class="mp-input" placeholder="Comma-separated" :readonly="submitting">
                        <p class="mp-hint">Optional. Example: machine learning, education, ethics</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="mp-card">
            <div class="mp-card__head">
                <div>
                    <h2 class="mp-card__title">Manuscript file</h2>
                    <p class="mp-card__desc">Upload the full document for editorial and peer review.</p>
                </div>
            </div>
            <div class="mp-card__body" style="display:grid;gap:.95rem">
                <div class="mp-field">
                    <label for="document">File <span style="color:#b91c1c">*</span></label>
                    <div class="ns-file" :class="fileName && 'is-ready'">
                        <input id="document" name="document" type="file" required accept=".pdf,.doc,.docx" class="mp-file" style="border:0;background:transparent;padding:0" :disabled="submitting" @change="onFile($event)">
                        <p class="ns-file__name" x-show="fileName" x-cloak x-text="fileName"></p>
                        <p class="mp-hint" x-show="!fileName">PDF, DOC, or DOCX · max 50MB</p>
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
                <p class="ns-tip"><strong>One journal.</strong> Submit to the journal that best fits your topic.</p>
                <p class="ns-tip"><strong>Blind review ready.</strong> Remove author names from the manuscript file if the journal requires anonymous review.</p>
                <p class="ns-tip"><strong>Clear title.</strong> Use the full scholarly title, not a working filename.</p>
                <p class="ns-tip"><strong>After submit.</strong> Track status and upload revisions from My submissions.</p>
            </div>
        </section>

        <section class="mp-card">
            <div class="mp-card__head">
                <h2 class="mp-card__title">Open journals</h2>
            </div>
            <div class="mp-card__body" style="display:grid;gap:.15rem">
                @foreach($journals->take(5) as $journal)
                    <a href="{{ route('journals.show', $journal) }}" class="ns-journal-link" target="_blank" rel="noopener">
                        {{ $journal->title }} ↗
                    </a>
                @endforeach
            </div>
        </section>
    </aside>
</form>
@endif
@endsection
