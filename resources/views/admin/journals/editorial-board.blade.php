@php
    $board = $board ?? $journal->editorialBoard()->orderBy('sort_order')->get();
    $manageJournal = $manageJournal ?? null;
    $canMutate = $canMutate ?? true;
    $storeUrl = $manageJournal
        ? route('journal.manage.editorial-board.store', $manageJournal)
        : route('admin.editorial-board.store', $journal);
    $destroyUrl = fn ($member) => $manageJournal
        ? route('journal.manage.editorial-board.destroy', [$manageJournal, $member])
        : route('admin.editorial-board.destroy', [$journal, $member]);
@endphp

<section class="jf-card" style="max-width:72rem;margin-top:1rem" id="editorial-board">
    <div class="jf-card__head">
        <h2 class="jf-card__title">Editorial board</h2>
        <p class="jf-card__desc">Shown on the public editorial board page for this journal.</p>
    </div>
    <div class="jf-card__body" style="display:grid;gap:1.1rem">
        @if($board->isEmpty())
            <p style="margin:0;font-size:.875rem;color:var(--muted)">No board members yet.</p>
        @else
            <div style="display:grid;gap:.55rem">
                @foreach($board as $member)
                    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.75rem;padding:.8rem 1rem;border:1px solid var(--line);border-radius:.85rem;background:#f8fafc">
                        <div style="flex:1;min-width:12rem">
                            <p style="margin:0;font-weight:700;font-size:.9rem">{{ $member->name }}</p>
                            <p style="margin:.2rem 0 0;font-size:.78rem;color:var(--muted)">
                                {{ $member->role_title ?: 'Board member' }}
                                @if($member->affiliation) · {{ $member->affiliation }} @endif
                            </p>
                        </div>
                        @if($canMutate)
                            <form method="POST" action="{{ $destroyUrl($member) }}" onsubmit="return confirm('Remove {{ $member->name }} from the editorial board?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="admin-btn admin-btn-secondary" style="padding:.4rem .65rem;font-size:.75rem;color:#b91c1c;border-color:#fecaca">Remove</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if($canMutate)
            <form method="POST" action="{{ $storeUrl }}" style="display:grid;gap:.85rem;border-top:1px solid var(--line);padding-top:1rem">
                @csrf
                <h3 style="margin:0;font-size:.88rem;font-weight:800">Add board member</h3>
                <div class="jf-grid jf-grid--2">
                    <div class="jf-field">
                        <label for="board_name">Name</label>
                        <input id="board_name" name="name" type="text" class="jf-input" required value="{{ old('name') }}">
                        @error('name')<p class="jf-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="jf-field">
                        <label for="board_role">Role title</label>
                        <input id="board_role" name="role_title" type="text" class="jf-input" value="{{ old('role_title') }}" placeholder="Editor-in-Chief">
                    </div>
                    <div class="jf-field">
                        <label for="board_affiliation">Affiliation</label>
                        <input id="board_affiliation" name="affiliation" type="text" class="jf-input" value="{{ old('affiliation') }}">
                    </div>
                    <div class="jf-field">
                        <label for="board_email">Email</label>
                        <input id="board_email" name="email" type="email" class="jf-input" value="{{ old('email') }}">
                    </div>
                    <div class="jf-field">
                        <label for="board_orcid">ORCID</label>
                        <input id="board_orcid" name="orcid" type="text" class="jf-input" value="{{ old('orcid') }}">
                    </div>
                    <div class="jf-field">
                        <label for="board_sort">Sort order</label>
                        <input id="board_sort" name="sort_order" type="number" min="0" class="jf-input" value="{{ old('sort_order', 0) }}">
                    </div>
                </div>
                <div>
                    <button type="submit" class="admin-btn admin-btn-primary">Add member</button>
                </div>
            </form>
        @endif
    </div>
</section>
