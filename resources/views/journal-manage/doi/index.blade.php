@extends('layouts.journal-manage')

@section('title', 'DOI | '.$journal->title)
@section('page_title', 'DOI deposits')
@section('page_subtitle', 'Platform credits or your own Crossref account')

@section('content')
@php
    $mode = old('doi_mode', $journal->doi_mode ?: 'unset');
@endphp
<style>
    .doi-banner { margin-bottom:1rem; padding:.95rem 1.1rem; border-radius:.95rem; }
    .doi-banner--exhausted { border:1px solid #fecaca; background:#fef2f2; color:#991b1b; }
    .doi-banner--low { border:1px solid #fde68a; background:#fffbeb; color:#92400e; }
    .doi-card { background:#fff; border:1px solid var(--line); border-radius:1rem; padding:1.1rem 1.2rem; margin-bottom:1rem; box-shadow:0 8px 24px rgba(15,23,42,.035); }
    .doi-stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.65rem; margin-bottom:1rem; }
    @media (max-width:720px){ .doi-stats { grid-template-columns:1fr; } }
    .doi-stat { border:1px solid var(--line); border-radius:.85rem; padding:.85rem 1rem; background:#f8fafc; }
    .doi-stat__label { font-size:.68rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--muted); }
    .doi-stat__value { margin-top:.3rem; font-size:1.25rem; font-weight:800; }
    .doi-option { border:1px solid #e2e8f0; border-radius:.85rem; padding:.85rem 1rem; display:grid; gap:.3rem; }
    .doi-option:has(input:checked) { border-color:#93c5fd; background:#f8fbff; }
    .doi-field label { display:block; margin-bottom:.35rem; font-size:.78rem; font-weight:700; }
    .doi-input { width:100%; border:1px solid #e2e8f0; border-radius:.7rem; padding:.55rem .7rem; font:inherit; font-size:.84rem; }
    .doi-hint { margin:.3rem 0 0; font-size:.74rem; color:var(--muted); line-height:1.45; }
    table { width:100%; border-collapse:collapse; }
    th, td { text-align:left; padding:.75rem 1rem; border-bottom:1px solid #f1f5f9; font-size:.84rem; vertical-align:top; }
    th { font-size:.68rem; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); background:#f8fafc; }
    .doi-pill { display:inline-flex; padding:.15rem .45rem; border-radius:999px; font-size:.68rem; font-weight:750; }
    .doi-pill--success { background:#dcfce7; color:#166534; }
    .doi-pill--failed { background:#fee2e2; color:#991b1b; }
    .doi-pill--pending { background:#fef3c7; color:#92400e; }
</style>

@unless($enabled)
    <div class="doi-banner doi-banner--exhausted">
        <strong>DOI deposits are disabled</strong> by the platform administrator.
    </div>
@endunless

@if($banner)
    <div class="doi-banner doi-banner--{{ $banner['level'] === 'exhausted' ? 'exhausted' : 'low' }}">
        <strong>{{ $banner['title'] }}</strong>
        <div style="margin-top:.35rem;font-size:.84rem;line-height:1.45">{{ $banner['message'] }}</div>
    </div>
@endif

<div class="doi-stats">
    <div class="doi-stat">
        <p class="doi-stat__label">Platform credits</p>
        <p class="doi-stat__value">{{ number_format($journal->doi_credits_balance) }}</p>
    </div>
    <div class="doi-stat">
        <p class="doi-stat__label">Lifetime topped up</p>
        <p class="doi-stat__value">{{ number_format($journal->doi_credits_lifetime) }}</p>
    </div>
    <div class="doi-stat">
        <p class="doi-stat__label">Indicative credit price</p>
        <p class="doi-stat__value" style="font-size:1rem">{{ number_format($priceNgn) }} NGN <span style="font-size:.72rem;color:var(--muted);font-weight:600">({{ number_format($rate) }} NGN/USD)</span></p>
    </div>
</div>

<form method="POST" action="{{ route('journal.manage.doi.settings', $journal) }}" class="doi-card" x-data="{ mode: @js($mode) }">
    @csrf
    @method('PUT')
    <h2 style="margin:0 0 .35rem;font-size:.95rem;font-weight:800">How should this journal deposit DOIs?</h2>
    <p class="doi-hint" style="margin-bottom:.9rem">Use the platform Crossref pool (ours) or your own Crossref account (yours). You can publish first and deposit later from the article page.</p>

    <div style="display:grid;gap:.55rem;margin-bottom:1rem">
        <label class="doi-option">
            <div style="font-weight:750"><input type="radio" name="doi_mode" value="platform" x-model="mode" @disabled(! ($canMutate ?? true))> Platform pool (ours)</div>
            <p class="doi-hint">Spend 1 credit per successful deposit under prefix <code>{{ $platformPrefix }}</code>.</p>
        </label>
        <label class="doi-option">
            <div style="font-weight:750"><input type="radio" name="doi_mode" value="own" x-model="mode" @disabled(! ($canMutate ?? true))> Own Crossref (yours)</div>
            <p class="doi-hint">Deposit with your Crossref login and prefix. No platform credits consumed.</p>
        </label>
        <label class="doi-option">
            <div style="font-weight:750"><input type="radio" name="doi_mode" value="unset" x-model="mode" @disabled(! ($canMutate ?? true))> Not configured</div>
            <p class="doi-hint">Deposits stay disabled until you choose a mode.</p>
        </label>
    </div>

    <div x-show="mode === 'own'" x-cloak style="display:grid;gap:.75rem;margin-bottom:1rem">
        <div class="doi-field">
            <label for="doi_prefix">Your DOI prefix</label>
            <input id="doi_prefix" class="doi-input" name="doi_prefix" value="{{ old('doi_prefix', $journal->doi_prefix) }}" placeholder="10.xxxx/yourjournal" @disabled(! ($canMutate ?? true))>
            @error('doi_prefix')<p style="color:#b91c1c;font-size:.75rem">{{ $message }}</p>@enderror
        </div>
        <div class="doi-field">
            <label for="crossref_username">Crossref username</label>
            <input id="crossref_username" class="doi-input" name="crossref_username" @disabled(! ($canMutate ?? true))
                value="{{ old('crossref_username', $maskedCrossrefUsername ?? '') }}">
            @error('crossref_username')<p style="color:#b91c1c;font-size:.75rem">{{ $message }}</p>@enderror
        </div>
        <div class="doi-field">
            <label for="crossref_password">Crossref password / role password</label>
            <input id="crossref_password" type="password" class="doi-input" name="crossref_password" @disabled(! ($canMutate ?? true))
                value="{{ old('crossref_password', ($hasCrossrefCredentials ?? false) ? '••••••••' : '') }}">
        </div>
        @if(($hasCrossrefCredentials ?? false) && ($canMutate ?? true))
            <label style="font-size:.8rem;display:flex;gap:.4rem;align-items:center">
                <input type="checkbox" name="clear_crossref" value="1"> Clear stored Crossref credentials
            </label>
        @endif
    </div>

    <label style="display:flex;gap:.45rem;align-items:flex-start;font-size:.84rem;margin-bottom:1rem">
        <input type="checkbox" name="doi_auto_deposit" value="1" @checked(old('doi_auto_deposit', $journal->doi_auto_deposit)) @disabled(! ($canMutate ?? true))>
        <span>Automatically deposit when an article is published (mint DOI if missing).</span>
    </label>

    @if($canMutate ?? true)
        <button type="submit" class="admin-btn admin-btn-primary">Save DOI settings</button>
    @endif
</form>

<section class="doi-card" style="padding:0;overflow:hidden">
    <div style="padding:1rem 1.1rem;border-bottom:1px solid #f1f5f9">
        <h2 style="margin:0;font-size:.95rem;font-weight:800">Deposit history</h2>
        <p class="doi-hint">Successful platform deposits spend 1 credit. Failed attempts refund credits.</p>
    </div>
    <div style="overflow-x:auto">
        <table>
            <thead>
                <tr>
                    <th>When</th>
                    <th>Article</th>
                    <th>DOI</th>
                    <th>Mode</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deposits as $deposit)
                    <tr>
                        <td>{{ optional($deposit->created_at)?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</td>
                        <td>{{ $deposit->article?->title ?: '—' }}</td>
                        <td style="font-family:ui-monospace,monospace;font-size:.78rem">{{ $deposit->doi }}</td>
                        <td style="text-transform:capitalize">{{ $deposit->mode }}</td>
                        <td>
                            <span class="doi-pill doi-pill--{{ $deposit->status }}">{{ ucfirst($deposit->status) }}</span>
                            @if($deposit->error_message)
                                <div class="doi-hint">{{ $deposit->error_message }}</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="padding:1.5rem;color:var(--muted)">No deposits yet. Open an article and use Deposit DOI.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($deposits->hasPages())
        <div style="padding:.85rem 1rem">{{ $deposits->links() }}</div>
    @endif
</section>
@endsection
