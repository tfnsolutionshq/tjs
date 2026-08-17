@extends('layouts.journal-manage')

@section('title', ($announcement->exists ? 'Edit' : 'New').' announcement | '.$journal->title)
@section('page_title', $announcement->exists ? 'Edit announcement' : 'New announcement')
@section('page_subtitle', $journal->title)

@section('page_actions')
    <a href="{{ route('journals.announcements', $journal) }}" class="admin-chip admin-topbar__public" target="_blank" rel="noopener">
        Public page
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H18v4.5M18 6l-7 7M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4"/></svg>
    </a>
    <a href="{{ route('journal.manage.announcements.index', $journal) }}" class="admin-btn admin-btn-secondary">All announcements</a>
@endsection

@section('content')
@php
    $callType = \App\Support\AnnouncementType::CALL_FOR_SUBMISSIONS;
    $newsType = \App\Support\AnnouncementType::NEWS;
    $opensValue = old('opens_at', $announcement->opens_at?->format('Y-m-d\TH:i') ?? (! $announcement->exists ? now()->format('Y-m-d\TH:i') : ''));
    $closesValue = old('closes_at', $announcement->closes_at?->format('Y-m-d\TH:i') ?? (! $announcement->exists ? now()->addMonth()->format('Y-m-d\TH:i') : ''));
@endphp

