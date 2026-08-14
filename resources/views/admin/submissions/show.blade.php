@extends(isset($manageJournal) ? 'layouts.journal-manage' : 'layouts.admin')

@section('title', isset($manageJournal) ? 'Submission | '.$manageJournal->title : 'Submission | Admin')
@section('page_title', 'Submission')
@section('page_subtitle', $submission->title)

@section('page_actions')
    <a href="{{ isset($manageJournal) ? route('journal.manage.submissions.index', $manageJournal) : route('admin.submissions.index') }}" class="admin-chip">All submissions</a>
@endsection

@section('content')
@php
    $assignUrl = isset($manageJournal)
        ? route('journal.manage.submissions.assign-reviewer', [$manageJournal, $submission])
        : route('admin.submissions.assign-reviewer', $submission);
    $publishUrl = isset($manageJournal)
        ? route('journal.manage.submissions.publish', [$manageJournal, $submission])
        : route('admin.submissions.publish', $submission);
@endphp

<div class="mt-2 flex flex-wrap gap-2">
    <span class="tjs-badge">{{ str_replace('_', ' ', $submission->status) }}</span>
    <span class="text-sm text-slate-500">{{ $submission->journal?->title }}</span>
</div>

<div class="tjs-card mt-6 space-y-3 p-4 text-sm text-slate-700 sm:p-6">
    <p><span class="font-medium text-slate-900">Author:</span> {{ $submission->author?->name }} ({{ $submission->author?->email }})</p>
    @if($submission->reviewer)
        <p><span class="font-medium text-slate-900">Reviewer:</span> {{ $submission->reviewer->name }}</p>
    @endif
    @if($submission->category)
        <p><span class="font-medium text-slate-900">Category:</span> {{ $submission->category }}</p>
    @endif
    @if($submission->keywords)
        <p><span class="font-medium text-slate-900">Keywords:</span> {{ $submission->keywords }}</p>
    @endif
    @if($submission->abstract)
        <div>
            <p class="font-medium text-slate-900">Abstract</p>
            <p class="mt-1 whitespace-pre-wrap text-slate-600">{{ $submission->abstract }}</p>
        </div>
    @endif
    @if($submission->review_comment)
        <p><span class="font-medium text-slate-900">Review comment:</span> {{ $submission->review_comment }}</p>
    @endif
    @if($submission->rejection_reason)
        <p><span class="font-medium text-slate-900">Rejection reason:</span> {{ $submission->rejection_reason }}</p>
    @endif
    @if($submission->document_path)
        <p><span class="font-medium text-slate-900">Manuscript:</span> on file</p>
    @endif
</div>

@if($submission->assignments->isNotEmpty())
    <div class="tjs-card mt-6 p-4 sm:p-6">
        <h2 class="text-base font-semibold text-slate-900">Assignments</h2>
        <ul class="mt-3 space-y-2 text-sm text-slate-600">
            @foreach($submission->assignments as $assignment)
                <li>
                    {{ $assignment->reviewer?->name ?? 'Reviewer' }}
                    · {{ str_replace('_', ' ', $assignment->status) }}
                    @if($assignment->due_at) · due {{ $assignment->due_at->format('M j, Y') }} @endif
                </li>
            @endforeach
        </ul>
    </div>
@endif

