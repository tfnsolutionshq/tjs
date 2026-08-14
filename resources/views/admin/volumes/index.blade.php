@extends(isset($manageJournal) ? 'layouts.journal-manage' : 'layouts.admin')

@section('title', (isset($manageJournal) ? $manageJournal->title.' | ' : '').'Volumes & issues')
@section('page_title', 'Volumes & issues')
@section('page_subtitle', $journal->title)

@php
    $canMutate = $canMutate ?? true;
    $volumesStore = isset($manageJournal)
        ? route('journal.manage.volumes.store', $manageJournal)
        : route('admin.volumes.store', $journal);
    $volumeUpdate = fn ($volume) => isset($manageJournal)
        ? route('journal.manage.volumes.update', [$manageJournal, $volume])
        : route('admin.volumes.update', [$journal, $volume]);
    $issueStore = fn ($volume) => isset($manageJournal)
        ? route('journal.manage.issues.store', [$manageJournal, $volume])
        : route('admin.issues.store', [$journal, $volume]);
    $issueUpdate = fn ($volume, $issue) => isset($manageJournal)
        ? route('journal.manage.issues.update', [$manageJournal, $volume, $issue])
        : route('admin.issues.update', [$journal, $volume, $issue]);
@endphp

@section('page_actions')
    <a href="{{ route('journals.show', $journal) }}" target="_blank" rel="noopener" class="admin-chip">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-6.5 9.5-6.5S21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="2.5"/></svg>
        Preview
    </a>
    @isset($manageJournal)
        <a href="{{ route('journal.manage.settings.edit', $manageJournal) }}" class="admin-btn admin-btn-secondary">Settings</a>
    @else
        <a href="{{ route('admin.journals.edit', $journal) }}" class="admin-btn admin-btn-secondary">Edit journal</a>
    @endisset
@endsection

@section('content')
@php
    $nextVolume = ((int) ($volumes->max('volume_number') ?? 0)) + 1;
@endphp

