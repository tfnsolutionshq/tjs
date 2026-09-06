@php
    $board = $board ?? $journal->editorialBoard()->orderBy('sort_order')->get();
    $manageJournal = $manageJournal ?? null;
    $canMutate = $canMutate ?? true;
    $roleOptions = \App\Support\EditorialBoardRoles::options();
    $oldRole = old('role_title');
    $roleIsCustom = filled($oldRole) && ! in_array($oldRole, $roleOptions, true);
    $storeUrl = $manageJournal
        ? route('journal.manage.editorial-board.store', $manageJournal)
        : route('admin.editorial-board.store', $journal);
    $destroyUrl = fn ($member) => $manageJournal
        ? route('journal.manage.editorial-board.destroy', [$manageJournal, $member])
        : route('admin.editorial-board.destroy', [$journal, $member]);
@endphp

<section class="jf-card" style="width:100%;margin-top:1rem" id="editorial-board">
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
                        <x-form-label for="board_name" field="board.name" :required="true" reqClass="jf-req">Name</x-form-label>
                        <input id="board_name" name="name" type="text" class="jf-input" required value="{{ old('name') }}">
                        @error('name')<p class="jf-error">{{ $message }}</p>@enderror
                    </div>
                    <div
                        class="jf-field"
                        x-data="{
                            role: @js($roleIsCustom ? '__other' : ($oldRole ?: '')),
                            custom: @js($roleIsCustom ? $oldRole : ''),
                        }"
                    >
                        <x-form-label for="board_role" field="board.role">Role title</x-form-label>
                        <select
                            id="board_role"
                            class="jf-input"
                            x-model="role"
                            @change="if (role !== '__other') custom = ''"
                        >
                            <option value="">Select a role…</option>
                            @foreach($roleOptions as $role)
                                <option value="{{ $role }}">{{ $role }}</option>
                            @endforeach
                            <option value="__other">Other…</option>
                        </select>
                        <input
                            type="text"
                            class="jf-input"
                            style="margin-top:.55rem"
                            placeholder="Custom role title"
                            x-show="role === '__other'"
                            x-cloak
                            x-model="custom"
                            :required="role === '__other'"
                        >
                        <input type="hidden" name="role_title" :value="role === '__other' ? custom : role">
                        @error('role_title')<p class="jf-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="jf-field">
                        <x-form-label for="board_affiliation" field="board.affiliation">Affiliation</x-form-label>
                        <input id="board_affiliation" name="affiliation" type="text" class="jf-input" value="{{ old('affiliation') }}">
                    </div>
                    <div class="jf-field">
                        <x-form-label for="board_email" field="board.email">Email</x-form-label>
                        <input id="board_email" name="email" type="email" class="jf-input" value="{{ old('email') }}">
                    </div>
                    <div class="jf-field">
                        <x-form-label for="board_orcid" field="board.orcid">ORCID</x-form-label>
                        <input id="board_orcid" name="orcid" type="text" class="jf-input" value="{{ old('orcid') }}">
                    </div>
                    <div class="jf-field">
                        <x-form-label for="board_sort" field="board.sort_order">Sort order</x-form-label>
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
