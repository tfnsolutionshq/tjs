@extends('layouts.admin')

@section('title', 'Edit journal | Admin')
@section('page_title', 'Edit journal')
@section('page_subtitle', $journal->title)

@section('page_actions')
    <a href="{{ route('journals.show', $journal) }}" target="_blank" rel="noopener" class="admin-chip">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-6.5 9.5-6.5S21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="2.5"/></svg>
        Preview
    </a>
    <a href="{{ route('admin.volumes.index', $journal) }}" class="admin-btn admin-btn-secondary">
        Volumes
    </a>
@endsection

@section('content')
    @if(! empty($platformEditsLocked))
        <div style="width:100%;margin-bottom:1rem;padding:1rem 1.15rem;border:1px solid #fde68a;border-radius:1rem;background:#fffbeb">
            <p style="margin:0;font-weight:700;color:#92400e">Platform edits are disabled for this journal.</p>
            <p style="margin:.35rem 0 0;font-size:.86rem;color:#a16207">
                You can still assign a journal team below. A journal admin can re-enable platform edits under
                <strong>Manage → Settings</strong>.
            </p>
        </div>
    @endif

    @include('admin.journals.form', [
        'journal' => $journal,
        'theme' => $theme ?? $journal->themeConfig(),
        'platformEditsLocked' => ! empty($platformEditsLocked),
    ])

    @include('admin.journals.team', ['journal' => $journal, 'team' => $team ?? $journal->teamMembers()])

    @include('admin.journals.editorial-board', [
        'journal' => $journal,
        'board' => $journal->editorialBoard,
        'canMutate' => empty($platformEditsLocked),
    ])

    <section class="jf-danger" style="width:100%;margin-top:1rem" x-data="{ open: {{ $errors->has('confirm') ? 'true' : 'false' }} }">
        <div style="background:#fff;border:1px solid #fecaca;border-radius:1.05rem;overflow:hidden;box-shadow:0 8px 24px rgba(15,23,42,.035)">
            <div style="padding:1rem 1.15rem;border-bottom:1px solid #fecaca;background:#fef2f2">
                <h2 style="margin:0;font-size:.95rem;font-weight:800;color:#991b1b">Danger zone</h2>
                <p style="margin:.3rem 0 0;font-size:.8rem;color:#b91c1c;line-height:1.45">
                    Deleting this journal permanently removes its volumes, issues, articles, submissions, and branding files.
                </p>
            </div>
            <div style="padding:1rem 1.15rem">
                <button
                    type="button"
                    class="admin-btn"
                    style="background:#fff;color:#b91c1c;border:1px solid #fecaca"
                    @click="open = !open"
                >
                    Delete journal…
                </button>

                <div x-show="open" x-cloak x-transition.opacity.duration.120ms style="margin-top:1rem;padding-top:1rem;border-top:1px solid #fecaca">
                    <form
                        method="POST"
                        action="{{ route('admin.journals.destroy', $journal) }}"
                        style="display:grid;gap:.75rem;max-width:28rem"
                    >
                        @csrf
                        @method('DELETE')
                        <div>
                            <label for="confirm_delete" style="display:block;margin-bottom:.4rem;font-size:.78rem;font-weight:700;color:#334155">
                                Type <code style="font-size:.78rem;background:#f1f5f9;padding:.1rem .35rem;border-radius:.3rem">{{ $journal->slug }}</code> to confirm
                            </label>
                            <input
                                id="confirm_delete"
                                name="confirm"
                                type="text"
                                required
                                autocomplete="off"
                                style="width:100%;border:1px solid #e2e8f0;border-radius:.7rem;padding:.7rem .85rem;font:inherit;font-size:.9rem"
                                placeholder="{{ $journal->slug }}"
                            >
                            @error('confirm')
                                <p style="margin:.4rem 0 0;font-size:.75rem;font-weight:600;color:#b91c1c">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="admin-btn" style="background:#b91c1c;color:#fff;justify-content:center">
                            Permanently delete journal
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
