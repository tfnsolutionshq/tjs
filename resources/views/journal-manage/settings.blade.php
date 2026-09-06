@extends('layouts.journal-manage')

@section('title', 'Settings | '.$journal->title)
@section('page_title', 'Journal settings')
@section('page_subtitle', 'Profile, theme, categories, and editorial board')

@section('page_actions')
    <a href="{{ route('journals.show', $journal) }}" target="_blank" rel="noopener" class="admin-chip">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-6.5 9.5-6.5S21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="2.5"/></svg>
        Preview
    </a>
@endsection

@section('content')
    @unless($canMutate ?? true)
        <div style="width:100%;margin-bottom:1rem;padding:1rem 1.15rem;border:1px solid #fde68a;border-radius:1rem;background:#fffbeb">
            @if($journal->activationLocked())
                <p style="margin:0;font-weight:700;color:#92400e">Settings are view-only until activation is current.</p>
                <p style="margin:.4rem 0 0;font-size:.84rem;color:#a16207">
                    <a href="{{ route('journal.manage.activation.show', $journal) }}" style="font-weight:700;color:#92400e">View activation status</a>
                    to unlock editing.
                </p>
            @else
                <p style="margin:0;font-weight:700;color:#92400e">You can view these settings, but platform edits are currently disabled for this journal.</p>
            @endif
        </div>
    @endunless

    @include('admin.journals.form', [
        'journal' => $journal,
        'theme' => $theme,
        'manageJournal' => $manageJournal,
        'platformEditsLocked' => ! ($canMutate ?? true),
        'canMutate' => $canMutate ?? true,
    ])

    <section class="admin-panel" style="width:100%;margin:1rem 0">
        <div class="admin-panel__head">
            <div>
                <h2 style="margin:0;font-size:.95rem;font-weight:800">Categories</h2>
                <p style="margin:.25rem 0 0;font-size:.76rem;color:var(--muted)">Private to this journal only — not shared with other journals. Used on author submissions and article forms.</p>
            </div>
        </div>
        <div class="admin-panel__body" style="display:grid;gap:1rem">
            @if($canMutate ?? true)
                <form method="POST" action="{{ route('journal.manage.settings.categories.store', $journal) }}" class="cat-add">
                    @csrf
                    <input id="new_category_name" name="name" type="text" required class="cat-input" placeholder="New category name (e.g. Methods Paper)" value="{{ old('name') }}" aria-label="New category name">
                    <select id="new_category_active" name="is_active" class="cat-input" aria-label="Status">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                    <button type="submit" class="admin-btn admin-btn-primary">Add</button>
                </form>
                @error('name')<p style="margin:0;font-size:.75rem;color:#b91c1c">{{ $message }}</p>@enderror
            @endif

            @if(($categories ?? collect())->isEmpty())
                <p style="margin:0;font-size:.84rem;color:var(--muted)">No categories yet.</p>
            @else
                <div class="cat-table" role="table" aria-label="Journal categories">
                    <div class="cat-table__head" role="row">
                        <span role="columnheader">Name</span>
                        <span role="columnheader">Sort</span>
                        <span role="columnheader">Status</span>
                        <span role="columnheader" class="cat-table__actions-h">{{ ($canMutate ?? true) ? 'Actions' : '' }}</span>
                    </div>
                    @foreach($categories as $category)
                        <form method="POST" action="{{ route('journal.manage.settings.categories.update', [$journal, $category]) }}" class="cat-table__row" role="row">
                            @csrf
                            @method('PUT')
                            <div class="cat-table__name" role="cell">
                                <input name="name" type="text" required value="{{ $category->name }}" @disabled(! ($canMutate ?? true)) class="cat-input" aria-label="Category name">
                                <span class="cat-table__meta">{{ $category->articles_count }} {{ \Illuminate\Support\Str::plural('article', $category->articles_count) }}</span>
                            </div>
                            <div role="cell">
                                <input name="sort_order" type="number" min="0" value="{{ $category->sort_order }}" @disabled(! ($canMutate ?? true)) class="cat-input cat-input--sm" aria-label="Sort order">
                            </div>
                            <div role="cell">
                                <select name="is_active" @disabled(! ($canMutate ?? true)) class="cat-input" aria-label="Status">
                                    <option value="1" @selected($category->is_active)>Active</option>
                                    <option value="0" @selected(! $category->is_active)>Inactive</option>
                                </select>
                            </div>
                            <div class="cat-table__actions" role="cell">
                                @if($canMutate ?? true)
                                    <button type="submit" class="admin-btn admin-btn-primary cat-btn">Save</button>
                                    @if($category->articles_count === 0)
                                        <button type="submit" form="delete-category-{{ $category->id }}" class="cat-link-danger" onclick="return confirm('Delete {{ $category->name }}?')">Delete</button>
                                    @endif
                                @endif
                            </div>
                        </form>
                        @if(($canMutate ?? true) && $category->articles_count === 0)
                            <form id="delete-category-{{ $category->id }}" method="POST" action="{{ route('journal.manage.settings.categories.destroy', [$journal, $category]) }}" hidden>
                                @csrf
                                @method('DELETE')
                            </form>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <style>
        .cat-add {
            display: grid;
            gap: .65rem;
            grid-template-columns: 1fr 8.5rem auto;
            align-items: center;
            padding: .85rem;
            border: 1px dashed #cbd5e1;
            border-radius: .85rem;
            background: #f8fafc;
        }
        .cat-input {
            width: 100%;
            padding: .55rem .75rem;
            border-radius: .65rem;
            border: 1px solid #e2e8f0;
            background: #fff;
            font: inherit;
            font-size: .875rem;
        }
        .cat-input--sm { max-width: 5.5rem; }
        .cat-table { display: grid; gap: 0; border: 1px solid var(--line); border-radius: .85rem; overflow: hidden; }
        .cat-table__head,
        .cat-table__row {
            display: grid;
            grid-template-columns: minmax(0, 1.6fr) 4.5rem 7.5rem auto;
            gap: .65rem;
            align-items: center;
            padding: .65rem .85rem;
        }
        .cat-table__head {
            background: #f1f5f9;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .02em;
            text-transform: uppercase;
            color: #64748b;
            border-bottom: 1px solid var(--line);
        }
        .cat-table__row + .cat-table__row,
        .cat-table__row + form + .cat-table__row { border-top: 1px solid var(--line); }
        .cat-table__name { min-width: 0; }
        .cat-table__meta { display: block; margin-top: .25rem; font-size: .7rem; color: var(--muted); }
        .cat-table__actions { display: flex; align-items: center; gap: .55rem; justify-content: flex-end; white-space: nowrap; }
        .cat-table__actions-h { text-align: right; }
        .cat-btn { padding: .4rem .7rem; font-size: .76rem; }
        .cat-link-danger {
            border: 0; background: transparent; padding: 0;
            font: inherit; font-size: .76rem; font-weight: 600;
            color: #b91c1c; cursor: pointer;
        }
        .cat-link-danger:hover { text-decoration: underline; }
        @media (max-width: 720px) {
            .cat-add { grid-template-columns: 1fr; }
            .cat-table__head { display: none; }
            .cat-table__head,
            .cat-table__row { grid-template-columns: 1fr 1fr; }
            .cat-table__name { grid-column: 1 / -1; }
            .cat-table__actions { grid-column: 1 / -1; justify-content: flex-start; }
            .cat-input--sm { max-width: none; }
        }
    </style>

    @include('admin.journals.editorial-board', [
        'journal' => $journal,
        'board' => $board ?? $journal->editorialBoard,
        'manageJournal' => $manageJournal,
        'canMutate' => $canMutate ?? true,
    ])
@endsection
