@extends('layouts.member')

@section('title', 'My payments | '.config('tjs.name'))
@section('page_title', 'My payments')
@section('page_subtitle', 'Receipts for purchases and fees you paid')

@section('content')
<style>
    .mp-pay {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(15,23,42,.035);
    }
    .mp-pay__head {
        padding: .95rem 1.1rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .mp-pay__title { margin: 0; font-size: .92rem; font-weight: 800; }
    .mp-pay__meta { margin: .25rem 0 0; font-size: .78rem; color: var(--muted); line-height: 1.45; }
    .mp-pay table { width: 100%; border-collapse: collapse; min-width: 640px; }
    .mp-pay th {
        text-align: left;
        padding: .7rem 1rem;
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--muted);
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    .mp-pay td {
        padding: .85rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: top;
        font-size: .84rem;
    }
    .mp-pay tr:last-child td { border-bottom: 0; }
    .mp-pay__ref {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: .78rem;
        word-break: break-all;
    }
    .mp-pay__sub { margin-top: .2rem; font-size: .74rem; color: var(--muted); }
    .mp-pay__empty { padding: 2.4rem 1.2rem; text-align: center; color: var(--muted); }
    .mp-pay__pill {
        display: inline-flex;
        padding: .18rem .5rem;
        border-radius: 999px;
        font-size: .68rem;
        font-weight: 750;
    }
    .mp-pay__pill--success { background: #dcfce7; color: #166534; }
    .mp-pay__pill--pending { background: #fef3c7; color: #92400e; }
    .mp-pay__pill--failed { background: #fee2e2; color: #991b1b; }
    .mp-pay__pager { padding: .85rem 1.1rem; border-top: 1px solid #f1f5f9; }
</style>

<section class="mp-pay">
    <div class="mp-pay__head">
        <h2 class="mp-pay__title">Payment history</h2>
        <p class="mp-pay__meta">
            Successful payments include a downloadable PDF receipt. The same receipt is emailed to you after checkout.
        </p>
    </div>

    @if($transactions->isEmpty())
        <div class="mp-pay__empty">
            <p style="margin:0;font-weight:800;color:var(--ink)">No payments yet</p>
            <p style="margin:.4rem 0 0;font-size:.82rem;line-height:1.5">Article purchases, memberships, and journal activation fees will appear here.</p>
        </div>
    @else
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Detail</th>
                        <th>Reference</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $tx)
                        <tr>
                            <td>
                                <div>{{ optional($tx['created_at'])?->timezone(config('app.timezone'))->format('M j, Y') }}</div>
                                <div class="mp-pay__sub">{{ optional($tx['created_at'])?->timezone(config('app.timezone'))->format('g:i A') }}</div>
                            </td>
                            <td>{{ $tx['type_label'] }}</td>
                            <td>
                                <div style="font-weight:650">{{ $tx['detail'] }}</div>
                                @if($tx['journal_title'])
                                    <div class="mp-pay__sub">{{ $tx['journal_title'] }}</div>
                                @endif
                            </td>
                            <td><span class="mp-pay__ref">{{ $tx['reference'] }}</span></td>
                            <td>
                                <span class="mp-pay__pill mp-pay__pill--{{ $tx['status'] }}">{{ ucfirst($tx['status']) }}</span>
                            </td>
                            <td style="font-weight:800;white-space:nowrap">{{ number_format($tx['amount']) }} {{ $tx['currency'] }}</td>
                            <td>
                                @if($tx['can_download'])
                                    <a href="{{ route('payments.receipt', $tx['id']) }}" class="admin-btn admin-btn-secondary" style="padding:.35rem .6rem;font-size:.75rem">Download PDF</a>
                                @else
                                    <span class="mp-pay__sub">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
            <div class="mp-pay__pager">{{ $transactions->links() }}</div>
        @endif
    @endif
</section>
@endsection