<div class="tjs-card mt-6 p-4 sm:p-6">
    <h2 class="text-base font-semibold text-slate-900">Assign reviewer</h2>
    <form method="POST" action="{{ $assignUrl }}" class="mt-4 grid gap-4 sm:grid-cols-2">
        @csrf
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-slate-700" for="reviewer_id">Reviewer</label>
            <select id="reviewer_id" name="reviewer_id" required class="mt-1 w-full rounded-md border-slate-300 shadow-sm focus:border-teal-600 focus:ring-teal-600">
                <option value="">Select reviewer…</option>
                @foreach($reviewers as $reviewer)
                    <option value="{{ $reviewer->id }}" @selected((string) old('reviewer_id') === (string) $reviewer->id)>
                        {{ $reviewer->name }} ({{ $reviewer->email }})
                    </option>
                @endforeach
            </select>
            @error('reviewer_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700" for="priority">Priority (1–5)</label>
            <input id="priority" name="priority" type="number" min="1" max="5" value="{{ old('priority', 3) }}"
                class="mt-1 w-full rounded-md border-slate-300 shadow-sm focus:border-teal-600 focus:ring-teal-600">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700" for="due_at">Due date</label>
            <input id="due_at" name="due_at" type="date" value="{{ old('due_at') }}"
                class="mt-1 w-full rounded-md border-slate-300 shadow-sm focus:border-teal-600 focus:ring-teal-600">
        </div>
        <div class="sm:col-span-2">
            <button type="submit" class="tjs-btn">Assign reviewer</button>
        </div>
    </form>
</div>

@if($submission->status === 'approved')
    <div class="tjs-card mt-6 p-4 sm:p-6">
        <h2 class="text-base font-semibold text-slate-900">Publish to issue</h2>
        <p class="mt-1 text-sm text-slate-600">Create or update the catalog article from this approved submission.</p>
        <form method="POST" action="{{ $publishUrl }}" class="mt-4 grid gap-4 sm:grid-cols-2">
            @csrf
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-slate-700" for="issue_id">Issue</label>
                <select id="issue_id" name="issue_id" required class="mt-1 w-full rounded-md border-slate-300 shadow-sm focus:border-teal-600 focus:ring-teal-600">
                    <option value="">Select issue…</option>
                    @foreach($issues as $issue)
                        <option value="{{ $issue->id }}" @selected((string) old('issue_id') === (string) $issue->id)>
                            Vol. {{ $issue->volume?->volume_number }} · Issue {{ $issue->issue_number }}
                            @if($issue->title) — {{ $issue->title }} @endif
                        </option>
                    @endforeach
                </select>
                @error('issue_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700" for="slug">Slug</label>
                <input id="slug" name="slug" type="text" value="{{ old('slug') }}"
                    class="mt-1 w-full rounded-md border-slate-300 shadow-sm focus:border-teal-600 focus:ring-teal-600" placeholder="optional">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700" for="doi">DOI</label>
                <input id="doi" name="doi" type="text" value="{{ old('doi') }}"
                    class="mt-1 w-full rounded-md border-slate-300 shadow-sm focus:border-teal-600 focus:ring-teal-600">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700" for="visibility">Visibility</label>
                <select id="visibility" name="visibility" class="mt-1 w-full rounded-md border-slate-300 shadow-sm focus:border-teal-600 focus:ring-teal-600">
                    @foreach(['open', 'members_only', 'paid', 'closed'] as $vis)
                        <option value="{{ $vis }}" @selected(old('visibility', 'open') === $vis)>{{ str_replace('_', ' ', $vis) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700" for="license">License</label>
                <x-license-picker
                    name="license"
                    id="license"
                    :value="old('license')"
                    select-class="mt-1 w-full rounded-md border-slate-300 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700" for="author_name">Author name (fallback)</label>
                <input id="author_name" name="author_name" type="text" value="{{ old('author_name', $submission->author?->name) }}"
                    class="mt-1 w-full rounded-md border-slate-300 shadow-sm focus:border-teal-600 focus:ring-teal-600">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700" for="page_range">Page range</label>
                <input id="page_range" name="page_range" type="text" value="{{ old('page_range') }}"
                    class="mt-1 w-full rounded-md border-slate-300 shadow-sm focus:border-teal-600 focus:ring-teal-600">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-slate-700" for="authors_text">Authors text (optional)</label>
                <textarea id="authors_text" name="authors_text" rows="3"
                    class="mt-1 w-full rounded-md border-slate-300 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                    placeholder="One per line: Name|email|affiliation|orcid">{{ old('authors_text') }}</textarea>
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="tjs-btn">Publish to issue</button>
            </div>
        </form>
    </div>
@endif

@if($submission->timelines->isNotEmpty())
    <div class="tjs-card mt-6 p-4 sm:p-6">
        <h2 class="text-base font-semibold text-slate-900">Timeline</h2>
        <ul class="mt-3 space-y-2 text-sm text-slate-600">
            @foreach($submission->timelines as $event)
                <li>
                    <span class="font-medium text-slate-800">{{ str_replace('_', ' ', $event->event) }}</span>
                    · {{ $event->user?->name ?? 'System' }}
                    · {{ $event->created_at?->format('M j, Y g:ia') }}
                </li>
            @endforeach
        </ul>
    </div>
@endif
@endsection
