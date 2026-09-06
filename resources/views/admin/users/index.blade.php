@extends('layouts.admin')

@section('title', 'Users | Admin')
@section('page_title', 'Users')
@section('page_subtitle', 'Everyone with a TJS account')

@section('content')
@php
    $role = $role ?? request('role');
    $journalFilter = $journalFilter ?? request('journal');
    $journalId = $journalId ?? request('journal_id');
    $indexUrl = route('admin.users.index');
@endphp

<style>
    .au-stats { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; margin-bottom:1rem; }
    @media (min-width:960px){ .au-stats{ grid-template-columns:repeat(5,minmax(0,1fr)); } }
    .au-stat {
        background:#fff; border:1px solid var(--line); border-radius:.95rem; padding:.9rem 1rem;
        box-shadow:0 8px 24px rgba(15,23,42,.035); text-decoration:none; color:inherit;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .au-stat:hover { border-color:#93c5fd; }
    .au-stat.is-active { border-color:#93c5fd; box-shadow:0 0 0 3px rgba(37,99,235,.12); }
    .au-stat__label { font-size:.68rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--muted); }
    .au-stat__value { margin-top:.35rem; font-size:1.45rem; font-weight:800; letter-spacing:-.03em; color:var(--ink); line-height:1; }

    .au-toolbar {
        display:flex; flex-direction:column; gap:.75rem; background:#fff; border:1px solid var(--line);
        border-radius:1rem; padding:.85rem .95rem; margin-bottom:1rem;
        box-shadow:0 8px 24px rgba(15,23,42,.035);
    }
    @media (min-width:860px){ .au-toolbar{ flex-direction:row; align-items:center; } }
    .au-search {
        flex:1; display:flex; align-items:center; gap:.55rem; border:1px solid var(--line);
        border-radius:.75rem; background:#f8fafc; padding:.62rem .85rem; min-width:0;
    }
    .au-search:focus-within { border-color:#93c5fd; background:#fff; box-shadow:0 0 0 3px rgba(37,99,235,.12); }
    .au-search svg { width:1rem; height:1rem; color:var(--muted); flex-shrink:0; }
    .au-search input { width:100%; border:0; outline:0; background:transparent; font:inherit; font-size:.9rem; color:var(--ink); }
    .au-toolbar__filters {
        display:flex; flex-wrap:wrap; gap:.5rem; align-items:center;
    }
    @media (min-width:860px){ .au-toolbar__filters{ flex-shrink:0; } }
    .au-toolbar .tjs-select { min-width:10.5rem; }
    .au-toolbar .tjs-select--compact { min-width:7.5rem; }
    .au-toolbar .tjs-journal-picker { min-width:12rem; max-width:16rem; }

    .au-table-wrap {
        background:#fff; border:1px solid var(--line); border-radius:1.05rem; overflow:hidden;
        box-shadow:0 8px 24px rgba(15,23,42,.035);
    }
    .au-table { width:100%; border-collapse:collapse; }
    .au-table th {
        text-align:left; font-size:.68rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase;
        color:var(--muted); padding:.85rem 1rem; border-bottom:1px solid var(--line); background:#f8fafc;
    }
    .au-table td { padding:.9rem 1rem; border-bottom:1px solid #f1f5f9; vertical-align:middle; font-size:.88rem; }
    .au-table tr:last-child td { border-bottom:0; }
    .au-table tr:hover td { background:#fafbfc; }
    .au-user { display:flex; align-items:center; gap:.7rem; min-width:0; }
    .au-avatar {
        width:2.25rem; height:2.25rem; border-radius:999px; display:inline-flex; align-items:center; justify-content:center;
        font-weight:800; font-size:.85rem; color:#fff; background:linear-gradient(145deg,#2f7de1,#1f5fb8); flex-shrink:0;
    }
    .au-name { margin:0; font-weight:750; color:var(--ink); }
    .au-email { margin:.15rem 0 0; font-size:.76rem; color:var(--muted); }
    .au-badge {
        display:inline-flex; align-items:center; font-size:.66rem; font-weight:700; letter-spacing:.03em;
        text-transform:uppercase; padding:.22rem .5rem; border-radius:999px;
    }
    .au-badge--admin { background:#ecfdf5; color:#047857; }
    .au-badge--reviewer { background:#eff6ff; color:#1d4ed8; }
    .au-badge--member { background:#f1f5f9; color:#475569; }
    .au-badge--editor { background:#fff7ed; color:#c2410c; }
    .au-journals { display:flex; flex-wrap:wrap; gap:.3rem; max-width:16rem; }
    .au-journals span {
        font-size:.7rem; font-weight:600; color:#334155; background:#f1f5f9;
        padding:.18rem .4rem; border-radius:.4rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:9rem;
    }
    .au-muted { color:var(--muted); font-size:.8rem; }
    .au-empty { padding:2.5rem 1.25rem; text-align:center; }
    .au-empty__title { margin:0; font-size:1rem; font-weight:800; color:var(--ink); }
    .au-empty__text { margin:.35rem 0 0; font-size:.86rem; color:var(--muted); }
    .au-pager { padding: .85rem 1rem; border-top:1px solid var(--line); background:#fafbfc; }

    @media (max-width: 860px) {
        .au-hide-sm { display:none; }
    }
</style>

<div class="au-stats">
    <a href="{{ $indexUrl }}" class="au-stat @if(! $role && ! $journalFilter && ! $journalId) is-active @endif">
        <p class="au-stat__label">Total</p>
        <p class="au-stat__value">{{ number_format($stats['total']) }}</p>
    </a>
    <a href="{{ route('admin.users.index', ['role' => 'admin']) }}" class="au-stat @if($role === 'admin') is-active @endif">
        <p class="au-stat__label">Admins</p>
        <p class="au-stat__value">{{ number_format($stats['admin']) }}</p>
    </a>
    <a href="{{ route('admin.users.index', ['role' => 'reviewer']) }}" class="au-stat @if($role === 'reviewer') is-active @endif">
        <p class="au-stat__label">Reviewers</p>
        <p class="au-stat__value">{{ number_format($stats['reviewer']) }}</p>
    </a>
    <a href="{{ route('admin.users.index', ['role' => 'member']) }}" class="au-stat @if($role === 'member') is-active @endif">
        <p class="au-stat__label">Members</p>
        <p class="au-stat__value">{{ number_format($stats['member']) }}</p>
    </a>
    <a href="{{ route('admin.users.index', ['journal' => 'assigned']) }}" class="au-stat @if($journalFilter === 'assigned') is-active @endif">
        <p class="au-stat__label">Journal team</p>
        <p class="au-stat__value">{{ number_format($stats['with_journals']) }}</p>
    </a>
</div>

<form method="GET" action="{{ $indexUrl }}" class="au-toolbar" x-data="{ q: @js(request('q', '')) }">
    <div class="au-search">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M20 20l-3.5-3.5"/></svg>
        <input type="search" name="q" placeholder="Search name, email, affiliation…" value="{{ request('q') }}" x-model="q" autocomplete="off">
    </div>
    <div class="au-toolbar__filters">
        <x-tjs-select
            name="role"
            :value="(string) ($role ?? '')"
            placeholder="All roles"
            :options="[
                ['value' => '', 'label' => 'All roles'],
                ['value' => 'admin', 'label' => 'Platform admin'],
                ['value' => 'member', 'label' => 'Member'],
                ['value' => 'reviewer', 'label' => 'Reviewer'],
                ['value' => 'editor', 'label' => 'Editor (legacy)'],
            ]"
            submit-on-change
        />
        <x-tjs-select
            name="journal"
            :value="(string) ($journalFilter ?? '')"
            placeholder="Any team status"
            :options="[
                ['value' => '', 'label' => 'Any team status'],
                ['value' => 'assigned', 'label' => 'On a journal team'],
                ['value' => 'unassigned', 'label' => 'No journal team'],
            ]"
            submit-on-change
        />
        <x-journal-picker
            :journals="$journals"
            name="journal_id"
            :value="(string) ($journalId ?? '')"
            allow-empty
            empty-label="All journals"
            placeholder="Filter by journal…"
            submit-on-change
        />
        <x-tjs-select
            name="per_page"
            class="tjs-select--compact"
            :value="(string) $perPage"
            :options="collect([12, 24, 48])->map(fn ($n) => ['value' => (string) $n, 'label' => $n.' / page'])->all()"
            submit-on-change
        />
        <button type="submit" class="admin-btn admin-btn-secondary">Search</button>
    </div>
</form>

<div class="au-table-wrap">
    @if($users->isEmpty())
        <div class="au-empty">
            <p class="au-empty__title">No users found</p>
            <p class="au-empty__text">Try a different search or clear the filters.</p>
        </div>
    @else
        <div style="overflow-x:auto">
            <table class="au-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th class="au-hide-sm">Journal teams</th>
                        <th class="au-hide-sm">Activity</th>
                        <th>Joined</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        @php
                            $badge = match ($user->role) {
                                'admin' => 'au-badge--admin',
                                'reviewer' => 'au-badge--reviewer',
                                'editor' => 'au-badge--editor',
                                default => 'au-badge--member',
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="au-user">
                                    <span class="au-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                    <div class="min-w-0">
                                        <p class="au-name truncate">{{ $user->name }}</p>
                                        <p class="au-email truncate">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td><span class="au-badge {{ $badge }}">{{ $user->role }}</span></td>
                            <td class="au-hide-sm">
                                @if($user->journals->isEmpty())
                                    <span class="au-muted">—</span>
                                @else
                                    <div class="au-journals">
                                        @foreach($user->journals->take(3) as $journal)
                                            <span title="{{ $journal->title }} ({{ $journal->pivot->role }})">{{ $journal->title }}</span>
                                        @endforeach
                                        @if($user->journals->count() > 3)
                                            <span>+{{ $user->journals->count() - 3 }}</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="au-hide-sm">
                                <span class="au-muted">
                                    {{ $user->submissions_count }} sub · {{ $user->memberships_count }} mem
                                </span>
                            </td>
                            <td><span class="au-muted">{{ optional($user->created_at)->format('M j, Y') }}</span></td>
                            <td style="text-align:right">
                                <a href="{{ route('admin.users.show', $user) }}" class="admin-btn admin-btn-secondary" style="padding:.4rem .7rem;font-size:.75rem">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="au-pager">
                {{ $users->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
