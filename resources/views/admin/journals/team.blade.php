@php
    use App\Support\JournalTeamRoles;
    $team = $team ?? $journal->teamMembers();
@endphp

<section class="jf-card" style="max-width:72rem;margin-top:1rem" id="journal-team">
    <div class="jf-card__head">
        <h2 class="jf-card__title">Journal team</h2>
        <p class="jf-card__desc">
            Assign a journal admin now or later — you can always add one here after creating the journal.
            Journal admins and editors manage this journal at
            <a href="{{ route('journal.manage.dashboard', $journal) }}" style="color:var(--blue);font-weight:600">{{ url('/j/'.$journal->slug.'/manage') }}</a>.
            They do not get platform-wide admin access.
        </p>
    </div>
    <div class="jf-card__body" style="display:grid;gap:1.25rem">
        @if($team->isEmpty())
            <div style="padding:.9rem 1rem;border:1px dashed #f59e0b;border-radius:.85rem;background:#fffbeb">
                <p style="margin:0;font-size:.9rem;font-weight:700;color:#92400e">No journal admin assigned yet</p>
                <p style="margin:.35rem 0 0;font-size:.8rem;color:#a16207;line-height:1.45">
                    Create a new account or assign an existing user below. Until then, only platform admins can manage this journal
                    (and only while platform edits are allowed).
                </p>
            </div>
        @else
            <div style="display:grid;gap:.65rem">
                @foreach($team as $member)
                    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.75rem;padding:.85rem 1rem;border:1px solid var(--line);border-radius:.85rem;background:#f8fafc">
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:2.25rem;height:2.25rem;border-radius:999px;background:#e2e8f0;font-weight:700;font-size:.85rem;color:#334155">
                            {{ strtoupper(substr($member->name, 0, 1)) }}
                        </span>
                        <div style="flex:1;min-width:10rem">
                            <p style="margin:0;font-weight:700;font-size:.9rem;color:var(--ink)">{{ $member->name }}</p>
                            <p style="margin:.15rem 0 0;font-size:.78rem;color:var(--muted)">{{ $member->email }}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.journals.team.update', [$journal, $member]) }}" style="display:flex;gap:.4rem;align-items:center">
                            @csrf
                            @method('PUT')
                            <select name="role" class="jf-select" style="width:auto;min-width:9rem;padding:.45rem .65rem;font-size:.8rem" onchange="this.form.submit()">
                                @foreach(JournalTeamRoles::all() as $role)
                                    <option value="{{ $role }}" @selected($member->pivot->role === $role)>{{ JournalTeamRoles::label($role) }}</option>
                                @endforeach
                            </select>
                        </form>
                        <form method="POST" action="{{ route('admin.journals.team.destroy', [$journal, $member]) }}" onsubmit="return confirm('Remove {{ $member->name }} from this journal’s team?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="admin-btn admin-btn-secondary" style="padding:.45rem .7rem;font-size:.78rem;color:#b91c1c;border-color:#fecaca">Remove</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif

        <div
            style="border-top:1px solid var(--line);padding-top:1.1rem"
            x-data="{ mode: 'create' }"
        >
            <h3 style="margin:0 0 .75rem;font-size:.88rem;font-weight:800;color:var(--ink)">Add team member</h3>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:.9rem">
                <button type="button" class="admin-btn admin-btn-secondary" :style="mode === 'create' && 'border-color:#94a3b8'" @click="mode = 'create'">Create new account</button>
                <button type="button" class="admin-btn admin-btn-secondary" :style="mode === 'existing' && 'border-color:#94a3b8'" @click="mode = 'existing'">Existing user</button>
            </div>

            <form method="POST" action="{{ route('admin.journals.team.store', $journal) }}" style="display:grid;gap:.85rem" x-show="mode === 'create'">
                @csrf
                <input type="hidden" name="mode" value="create">
                <div class="jf-grid jf-grid--2">
                    <div class="jf-field">
                        <label for="team_name">Full name</label>
                        <input id="team_name" name="name" type="text" class="jf-input" value="{{ old('mode') === 'create' ? old('name') : '' }}" required>
                        @error('name')<p class="jf-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="jf-field">
                        <label for="team_email_create">Email</label>
                        <input id="team_email_create" name="email" type="email" class="jf-input" value="{{ old('mode') === 'create' ? old('email') : '' }}" required>
                        @error('email')<p class="jf-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="jf-field">
                        <label for="team_password">Temporary password</label>
                        <input id="team_password" name="password" type="password" class="jf-input" autocomplete="new-password" required>
                        @error('password')<p class="jf-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="jf-field">
                        <label for="team_password_confirmation">Confirm password</label>
                        <input id="team_password_confirmation" name="password_confirmation" type="password" class="jf-input" autocomplete="new-password" required>
                    </div>
                    <div class="jf-field">
                        <label for="team_role_create">Role</label>
                        <select id="team_role_create" name="role" class="jf-select">
                            @foreach(JournalTeamRoles::all() as $role)
                                <option value="{{ $role }}" @selected(old('role', JournalTeamRoles::ADMIN) === $role)>{{ JournalTeamRoles::label($role) }}</option>
                            @endforeach
                        </select>
                        <p class="jf-hint">{{ JournalTeamRoles::description(JournalTeamRoles::ADMIN) }}</p>
                    </div>
                </div>
                <div>
                    <button type="submit" class="admin-btn admin-btn-primary">Create &amp; assign</button>
                </div>
            </form>

            <form method="POST" action="{{ route('admin.journals.team.store', $journal) }}" style="display:grid;gap:.85rem" x-show="mode === 'existing'" x-cloak>
                @csrf
                <input type="hidden" name="mode" value="existing">
                <div class="jf-grid jf-grid--2">
                    <div class="jf-field">
                        <label for="team_email_existing">User email</label>
                        <input id="team_email_existing" name="email" type="email" class="jf-input" value="{{ old('mode') === 'existing' ? old('email') : '' }}" placeholder="person@example.com" required>
                        @error('email')<p class="jf-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="jf-field">
                        <label for="team_role_existing">Role</label>
                        <select id="team_role_existing" name="role" class="jf-select">
                            @foreach(JournalTeamRoles::all() as $role)
                                <option value="{{ $role }}" @selected(old('role', JournalTeamRoles::ADMIN) === $role)>{{ JournalTeamRoles::label($role) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <button type="submit" class="admin-btn admin-btn-primary">Assign to journal</button>
                </div>
            </form>
        </div>
    </div>
</section>
