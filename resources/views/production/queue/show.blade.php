@extends('layouts.member')

@section('title', $submission->title.' | Production')
@section('page_title', 'Production workspace')
@section('page_subtitle', $submission->journal?->title)

@section('page_actions')
    <a href="{{ route('production.queue.index') }}" class="admin-btn admin-btn-secondary">Production queue</a>
@endsection

@section('content')
@php
    use App\Support\ProductionChecklist;
    use App\Support\SubmissionStatus;
    $canEdit = ! in_array($submission->status, [SubmissionStatus::PUBLISHED], true);
@endphp

@if (session('status'))
    <div class="mp-flash">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="mp-flash mp-flash--error">{{ session('error') }}</div>
@endif

<div style="display:grid;gap:1rem;max-width:72rem">
    <div class="mp-card">
        <div class="mp-card__head">
            <div>
                <h2 class="mp-card__title">{{ $submission->title }}</h2>
                <p class="mp-card__desc">
                    <span class="mp-badge mp-badge--{{ $submission->status }}">{{ SubmissionStatus::label($submission->status) }}</span>
                    · {{ $submission->author?->name }}
                    @if($submission->issue) · {{ $submission->issue->label() }} @endif
                </p>
            </div>
        </div>
        <div class="mp-card__body" style="display:grid;gap:.75rem">
            @if($submission->abstract)
                <div>
                    <p style="margin:0;font-size:.72rem;font-weight:700;text-transform:uppercase;color:var(--muted)">Abstract</p>
                    <div class="tjs-prose" style="margin-top:.35rem;font-size:.88rem">{!! \App\Support\SafeHtml::display($submission->abstract) !!}</div>
                </div>
            @endif
            @if($submission->keywords)
                <p style="margin:0;font-size:.84rem"><strong>Keywords:</strong> {{ $submission->keywords }}</p>
            @endif
        </div>
    </div>

    <div class="mp-card">
        <div class="mp-card__head">
            <div>
                <h2 class="mp-card__title">Author manuscript</h2>
                <p class="mp-card__desc">Read-only source file from the author. Do not overwrite this file.</p>
            </div>
        </div>
        <div class="mp-card__body">
            @if($submission->document_path)
                <a href="{{ route('production.queue.download-source', $submission) }}" class="mp-btn mp-btn-secondary">Download accepted manuscript</a>
            @else
                <p class="mp-empty">No author manuscript on file.</p>
            @endif
        </div>
    </div>

    <div class="mp-card">
        <div class="mp-card__head">
            <div>
                <h2 class="mp-card__title">Final production document</h2>
                <p class="mp-card__desc">Journal-formatted file used when editors publish this article.</p>
            </div>
        </div>
        <div class="mp-card__body" style="display:grid;gap:1rem">
            @if($currentFile)
                <div style="padding:.85rem 1rem;border-radius:.75rem;background:#f8fafc;border:1px solid var(--line)">
                    <p style="margin:0;font-weight:700">{{ $currentFile->original_filename ?: basename($currentFile->document_path) }}</p>
                    <p style="margin:.35rem 0 0;font-size:.8rem;color:var(--muted)">
                        Uploaded by {{ $currentFile->uploader?->name ?? 'Production editor' }}
                        · {{ optional($currentFile->created_at)->format('M j, Y g:i A') }}
                        · Version {{ $currentFile->version }}
                    </p>
                    <div style="margin-top:.75rem;display:flex;flex-wrap:wrap;gap:.5rem">
                        <a href="{{ route('production.queue.download-production', $submission) }}" class="mp-btn mp-btn-secondary">Download</a>
                    </div>
                </div>
            @else
                <p class="mp-empty">No production document uploaded yet.</p>
            @endif

            @if($canEdit)
                <form method="POST" action="{{ route('production.queue.upload', $submission) }}" enctype="multipart/form-data" style="display:grid;gap:.65rem">
                    @csrf
                    <div>
                        <label for="production_document" style="font-size:.78rem;font-weight:700">Upload production document (DOC, DOCX, or PDF)</label>
                        <input id="production_document" type="file" name="production_document" accept=".doc,.docx,.pdf" required class="mp-input" style="margin-top:.35rem">
                        @error('production_document')<p style="margin:.35rem 0 0;font-size:.78rem;color:#b91c1c">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="mp-btn mp-btn-primary">{{ $currentFile ? 'Replace production document' : 'Upload production document' }}</button>
                </form>
            @endif
        </div>
    </div>

    @if($canEdit && $submission->status !== SubmissionStatus::READY_TO_PUBLISH)
        <div class="mp-card">
            <div class="mp-card__head">
                <div>
                    <h2 class="mp-card__title">Production checklist</h2>
                    <p class="mp-card__desc">Confirm formatting before marking production complete.</p>
                </div>
            </div>
            <div class="mp-card__body">
                <form method="POST" action="{{ route('production.queue.complete', $submission) }}" style="display:grid;gap:.85rem">
                    @csrf
                    <div style="display:grid;gap:.45rem">
                        @foreach(ProductionChecklist::labels() as $key => $label)
                            @if($key !== 'document_uploaded')
                                <label style="display:flex;align-items:center;gap:.5rem;font-size:.86rem">
                                    <input type="checkbox" name="checklist[{{ $key }}]" value="1" @checked($checklist[$key] ?? false)>
                                    {{ $label }}
                                </label>
                            @endif
                        @endforeach
                    </div>
                    <button type="submit" class="mp-btn mp-btn-primary" @disabled(! $currentFile)>Mark production complete</button>
                    @unless($currentFile)
                        <p style="margin:0;font-size:.78rem;color:#b45309">Upload the final production document first.</p>
                    @endunless
                </form>
            </div>
        </div>
    @elseif($submission->status === SubmissionStatus::READY_TO_PUBLISH)
        <div class="mp-card" style="border-color:#86efac;background:#f0fdf4">
            <div class="mp-card__body">
                <p style="margin:0;font-weight:700;color:#047857">Production complete</p>
                <p style="margin:.35rem 0 0;font-size:.86rem;color:#065f46">Editors can now publish this manuscript to its issue using the production document.</p>
            </div>
        </div>
    @endif
</div>
@endsection
