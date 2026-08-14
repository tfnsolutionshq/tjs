@extends('layouts.journal-manage')

@section('title', 'Settings | '.$journal->title)
@section('page_title', 'Journal settings')
@section('page_subtitle', 'Profile, theme, platform access, and editorial board')

@section('page_actions')
    <a href="{{ route('journals.show', $journal) }}" target="_blank" rel="noopener" class="admin-chip">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-6.5 9.5-6.5S21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="2.5"/></svg>
        Preview
    </a>
@endsection

@section('content')
    @unless($canMutate ?? true)
        <div style="max-width:72rem;margin-bottom:1rem;padding:1rem 1.15rem;border:1px solid #fde68a;border-radius:1rem;background:#fffbeb">
            <p style="margin:0;font-weight:700;color:#92400e">You can view these settings, but platform edits are currently disabled for this journal.</p>
        </div>
    @endunless

    @include('admin.journals.form', [
        'journal' => $journal,
        'theme' => $theme,
        'manageJournal' => $manageJournal,
        'platformEditsLocked' => ! ($canMutate ?? true),
    ])

    @include('admin.journals.editorial-board', [
        'journal' => $journal,
        'board' => $board ?? $journal->editorialBoard,
        'manageJournal' => $manageJournal,
        'canMutate' => $canMutate ?? true,
    ])
@endsection
