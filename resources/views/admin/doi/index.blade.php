@extends('layouts.admin')

@section('title', 'DOI credits | Admin')
@section('page_title', 'DOI credits')
@section('page_subtitle', 'Top up journal Crossref deposit credits after funding the DOI platform')

@section('content')
<style>
    .doi-grid { display:grid; gap:1rem; grid-template-columns: 1.4fr .9fr; }
    @media (max-width:960px){ .doi-grid { grid-template-columns:1fr; } }
    .doi-card { background:#fff; border:1px solid var(--line); border-radius:1rem; overflow:hidden; box-shadow:0 8px 24px rgba(15,23,42,.035); }
    .doi-card__head { padding:1rem 1.1rem; border-bottom:1px solid #f1f5f9; }
    .doi-card__title { margin:0; font-size:.95rem; font-weight:800; }
    .doi-card__meta { margin:.25rem 0 0; font-size:.78rem; color:var(--muted); }
    .doi-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.65rem; margin-bottom:1rem; }
    @media (max-width:720px){ .doi-stats { grid-template-columns:1fr 1fr; } }
    .doi-stat { background:#fff; border:1px solid var(--line); border-radius:.9rem; padding:.85rem 1rem; }
    .doi-stat__label { font-size:.68rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--muted); }
    .doi-stat__value { margin-top:.3rem; font-size:1.2rem; font-weight:800; }
    table { width:100%; border-collapse:collapse; }
    th, td { text-align:left; padding:.75rem 1rem; border-bottom:1px solid #f1f5f9; font-size:.84rem; vertical-align:top; }
    th { font-size:.68rem; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); background:#f8fafc; }
    .doi-input { width:100%; border:1px solid #e2e8f0; border-radius:.65rem; padding:.45rem .6rem; font:inherit; font-size:.82rem; }
    .doi-form { display:grid; gap:.45rem; min-width:12rem; }
</style>

<div class="doi-stats">
    <div class="doi-stat">
        <p class="doi-stat__label">FX rate</p>
        <p class="doi-stat__value">1 USD = {{ number_format($rate) }} NGN</p>
    </div>
    <div class="doi-stat">
        <p class="doi-stat__label">Credit price</p>
        <p class="doi-stat__value">{{ number_format($priceUsd, 2) }} USD / {{ number_format($priceNgn) }} NGN</p>
    </div>
    <div class="doi-stat">
        <p class="doi-stat__label">Low threshold</p>
        <p class="doi-stat__value">≤ {{ $thresholdAbsolute }} or {{ $thresholdPercent }}%</p>
    </div>
    <div class="doi-stat">
        <p class="doi-stat__label">Hint</p>
        <p class="doi-stat__value" style="font-size:.82rem;font-weight:650;line-height:1.35">Record top-ups here after you fund Crossref.</p>
    </div>
</div>

<div class="doi-grid">
    <section class="doi-card">
        <div class="doi-card__head">
            <h2 class="doi-card__title">Journals</h2>
            <p class="doi-card__meta">Add credits after topping up on the DOI/Crossref platform.</p>
        </div>
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th>Journal</th>
                        <th>Mode</th>
                        <th>Balance</th>
                        <th>Lifetime</th>
                        <th>Top up</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($journals as $journal)
                        <tr>
                            <td>
                                <div style="font-weight:700">{{ $journal->title }}</div>
                                <div style="font-size:.74rem;color:var(--muted)">/j/{{ $journal->slug }}</div>
                            </td>
                            <td style="text-transform:capitalize">{{ $journal->doi_mode ?: 'unset' }}</td>
                            <td style="font-weight:800">{{ number_format($journal->doi_credits_balance) }}</td>
                            <td>{{ number_format($journal->doi_credits_lifetime) }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.doi.topup', $journal) }}" class="doi-form">
                                    @csrf
                                    <input class="doi-input" type="number" name="credits" min="1" required placeholder="Credits">
                                    <input class="doi-input" type="number" step="0.01" name="amount_usd" placeholder="USD (optional)">
                                    <input class="doi-input" type="text" name="note" placeholder="Note (optional)">
                                    <button type="submit" class="admin-btn admin-btn-primary" style="padding:.4rem .65rem;font-size:.75rem">Add credits</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="padding:1.5rem;color:var(--muted)">No journals yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($journals->hasPages())
            <div style="padding:.85rem 1rem">{{ $journals->links() }}</div>
        @endif
    </section>

    <section class="doi-card">
        <div class="doi-card__head">
            <h2 class="doi-card__title">Recent top-ups</h2>
            <p class="doi-card__meta">Audit of credit additions.</p>
        </div>
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Journal</th>
                        <th>Credits</th>
                        <th>By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentTopups as $topup)
                        <tr>
                            <td>{{ $topup->created_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</td>
                            <td>{{ $topup->journal?->title }}</td>
                            <td style="font-weight:800">+{{ number_format($topup->credits) }}</td>
                            <td>{{ $topup->creator?->name ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="padding:1.5rem;color:var(--muted)">No top-ups yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
