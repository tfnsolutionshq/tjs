@extends('layouts.admin')

@section('title', $user->name.' | Users | Admin')
@section('page_title', $user->name)
@section('page_subtitle', $user->email)

@section('page_actions')
    <a href="{{ route('admin.users.index') }}" class="admin-btn admin-btn-secondary">All users</a>
@endsection

@section('content')
@php
    use App\Support\JournalTeamRoles;
    $badge = match ($user->role) {
        'admin' => ['bg' => '#ecfdf5', 'color' => '#047857', 'label' => 'Platform admin'],
        'reviewer' => ['bg' => '#eff6ff', 'color' => '#1d4ed8', 'label' => 'Reviewer'],
        'editor' => ['bg' => '#fff7ed', 'color' => '#c2410c', 'label' => 'Editor (legacy)'],
        default => ['bg' => '#f1f5f9', 'color' => '#475569', 'label' => 'Member'],
    };
@endphp

<style>
    .us { display:grid; gap:1rem; max-width:72rem; }
    @media (min-width:1100px){ .us{ grid-template-columns:minmax(0,1fr) 18rem; align-items:start; } }
    .us-main { display:grid; gap:1rem; min-width:0; }
    .us-side { display:grid; gap:1rem; }
    .us-card {
        background:#fff; border:1px solid var(--line); border-radius:1.05rem;
        box-shadow:0 8px 24px rgba(15,23,42,.035); overflow:hidden;
    }
    .us-card__head { padding:1rem 1.15rem .2rem; }
    .us-card__title { margin:0; font-size:.95rem; font-weight:800; color:var(--ink); }
    .us-card__desc { margin:.3rem 0 0; font-size:.8rem; color:var(--muted); line-height:1.45; }
    .us-card__body { padding:1rem 1.15rem 1.2rem; display:grid; gap:.85rem; }
    .us-hero {
        display:flex; flex-wrap:wrap; align-items:center; gap:1rem; padding:1.15rem 1.2rem;
        background:#fff; border:1px solid var(--line); border-radius:1.05rem;
        box-shadow:0 8px 24px rgba(15,23,42,.035);
    }
    .us-avatar {
        width:3.4rem; height:3.4rem; border-radius:999px; display:inline-flex; align-items:center; justify-content:center;
        font-weight:800; font-size:1.2rem; color:#fff; background:linear-gradient(145deg,#2f7de1,#1f5fb8);
    }
    .us-name { margin:0; font-size:1.15rem; font-weight:800; color:var(--ink); }
    .us-meta { margin:.25rem 0 0; font-size:.84rem; color:var(--muted); }
    .us-badge {
        display:inline-flex; margin-top:.4rem; font-size:.68rem; font-weight:700; letter-spacing:.04em;
        text-transform:uppercase; padding:.28rem .55rem; border-radius:999px;
    }
    .us-field label { display:block; margin-bottom:.35rem; font-size:.76rem; font-weight:700; color:#334155; }
    .us-field .us-error { margin:.3rem 0 0; font-size:.74rem; font-weight:600; color:#b91c1c; }
    .us-input, .us-select, .us-textarea {
        width:100%; border:1px solid #e2e8f0; border-radius:.7rem; padding:.65rem .8rem;
        font:inherit; font-size:.88rem; color:var(--ink); background:#fff; outline:none;
    }
    .us-textarea { min-height:5.5rem; resize:vertical; line-height:1.45; }
    .us-input:focus, .us-select:focus, .us-textarea:focus {
        border-color:#93c5fd; box-shadow:0 0 0 3px rgba(47,125,225,.14);
    }
    .us-grid { display:grid; gap:.8rem; }
    @media (min-width:720px){ .us-grid--2{ grid-template-columns:1fr 1fr; } }
    .us-stat {
        background:#f8fafc; border:1px solid var(--line); border-radius:.85rem; padding:.75rem .85rem;
    }
    .us-stat__label { margin:0; font-size:.66rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--muted); }
    .us-stat__value { margin:.25rem 0 0; font-size:1.25rem; font-weight:800; color:var(--ink); }
    .us-row {
        display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.5rem;
        padding:.7rem 0; border-bottom:1px solid #f1f5f9;
    }
    .us-row:last-child { border-bottom:0; }
    .us-row__title { margin:0; font-size:.86rem; font-weight:700; color:var(--ink); }
    .us-row__meta { margin:.15rem 0 0; font-size:.74rem; color:var(--muted); }
    .us-empty { margin:0; font-size:.84rem; color:var(--muted); }
</style>

<div class="us">
    <div class="us-main">
        <section class="us-hero">
            <span class="us-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
            <div class="min-w-0">
                <h2 class="us-name">{{ $user->name }}</h2>
                <p class="us-meta">{{ $user->email }} · Joined {{ optional($user->created_at)->format('M j, Y') }}</p>
                <span class="us-badge" style="background:{{ $badge['bg'] }};color:{{ $badge['color'] }}">{{ $badge['label'] }}</span>
            </div>
        </section>

        <section class="us-card">
            <div class="us-card__head">
                <h2 class="us-card__title">Account details</h2>
                <p class="us-card__desc">Platform role and profile fields. Journal-specific roles are managed on each journal’s team page.</p>
            </div>
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="us-card__body">
                @csrf
                @method('PUT')

                <div class="us-grid us-grid--2">
                    <div class="us-field">
                        <label for="name">Full name</label>
                        <input id="name" name="name" type="text" class="us-input" value="{{ old('name', $user->name) }}" required>
                        @error('name')<p class="us-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="us-field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" class="us-input" value="{{ old('email', $user->email) }}" required>
                        @error('email')<p class="us-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="us-field">
                        <label for="role">Platform role</label>
                        <select id="role" name="role" class="us-select">
                            <option value="admin" @selected(old('role', $user->role) === 'admin')>Platform admin</option>
                            <option value="member" @selected(old('role', $user->role) === 'member')>Member</option>
                            <option value="reviewer" @selected(old('role', $user->role) === 'reviewer')>Reviewer</option>
                        </select>
                        @error('role')<p class="us-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="us-field">
                        <label for="position">Position</label>
                        <input id="position" name="position" type="text" class="us-input" value="{{ old('position', $user->position) }}">
                    </div>
                    <div class="us-field">
                        <label for="affiliation">Affiliation</label>
                        <input id="affiliation" name="affiliation" type="text" class="us-input" value="{{ old('affiliation', $user->affiliation) }}">
                    </div>
                    <div class="us-field">
                        <label for="orcid">ORCID</label>
                        <input id="orcid" name="orcid" type="text" class="us-input" value="{{ old('orcid', $user->orcid) }}">
                    </div>
                    <div class="us-field" style="grid-column:1 / -1">
                        <label for="bio">Bio</label>
                        <textarea id="bio" name="bio" class="us-textarea">{{ old('bio', $user->bio) }}</textarea>
                    </div>
                </div>

                <label style="display:flex;align-items:center;gap:.55rem;font-size:.86rem;font-weight:600;color:var(--ink)">
                    <input type="hidden" name="is_public_reviewer" value="0">
                    <input type="checkbox" name="is_public_reviewer" value="1" @checked(old('is_public_reviewer', $user->is_public_reviewer))>
                    List as public reviewer
                </label>

                <div>
                    <button type="submit" class="admin-btn admin-btn-primary">Save user</button>
                </div>
            </form>
        </section>

        <section class="us-card">
            <div class="us-card__head">
                <h2 class="us-card__title">Journal teams</h2>
                <p class="us-card__desc">Journals this user can manage or review. Assign more from a journal’s edit page.</p>
            </div>
            <div class="us-card__body">
                @forelse($user->journals as $journal)
                    <div class="us-row">
                        <div>
                            <p class="us-row__title">{{ $journal->title }}</p>
                            <p class="us-row__meta">{{ JournalTeamRoles::label($journal->pivot->role) }}</p>
                        </div>
                        <div style="display:flex;gap:.4rem">
                            <a href="{{ route('admin.journals.edit', $journal) }}#journal-team" class="admin-btn admin-btn-secondary" style="padding:.35rem .65rem;font-size:.72rem">Team</a>
                            @if(in_array($journal->pivot->role, JournalTeamRoles::manageRoles(), true))
                                <a href="{{ route('journal.manage.dashboard', $journal) }}" class="admin-btn admin-btn-secondary" style="padding:.35rem .65rem;font-size:.72rem">Portal</a>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="us-empty">Not assigned to any journal team.</p>
                @endforelse
            </div>
        </section>

        <section class="us-card">
            <div class="us-card__head">
                <h2 class="us-card__title">Recent submissions</h2>
            </div>
            <div class="us-card__body">
                @forelse($user->submissions as $submission)
                    <div class="us-row">
                        <div>
                            <p class="us-row__title">{{ $submission->title }}</p>
                            <p class="us-row__meta">{{ $submission->journal?->title }} · {{ str_replace('_', ' ', $submission->status) }}</p>
                        </div>
                        <a href="{{ route('admin.submissions.show', $submission) }}" class="admin-btn admin-btn-secondary" style="padding:.35rem .65rem;font-size:.72rem">Open</a>
                    </div>
                @empty
                    <p class="us-empty">No submissions yet.</p>
                @endforelse
            </div>
        </section>
    </div>

    <aside class="us-side">
        <section class="us-card">
            <div class="us-card__head">
                <h2 class="us-card__title">Overview</h2>
            </div>
            <div class="us-card__body">
                <div class="us-stat">
                    <p class="us-stat__label">Journal teams</p>
                    <p class="us-stat__value">{{ number_format($user->journals_count) }}</p>
                </div>
                <div class="us-stat">
                    <p class="us-stat__label">Submissions</p>
                    <p class="us-stat__value">{{ number_format($user->submissions_count) }}</p>
                </div>
                <div class="us-stat">
                    <p class="us-stat__label">Memberships</p>
                    <p class="us-stat__value">{{ number_format($user->memberships_count) }}</p>
                </div>
                <div class="us-stat">
                    <p class="us-stat__label">Purchases</p>
                    <p class="us-stat__value">{{ number_format($user->purchases_count) }}</p>
                </div>
            </div>
        </section>

        <section class="us-card">
            <div class="us-card__head">
                <h2 class="us-card__title">Memberships</h2>
            </div>
            <div class="us-card__body">
                @forelse($user->memberships as $membership)
                    <div class="us-row">
                        <div>
                            <p class="us-row__title">{{ $membership->plan?->name ?? 'Membership' }}</p>
                            <p class="us-row__meta">{{ $membership->status }} · ends {{ optional($membership->ends_at)->format('M j, Y') }}</p>
                        </div>
                    </div>
                @empty
                    <p class="us-empty">No memberships.</p>
                @endforelse
            </div>
        </section>
    </aside>
</div>
@endsection
