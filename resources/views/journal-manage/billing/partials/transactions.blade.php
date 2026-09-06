@php
    $hasFilters = collect($filters)->filter(fn ($v) => filled($v))->isNotEmpty();
@endphp

@if($transactions->isEmpty())
    <div class="jb-empty">
        <p class="jb-empty__title">{{ $hasFilters ? 'No matching transactions' : 'No payments yet' }}</p>
        <p class="jb-empty__text">
            @if($hasFilters)
                Try clearing filters, or broaden the date range.
            @else
                When someone buys an article or membership, or you pay the activation fee, it will show up here.
            @endif
        </p>
    </div>
@else
    <div class="jb-table-wrap">
        <table class="jb-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Detail</th>
                    <th>Payer</th>
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
                            <div class="jb-sub">{{ optional($tx['created_at'])?->timezone(config('app.timezone'))->format('g:i A') }}</div>
                        </td>
                        <td>
                            <span class="jb-pill jb-pill--type">{{ $tx['type_label'] }}</span>
                            <div style="margin-top:.35rem">
                                <span class="jb-pill {{ $tx['direction'] === 'in' ? 'jb-pill--in' : 'jb-pill--out' }}">
                                    {{ $tx['direction'] === 'in' ? 'Income' : 'Expense' }}
                                </span>
                            </div>
                        </td>
                        <td>
                            <div class="jb-detail">{{ $tx['detail'] }}</div>
                        </td>
                        <td>
                            <div>{{ $tx['payer_name'] ?: '—' }}</div>
                            @if($tx['payer_email'])
                                <div class="jb-sub">{{ $tx['payer_email'] }}</div>
                            @endif
                        </td>
                        <td><span class="jb-ref">{{ $tx['reference'] }}</span></td>
                        <td>
                            <span class="jb-pill jb-pill--{{ $tx['status'] }}">{{ ucfirst($tx['status']) }}</span>
                            @if($tx['paid_at'])
                                <div class="jb-sub">Paid {{ $tx['paid_at']->timezone(config('app.timezone'))->format('M j, Y') }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="jb-amount {{ $tx['direction'] === 'in' ? 'jb-amount--in' : 'jb-amount--out' }}">
                                {{ $tx['direction'] === 'in' ? '+' : '−' }}{{ number_format($tx['amount']) }} {{ $tx['currency'] }}
                            </span>
                        </td>
                        <td>
                            @if($tx['status'] === 'success')
                                <a href="{{ route('payments.receipt', $tx['id']) }}" class="admin-btn admin-btn-ghost" style="padding:.35rem .55rem;font-size:.72rem">PDF</a>
                            @else
                                <span class="jb-sub">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="jb-pager" @if($transactions->hasPages()) data-billing-pager @endif>
        <p class="jb-sub" style="margin:0 0 .55rem">
            Showing {{ $transactions->firstItem() }}–{{ $transactions->lastItem() }}
            of {{ number_format($transactions->total()) }}
            · {{ $transactions->perPage() }} per page
        </p>
        @if($transactions->hasPages())
            {{ $transactions->onEachSide(1)->links() }}
        @endif
    </div>
@endif
