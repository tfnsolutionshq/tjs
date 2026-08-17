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

    <section class="admin-panel" style="max-width:72rem;margin:1rem 0">
        <div class="admin-panel__head">
            <div>
                <h2 style="margin:0;font-size:.95rem;font-weight:800">Categories</h2>
                <p style="margin:.25rem 0 0;font-size:.76rem;color:var(--muted)">Unique to this journal. Used on author submissions and article forms.</p>
            </div>
        </div>
        <div class="admin-panel__body" style="display:grid;gap:1rem">
            @if($canMutate ?? true)
                <form method="POST" action="{{ route('journal.manage.settings.categories.store', $journal) }}" style="display:grid;gap:.75rem;grid-template-columns:1fr auto auto;align-items:end">
                    @csrf
                    <div>
                        <label for="new_category_name" style="display:block;margin-bottom:.35rem;font-size:.78rem;font-weight:700;color:#334155">New category</label>
                        <input id="new_category_name" name="name" type="text" required style="width:100%;padding:.65rem .85rem;border-radius:.7rem;border:1px solid #e2e8f0;background:#fff;font:inherit" placeholder="e.g. Methods Paper" value="{{ old('name') }}">
                        @error('name')<p style="margin:.35rem 0 0;font-size:.75rem;color:#b91c1c">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="new_category_active" style="display:block;margin-bottom:.35rem;font-size:.78rem;font-weight:700;color:#334155">Status</label>
                        <select id="new_category_active" name="is_active" style="padding:.65rem .85rem;border-radius:.7rem;border:1px solid #e2e8f0;background:#fff;font:inherit">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="admin-btn admin-btn-primary">Add</button>
                </form>
            @endif

            @forelse($categories ?? [] as $category)
                <form method="POST" action="{{ route('journal.manage.settings.categories.update', [$journal, $category]) }}" style="display:grid;gap:.65rem;grid-template-columns:1.5fr .6fr .8fr auto;align-items:end;padding:.85rem 0;border-top:1px solid var(--line)">
                    @csrf
                    @method('PUT')
                    <div>
                        <label style="display:block;margin-bottom:.35rem;font-size:.72rem;font-weight:700;color:var(--muted)">Name</label>
                        <input name="name" type="text" required value="{{ $category->name }}" @disabled(! ($canMutate ?? true)) style="width:100%;padding:.55rem .75rem;border-radius:.65rem;border:1px solid #e2e8f0;font:inherit">
                        <p style="margin:.3rem 0 0;font-size:.72rem;color:var(--muted)">{{ $category->articles_count }} {{ \Illuminate\Support\Str::plural('article', $category->articles_count) }}</p>
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:.35rem;font-size:.72rem;font-weight:700;color:var(--muted)">Sort</label>
                        <input name="sort_order" type="number" min="0" value="{{ $category->sort_order }}" @disabled(! ($canMutate ?? true)) style="width:100%;padding:.55rem .75rem;border-radius:.65rem;border:1px solid #e2e8f0;font:inherit">
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:.35rem;font-size:.72rem;font-weight:700;color:var(--muted)">Status</label>
                        <select name="is_active" @disabled(! ($canMutate ?? true)) style="width:100%;padding:.55rem .75rem;border-radius:.65rem;border:1px solid #e2e8f0;font:inherit">
                            <option value="1" @selected($category->is_active)>Active</option>
                            <option value="0" @selected(! $category->is_active)>Inactive</option>
                        </select>
                    </div>
                    @if($canMutate ?? true)
                        <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                            <button type="submit" class="admin-btn admin-btn-primary" style="padding:.45rem .7rem;font-size:.76rem">Save</button>
                            @if($category->articles_count === 0)
                                <button type="submit" form="delete-category-{{ $category->id }}" class="admin-btn admin-btn-secondary" style="padding:.45rem .7rem;font-size:.76rem;color:#b91c1c" onclick="return confirm('Delete {{ $category->name }}?')">Delete</button>
                            @endif
                        </div>
                    @endif
                </form>
                @if(($canMutate ?? true) && $category->articles_count === 0)
                    <form id="delete-category-{{ $category->id }}" method="POST" action="{{ route('journal.manage.settings.categories.destroy', [$journal, $category]) }}" style="display:none">
                        @csrf
                        @method('DELETE')
                    </form>
                @endif
            @empty
                <p style="margin:0;font-size:.84rem;color:var(--muted)">No categories yet.</p>
            @endforelse
        </div>
    </section>

    @include('admin.journals.editorial-board', [
        'journal' => $journal,
        'board' => $board ?? $journal->editorialBoard,
        'manageJournal' => $manageJournal,
        'canMutate' => $canMutate ?? true,
    ])
@endsection