<style>
    .jaf { display: grid; gap: 1rem; max-width: 72rem; }
    @media (min-width: 1100px) {
        .jaf-layout { display: grid; grid-template-columns: minmax(0, 1fr) 17.5rem; gap: 1.15rem; align-items: start; }
    }
    .jaf-main { display: grid; gap: 1rem; min-width: 0; }
    .jaf-side { display: grid; gap: 1rem; }
    @media (min-width: 1100px) {
        .jaf-side { position: sticky; top: 1rem; }
    }
    .jaf-card {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 1.05rem;
        box-shadow: 0 8px 24px rgba(15,23,42,.035);
        overflow: visible;
    }
    .jaf-card__head {
        padding: 1rem 1.15rem .15rem;
    }
    .jaf-card__title {
        margin: 0;
        font-size: .95rem;
        font-weight: 800;
        color: var(--ink);
        letter-spacing: -.01em;
    }
    .jaf-card__desc {
        margin: .3rem 0 0;
        font-size: .8rem;
        color: var(--muted);
        line-height: 1.45;
    }
    .jaf-card__body { padding: 1rem 1.15rem 1.2rem; }
    .jaf-grid { display: grid; gap: .95rem; }
    @media (min-width: 720px) {
        .jaf-grid--2 { grid-template-columns: 1fr 1fr; }
        .jaf-span-2 { grid-column: 1 / -1; }
    }
    .jaf-field label,
    .jaf-field .tjs-label-row {
        display: block;
        margin-bottom: .4rem;
        font-size: .78rem;
        font-weight: 700;
        color: #334155;
        letter-spacing: .01em;
    }
    .jaf-req { color: #dc2626; font-weight: 800; margin-left: .05rem; }
    .jaf-field .jaf-hint {
        margin: .4rem 0 0;
        font-size: .72rem;
        color: var(--muted);
        line-height: 1.4;
    }
    .jaf-field .jaf-error {
        margin: .4rem 0 0;
        font-size: .75rem;
        font-weight: 600;
        color: #b91c1c;
    }
    .jaf-input,
    .jaf-textarea,
    .jaf-select {
        width: 100%;
        border: 1px solid #e2e8f0;
        background: #fff;
        border-radius: .7rem;
        padding: .7rem .85rem;
        font: inherit;
        font-size: .9rem;
        color: var(--ink);
        outline: none;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .jaf-textarea { resize: vertical; min-height: 9rem; line-height: 1.5; }
    .jaf-input:hover,
    .jaf-textarea:hover,
    .jaf-select:hover { border-color: #cbd5e1; }
    .jaf-input:focus,
    .jaf-textarea:focus,
    .jaf-select:focus {
        border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    }
    .jaf-type-grid {
        display: grid;
        gap: .65rem;
    }
    @media (min-width: 640px) {
        .jaf-type-grid { grid-template-columns: 1fr 1fr; }
    }
    .jaf-type {
        display: flex;
        gap: .75rem;
        align-items: flex-start;
        padding: .9rem 1rem;
        border: 1px solid #e2e8f0;
        border-radius: .85rem;
        background: #fff;
        cursor: pointer;
        transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
    }
    .jaf-type:hover { border-color: #cbd5e1; background: #f8fafc; }
    .jaf-type.is-active {
        border-color: #93c5fd;
        background: #eff6ff;
        box-shadow: 0 0 0 3px rgba(37,99,235,.1);
    }
    .jaf-type__icon {
        flex-shrink: 0;
        width: 2.15rem;
        height: 2.15rem;
        border-radius: .65rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .jaf-type__icon svg { width: 1rem; height: 1rem; }
    .jaf-type__icon--news { background: #f1f5f9; color: #475569; }
    .jaf-type__icon--call { background: #dbeafe; color: #2563eb; }
    .jaf-type.is-active .jaf-type__icon--call { background: #2563eb; color: #fff; }
    .jaf-type.is-active .jaf-type__icon--news { background: #475569; color: #fff; }
    .jaf-type__title {
        margin: 0;
        font-size: .86rem;
        font-weight: 800;
        color: var(--ink);
    }
    .jaf-type__text {
        margin: .2rem 0 0;
        font-size: .74rem;
        line-height: 1.4;
        color: var(--muted);
    }
    .jaf-type input { position: absolute; opacity: 0; pointer-events: none; }
    .jaf-alert {
        display: flex;
        gap: .65rem;
        align-items: flex-start;
        padding: .85rem .95rem;
        border-radius: .85rem;
        border: 1px solid #fde68a;
        background: #fffbeb;
        font-size: .78rem;
        line-height: 1.45;
        color: #92400e;
    }
    .jaf-alert a { color: #b45309; font-weight: 700; }
    .jaf-publish {
        display: flex;
        gap: .75rem;
        align-items: flex-start;
        padding: .95rem 1rem;
        border: 1px solid #e2e8f0;
        border-radius: .85rem;
        background: #f8fafc;
        cursor: pointer;
    }
    .jaf-publish input {
        margin-top: .15rem;
        width: 1rem;
        height: 1rem;
        border-radius: .3rem;
        border-color: #cbd5e1;
        color: #2563eb;
        flex-shrink: 0;
    }
    .jaf-publish__title {
        margin: 0;
        font-size: .84rem;
        font-weight: 800;
        color: var(--ink);
    }
    .jaf-publish__text {
        margin: .25rem 0 0;
        font-size: .74rem;
        line-height: 1.4;
        color: var(--muted);
    }
    .jaf-tips {
        margin: 0;
        padding: 0;
        list-style: none;
        display: grid;
        gap: .55rem;
    }
    .jaf-tips li {
        position: relative;
        padding-left: 1rem;
        font-size: .74rem;
        line-height: 1.45;
        color: var(--muted);
    }
    .jaf-tips li::before {
        content: '';
        position: absolute;
        left: 0;
        top: .45rem;
        width: .35rem;
        height: .35rem;
        border-radius: 999px;
        background: #93c5fd;
    }
    .jaf-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .55rem;
        padding: .95rem 1.15rem;
        border: 1px solid var(--line);
        border-radius: 1.05rem;
        background: #fff;
        box-shadow: 0 8px 24px rgba(15,23,42,.035);
    }
    .jaf-actions .admin-btn { min-height: 2.5rem; }
    [x-cloak] { display: none !important; }
</style>

<form
    method="POST"
    action="{{ $announcement->exists ? route('journal.manage.announcements.update', [$journal, $announcement]) : route('journal.manage.announcements.store', $journal) }}"
    class="jaf"
    x-data="{ type: @js(old('type', $announcement->type ?? $callType)) }"
>
    @csrf
    @if($announcement->exists)
        @method('PUT')
    @endif

    <div class="jaf-layout">
        <div class="jaf-main">
            <section class="jaf-card">
                <div class="jaf-card__head">
                    <h2 class="jaf-card__title">Announcement details</h2>
                    <p class="jaf-card__desc">Choose a type, write the content, then publish when ready.</p>
                </div>
                <div class="jaf-card__body jaf-grid">
                    <div class="jaf-field jaf-span-2">
                        <x-form-label field="announcement.type" required reqClass="jaf-req">Type</x-form-label>
                        <div class="jaf-type-grid">
                            <label @class(['jaf-type', 'is-active' => old('type', $announcement->type ?? $callType) === $callType]) :class="{ 'is-active': type === @js($callType) }">
                                <input type="radio" name="type" value="{{ $callType }}" x-model="type" required>
                                <span class="jaf-type__icon jaf-type__icon--call" aria-hidden="true">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                </span>
                                <span>
                                    <p class="jaf-type__title">Call for submissions</p>
                                    <p class="jaf-type__text">Open a specific issue to author manuscripts.</p>
                                </span>
                            </label>
                            <label @class(['jaf-type', 'is-active' => old('type', $announcement->type ?? $callType) === $newsType]) :class="{ 'is-active': type === @js($newsType) }">
                                <input type="radio" name="type" value="{{ $newsType }}" x-model="type">
                                <span class="jaf-type__icon jaf-type__icon--news" aria-hidden="true">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z"/></svg>
                                </span>
                                <span>
                                    <p class="jaf-type__title">News</p>
                                    <p class="jaf-type__text">Share updates with readers — no submission intake.</p>
                                </span>
                            </label>
                        </div>
                        @error('type')<p class="jaf-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="jaf-field jaf-span-2">
                        <x-form-label for="title" field="announcement.title" required reqClass="jaf-req">Title</x-form-label>
                        <input id="title" name="title" type="text" required class="jaf-input" value="{{ old('title', $announcement->title) }}" placeholder="e.g. Winter 2026 special issue call">
                        @error('title')<p class="jaf-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="jaf-field jaf-span-2">
                        <x-form-label for="summary" field="announcement.summary">Summary</x-form-label>
                        <input id="summary" name="summary" type="text" class="jaf-input" value="{{ old('summary', $announcement->summary) }}" placeholder="Short line shown in announcement listings">
                        @error('summary')<p class="jaf-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="jaf-field jaf-span-2">
                        <x-form-label for="body" field="announcement.body">Body</x-form-label>
                        <textarea id="body" name="body" rows="8" class="jaf-textarea" placeholder="Full announcement text for the public detail page">{{ old('body', $announcement->body) }}</textarea>
                        @error('body')<p class="jaf-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <section class="jaf-card" x-show="type === @js($callType)" x-cloak>
                <div class="jaf-card__head">
                    <h2 class="jaf-card__title">Call for submissions</h2>
                    <p class="jaf-card__desc">Authors can only submit to the selected issue while the call is open.</p>
                </div>
                <div class="jaf-card__body jaf-grid">
                    <div class="jaf-field jaf-span-2">
                        <x-form-label for="issue_id" field="announcement.issue" required reqClass="jaf-req">Target issue</x-form-label>
                        @if($issues->isEmpty())
                            <div class="jaf-alert">
                                <span aria-hidden="true">⚠</span>
                                <span>
                                    You need at least one issue before opening a call.
                                    <a href="{{ route('journal.manage.volumes.index', $journal) }}">Create a volume &amp; issue</a>
                                    first under Catalog.
                                </span>
                            </div>
                        @else
                            <select id="issue_id" name="issue_id" class="jaf-select" :required="type === @js($callType)">
                                <option value="">Select issue…</option>
                                @foreach($issues as $issue)
                                    <option value="{{ $issue->id }}" @selected((string) old('issue_id', $announcement->issue_id) === (string) $issue->id)>
                                        {{ $issue->label() }}@if($issue->title) — {{ $issue->title }}@endif
                                    </option>
                                @endforeach
                            </select>
                        @endif
                        @error('issue_id')<p class="jaf-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="jaf-grid jaf-grid--2 jaf-span-2">
                        <div class="jaf-field">
                            <x-form-label for="opens_at" field="announcement.opens_at" required reqClass="jaf-req">Opens</x-form-label>
                            <input id="opens_at" name="opens_at" type="datetime-local" class="jaf-input" value="{{ $opensValue }}" :required="type === @js($callType)">
                            @error('opens_at')<p class="jaf-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="jaf-field">
                            <x-form-label for="closes_at" field="announcement.closes_at" required reqClass="jaf-req">Closes</x-form-label>
                            <input id="closes_at" name="closes_at" type="datetime-local" class="jaf-input" value="{{ $closesValue }}" :required="type === @js($callType)">
                            @error('closes_at')<p class="jaf-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <aside class="jaf-side">
            <section class="jaf-card">
                <div class="jaf-card__head">
                    <h2 class="jaf-card__title">Publishing</h2>
                </div>
                <div class="jaf-card__body">
                    <label class="jaf-publish">
                        <input type="hidden" name="is_published" value="0">
                        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $announcement->is_published))>
                        <span>
                            <p class="jaf-publish__title">Publish on the journal website</p>
                            <p class="jaf-publish__text">Drafts stay hidden from readers and authors until you publish.</p>
                        </span>
                    </label>
                </div>
            </section>

            <section class="jaf-card">
                <div class="jaf-card__head">
                    <h2 class="jaf-card__title">Tips</h2>
                </div>
                <div class="jaf-card__body">
                    <ul class="jaf-tips">
                        <li>Calls must target a single issue — authors submit through the open call picker.</li>
                        <li>Closing a call stops new submissions; existing manuscripts stay in the workflow.</li>
                        <li>News posts do not accept submissions — use them for editorial updates.</li>
                    </ul>
                </div>
            </section>
        </aside>
    </div>

    <div class="jaf-actions">
        <button type="submit" class="admin-btn admin-btn-primary">
            {{ $announcement->exists ? 'Save changes' : 'Create announcement' }}
        </button>
        <a href="{{ route('journal.manage.announcements.index', $journal) }}" class="admin-btn admin-btn-secondary">Cancel</a>
    </div>
</form>
@endsection