<style>
    .av-crumb {
        display: flex; flex-wrap: wrap; align-items: center; gap: .35rem;
        margin-bottom: .9rem; font-size: .8rem; color: var(--muted); font-weight: 600;
    }
    .av-crumb a { color: var(--muted); text-decoration: none; }
    .av-crumb a:hover { color: #1d4ed8; }
    .av-crumb__sep { opacity: .5; }

    .av-stats {
        display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; margin-bottom: 1rem;
    }
    @media (min-width: 860px) {
        .av-stats { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }
    .av-stat {
        background: #fff; border: 1px solid var(--line); border-radius: .95rem;
        padding: .9rem 1rem; box-shadow: 0 8px 24px rgba(15,23,42,.035);
    }
    .av-stat__label {
        font-size: .68rem; font-weight: 700; letter-spacing: .08em;
        text-transform: uppercase; color: var(--muted);
    }
    .av-stat__value {
        margin-top: .35rem; font-size: 1.55rem; font-weight: 800;
        letter-spacing: -.03em; color: var(--ink); line-height: 1;
    }

    .av-card {
        background: #fff; border: 1px solid var(--line); border-radius: 1.05rem;
        box-shadow: 0 8px 24px rgba(15,23,42,.035); overflow: hidden;
    }
    .av-card + .av-card { margin-top: .85rem; }
    .av-card__head {
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .65rem;
        padding: .9rem 1.05rem; border-bottom: 1px solid var(--line);
    }
    .av-card__title { margin: 0; font-size: .95rem; font-weight: 800; color: var(--ink); }
    .av-card__desc { margin: .2rem 0 0; font-size: .78rem; color: var(--muted); }
    .av-card__body { padding: 1rem 1.05rem 1.1rem; }

    .av-grid { display: grid; gap: .75rem; }
    @media (min-width: 720px) {
        .av-grid--2 { grid-template-columns: 1fr 1fr; }
        .av-grid--3 { grid-template-columns: 1fr 1fr 1fr; }
        .av-span-2 { grid-column: 1 / -1; }
    }

    .av-field label {
        display: block; margin-bottom: .35rem;
        font-size: .74rem; font-weight: 700; color: #334155;
    }
    .av-field .av-error { margin: .3rem 0 0; font-size: .72rem; font-weight: 600; color: #b91c1c; }
    .av-input, .av-select, .av-textarea {
        width: 100%; border: 1px solid #e2e8f0; background: #fff; border-radius: .65rem;
        padding: .58rem .75rem; font: inherit; font-size: .86rem; color: var(--ink); outline: none;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .av-textarea { resize: vertical; min-height: 4.5rem; line-height: 1.45; }
    .av-input:focus, .av-select:focus, .av-textarea:focus {
        border-color: #93c5fd; box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    }
    .av-file {
        display: block; width: 100%; font-size: .78rem; color: var(--muted);
    }
    .av-file::file-selector-button {
        margin-right: .55rem; border: 1px solid var(--line); border-radius: .5rem;
        background: #fff; padding: .4rem .65rem; font: inherit; font-size: .74rem;
        font-weight: 700; color: var(--ink); cursor: pointer;
    }

    .av-actions { display: flex; flex-wrap: wrap; gap: .45rem; margin-top: .85rem; }
    .av-badge {
        display: inline-flex; align-items: center;
        font-size: .66rem; font-weight: 700; letter-spacing: .02em;
        padding: .22rem .5rem; border-radius: 999px;
    }
    .av-badge--draft { background: #f1f5f9; color: #64748b; }
    .av-badge--published { background: #dcfce7; color: #15803d; }

    .av-volume {
        background: #fff; border: 1px solid var(--line); border-radius: 1.05rem;
        box-shadow: 0 8px 24px rgba(15,23,42,.035); overflow: hidden;
    }
    .av-volume + .av-volume { margin-top: .85rem; }
    .av-volume__top {
        display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: .75rem;
        padding: 1rem 1.05rem; border-bottom: 1px solid var(--line);
    }
    .av-volume__title {
        margin: 0; font-size: 1.05rem; font-weight: 800; color: var(--ink); line-height: 1.3;
    }
    .av-volume__meta {
        margin: .35rem 0 0; font-size: .78rem; color: var(--muted); font-weight: 600;
        display: flex; flex-wrap: wrap; gap: .45rem; align-items: center;
    }
    .av-volume__body { padding: 1rem 1.05rem 1.1rem; display: grid; gap: 1rem; }

    .av-section-label {
        margin: 0 0 .65rem;
        font-size: .68rem; font-weight: 700; letter-spacing: .08em;
        text-transform: uppercase; color: var(--muted);
    }

    .av-issue {
        border: 1px solid var(--line); border-radius: .8rem; background: #f8fafc;
        padding: .75rem .85rem;
    }
    .av-issue + .av-issue { margin-top: .55rem; }
    .av-issue__row {
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem;
    }
    .av-issue__name { margin: 0; font-size: .86rem; font-weight: 700; color: var(--ink); }
    .av-issue__period { margin: .2rem 0 0; font-size: .74rem; color: var(--muted); }

    .av-add {
        border: 1px dashed #d0d7e2; border-radius: .85rem; padding: .9rem;
        background: #fafbfc;
    }

    .av-empty {
        background: #fff; border: 1px solid var(--line); border-radius: 1.05rem;
        padding: 2rem 1.25rem; text-align: center;
        box-shadow: 0 8px 24px rgba(15,23,42,.035);
    }
    .av-empty__title { margin: 0; font-size: 1rem; font-weight: 800; color: var(--ink); }
    .av-empty__text { margin: .35rem 0 0; font-size: .86rem; color: var(--muted); }

    .av-toggle {
        display: inline-flex; align-items: center; gap: .35rem;
        border: 1px solid var(--line); background: #fff; border-radius: .6rem;
        padding: .45rem .7rem; font: inherit; font-size: .78rem; font-weight: 700;
        color: var(--ink); cursor: pointer;
    }
    .av-toggle svg { width: .9rem; height: .9rem; }
    [x-cloak] { display: none !important; }
</style>

<nav class="av-crumb" aria-label="Breadcrumb">
    @isset($manageJournal)
        <a href="{{ route('journal.manage.dashboard', $manageJournal) }}">Manage</a>
        <span class="av-crumb__sep">/</span>
        <span style="color:var(--ink)">Volumes</span>
    @else
        <a href="{{ route('admin.journals.index') }}">Journals</a>
        <span class="av-crumb__sep">/</span>
        <a href="{{ route('admin.journals.edit', $journal) }}">{{ $journal->title }}</a>
        <span class="av-crumb__sep">/</span>
        <span style="color:var(--ink)">Volumes</span>
    @endisset
</nav>

@unless($canMutate)
    <div class="av-card" style="margin-bottom:1rem;border-color:#fde68a;background:#fffbeb">
        <div class="av-card__body">
            <p style="margin:0;font-size:.88rem;font-weight:700;color:#92400e">Platform edits are disabled for this journal.</p>
            <p style="margin:.35rem 0 0;font-size:.8rem;color:#a16207">A journal admin can re-enable them under Settings → Allow platform admin edits.</p>
        </div>
    </div>
@endunless

<div @unless($canMutate) style="opacity:.55;pointer-events:none" @endunless>

<div class="av-stats">
    <div class="av-stat">
        <p class="av-stat__label">Volumes</p>
        <p class="av-stat__value">{{ number_format($stats['volumes']) }}</p>
    </div>
    <div class="av-stat">
        <p class="av-stat__label">Issues</p>
        <p class="av-stat__value">{{ number_format($stats['issues']) }}</p>
    </div>
    <div class="av-stat">
        <p class="av-stat__label">Published</p>
        <p class="av-stat__value">{{ number_format($stats['published']) }}</p>
    </div>
    <div class="av-stat">
        <p class="av-stat__label">Draft</p>
        <p class="av-stat__value">{{ number_format($stats['draft']) }}</p>
    </div>
</div>

<section class="av-card" x-data="{ open: {{ $errors->any() && ! old('_volume_id') ? 'true' : ($volumes->isEmpty() ? 'true' : 'false') }} }">
    <div class="av-card__head">
        <div>
            <h2 class="av-card__title">Create volume</h2>
            <p class="av-card__desc">Add a new volume for {{ $journal->title }}.</p>
        </div>
        <button type="button" class="av-toggle" @click="open = !open" :aria-expanded="open.toString()">
            <svg x-show="!open" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M12 6v12M6 12h12"/></svg>
            <svg x-show="open" x-cloak fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M6 12h12"/></svg>
            <span x-text="open ? 'Hide form' : 'New volume'"></span>
        </button>
    </div>
    <div class="av-card__body" x-show="open" x-cloak>
        @if ($errors->any() && ! old('_volume_id') && ! old('_issue_volume'))
            <div style="margin-bottom:.85rem;padding:.75rem .85rem;border-radius:.75rem;background:#fef2f2;border:1px solid #fecaca;color:#991b1b">
                <p style="margin:0;font-size:.82rem;font-weight:800">Couldn’t create volume</p>
                <ul style="margin:.35rem 0 0;padding-left:1.1rem;font-size:.78rem">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $volumesStore }}" enctype="multipart/form-data">
            @csrf
            <div class="av-grid av-grid--2">
                <div class="av-field">
                    <label for="volume_number">Volume number</label>
                    <input id="volume_number" name="volume_number" type="number" min="1" required class="av-input"
                        value="{{ old('volume_number', $nextVolume) }}" placeholder="{{ $nextVolume }}">
                    @error('volume_number')<p class="av-error">{{ $message }}</p>@enderror
                </div>
                <div class="av-field">
                    <label for="year">Year</label>
                    <input id="year" name="year" type="number" min="1900" max="2100" required class="av-input"
                        value="{{ old('year', date('Y')) }}">
                    @error('year')<p class="av-error">{{ $message }}</p>@enderror
                </div>
                <div class="av-field">
                    <label for="title">Title</label>
                    <input id="title" name="title" type="text" class="av-input" value="{{ old('title') }}" placeholder="Optional volume title">
                </div>
                <div class="av-field">
                    <label for="issn">ISSN</label>
                    <input id="issn" name="issn" type="text" class="av-input" value="{{ old('issn') }}" placeholder="0000-0000">
                </div>
                <div class="av-field av-span-2">
                    <label for="introduction">Introduction</label>
                    <textarea id="introduction" name="introduction" rows="3" class="av-textarea" placeholder="Optional intro for this volume">{{ old('introduction') }}</textarea>
                </div>
                <div class="av-field">
                    <label for="status">Status</label>
                    <select id="status" name="status" required class="av-select">
                        <option value="draft" @selected(old('status', 'draft') === 'draft')>Draft</option>
                        <option value="published" @selected(old('status') === 'published')>Published</option>
                    </select>
                </div>
                <div class="av-field">
                    <label for="cover">Cover image</label>
                    <input id="cover" name="cover" type="file" accept=".jpg,.jpeg,.png,.webp" class="av-file">
                </div>
            </div>
            <div class="av-actions">
                <button type="submit" class="admin-btn admin-btn-primary">Create volume</button>
            </div>
        </form>
    </div>
</section>

<div style="margin-top:1rem">
    @forelse($volumes as $volume)
        <article class="av-volume" x-data="{ edit: false, addIssue: false }">
            <div class="av-volume__top">
                <div>
                    <h2 class="av-volume__title">
                        Vol. {{ $volume->volume_number }}
                        <span style="color:var(--muted);font-weight:700">({{ $volume->year }})</span>
                        @if($volume->title)
                            <span style="font-weight:650;color:#334155">— {{ $volume->title }}</span>
                        @endif
                    </h2>
                    <div class="av-volume__meta">
                        <span class="av-badge {{ $volume->status === 'published' ? 'av-badge--published' : 'av-badge--draft' }}">
                            {{ ucfirst($volume->status) }}
                        </span>
                        <span>{{ $volume->issues_count }} {{ \Illuminate\Support\Str::plural('issue', $volume->issues_count) }}</span>
                        @if($volume->issn)<span>ISSN {{ $volume->issn }}</span>@endif
                    </div>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:.4rem">
                    <button type="button" class="av-toggle" @click="edit = !edit; addIssue = false">
                        <span x-text="edit ? 'Close edit' : 'Edit volume'"></span>
                    </button>
                    <button type="button" class="admin-btn admin-btn-primary" style="padding:.45rem .75rem;font-size:.78rem" @click="addIssue = !addIssue; edit = false">
                        Add issue
                    </button>
                </div>
            </div>

            <div class="av-volume__body">
                <div x-show="edit" x-cloak>
                    <p class="av-section-label">Edit volume</p>
                    <form method="POST" action="{{ $volumeUpdate($volume) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="_volume_id" value="{{ $volume->id }}">
                        <div class="av-grid av-grid--3">
                            <div class="av-field">
                                <label>Volume number</label>
                                <input name="volume_number" type="number" min="1" required class="av-input" value="{{ old('volume_number', $volume->volume_number) }}">
                            </div>
                            <div class="av-field">
                                <label>Year</label>
                                <input name="year" type="number" min="1900" max="2100" required class="av-input" value="{{ old('year', $volume->year) }}">
                            </div>
                            <div class="av-field">
                                <label>Status</label>
                                <select name="status" required class="av-select">
                                    <option value="draft" @selected(old('status', $volume->status) === 'draft')>Draft</option>
                                    <option value="published" @selected(old('status', $volume->status) === 'published')>Published</option>
                                </select>
                            </div>
                            <div class="av-field">
                                <label>Title</label>
                                <input name="title" type="text" class="av-input" value="{{ old('title', $volume->title) }}">
                            </div>
                            <div class="av-field">
                                <label>ISSN</label>
                                <input name="issn" type="text" class="av-input" value="{{ old('issn', $volume->issn) }}">
                            </div>
                            <div class="av-field">
                                <label>Replace cover</label>
                                <input name="cover" type="file" accept=".jpg,.jpeg,.png,.webp" class="av-file">
                            </div>
                            <div class="av-field av-span-2" style="grid-column:1/-1">
                                <label>Introduction</label>
                                <textarea name="introduction" rows="3" class="av-textarea">{{ old('introduction', $volume->introduction) }}</textarea>
                            </div>
                        </div>
                        <div class="av-actions">
                            <button type="submit" class="admin-btn admin-btn-primary">Save volume</button>
                            <button type="button" class="admin-btn admin-btn-secondary" @click="edit = false">Cancel</button>
                        </div>
                    </form>
                </div>

                <div>
                    <p class="av-section-label">Issues</p>
                    @forelse($volume->issues as $issue)
                        <div class="av-issue" x-data="{ open: false }">
                            <div class="av-issue__row">
                                <div>
                                    <p class="av-issue__name">
                                        Issue {{ $issue->issue_number }}
                                        @if($issue->title) — {{ $issue->title }}@endif
                                    </p>
                                    <p class="av-issue__period">
                                        <span class="av-badge {{ $issue->status === 'published' ? 'av-badge--published' : 'av-badge--draft' }}">
                                            {{ ucfirst($issue->status) }}
                                        </span>
                                        @if($issue->period_start || $issue->period_end)
                                            <span style="margin-left:.35rem">
                                                {{ optional($issue->period_start)->format('M j, Y') ?: '…' }}
                                                –
                                                {{ optional($issue->period_end)->format('M j, Y') ?: '…' }}
                                            </span>
                                        @endif
                                    </p>
                                </div>
                                <button type="button" class="av-toggle" @click="open = !open">
                                    <span x-text="open ? 'Close' : 'Edit'"></span>
                                </button>
                            </div>

                            <form
                                method="POST"
                                action="{{ $issueUpdate($volume, $issue) }}"
                                enctype="multipart/form-data"
                                class="av-grid av-grid--3"
                                style="margin-top:.75rem"
                                x-show="open"
                                x-cloak
                            >
                                @csrf
                                @method('PUT')
                                <div class="av-field">
                                    <label>Issue number</label>
                                    <input name="issue_number" type="number" min="1" required class="av-input" value="{{ $issue->issue_number }}">
                                </div>
                                <div class="av-field">
                                    <label>Title</label>
                                    <input name="title" type="text" class="av-input" value="{{ $issue->title }}">
                                </div>
                                <div class="av-field">
                                    <label>Status</label>
                                    <select name="status" required class="av-select">
                                        <option value="draft" @selected($issue->status === 'draft')>Draft</option>
                                        <option value="published" @selected($issue->status === 'published')>Published</option>
                                    </select>
                                </div>
                                <div class="av-field">
                                    <label>Period start</label>
                                    <input name="period_start" type="date" class="av-input" value="{{ optional($issue->period_start)->format('Y-m-d') }}">
                                </div>
                                <div class="av-field">
                                    <label>Period end</label>
                                    <input name="period_end" type="date" class="av-input" value="{{ optional($issue->period_end)->format('Y-m-d') }}">
                                </div>
                                <div class="av-field">
                                    <label>Cover</label>
                                    <input name="cover" type="file" accept=".jpg,.jpeg,.png,.webp" class="av-file">
                                </div>
                                <div class="av-actions" style="grid-column:1/-1;margin-top:0">
                                    <button type="submit" class="admin-btn admin-btn-primary">Save issue</button>
                                </div>
                            </form>
                        </div>
                    @empty
                        <p style="margin:0;font-size:.84rem;color:var(--muted)">No issues in this volume yet.</p>
                    @endforelse
                </div>

                <div class="av-add" x-show="addIssue" x-cloak>
                    <p class="av-section-label">Add issue to Vol. {{ $volume->volume_number }}</p>
                    <form method="POST" action="{{ $issueStore($volume) }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="_issue_volume" value="{{ $volume->id }}">
                        <div class="av-grid av-grid--3">
                            <div class="av-field">
                                <label>Issue number</label>
                                <input name="issue_number" type="number" min="1" required class="av-input"
                                    value="{{ ($volume->issues->max('issue_number') ?? 0) + 1 }}">
                            </div>
                            <div class="av-field">
                                <label>Title</label>
                                <input name="title" type="text" class="av-input" placeholder="Optional">
                            </div>
                            <div class="av-field">
                                <label>Status</label>
                                <select name="status" required class="av-select">
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                </select>
                            </div>
                            <div class="av-field">
                                <label>Period start</label>
                                <input name="period_start" type="date" class="av-input">
                            </div>
                            <div class="av-field">
                                <label>Period end</label>
                                <input name="period_end" type="date" class="av-input">
                            </div>
                            <div class="av-field">
                                <label>Cover</label>
                                <input name="cover" type="file" accept=".jpg,.jpeg,.png,.webp" class="av-file">
                            </div>
                        </div>
                        <div class="av-actions">
                            <button type="submit" class="admin-btn admin-btn-primary">Create issue</button>
                            <button type="button" class="admin-btn admin-btn-secondary" @click="addIssue = false">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </article>
    @empty
        <div class="av-empty">
            <p class="av-empty__title">No volumes yet</p>
            <p class="av-empty__text">Create the first volume to start organizing issues and articles.</p>
        </div>
    @endforelse
</div>
@endsection
