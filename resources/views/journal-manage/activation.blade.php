@extends('layouts.journal-manage')

@section('title', 'Activation | '.$journal->title)
@section('page_title', 'Journal activation')
@section('page_subtitle', 'Listing fee and management unlock')

@section('content')
@php
    $amountLabel = number_format($price).' '.$currency;
    $status = $journal->activation_status;
    $feeEnabled = \App\Support\JournalActivation::enabled();
@endphp

<section class="admin-panel" style="width:100%;max-width:42rem">
    <div class="admin-panel__head">
        <div>
            <h2 style="margin:0;font-size:1rem;font-weight:800">
                @if($unlocked)
                    Activation current
                @elseif(! $feeEnabled)
                    Activation locked
                @elseif($status === \App\Support\JournalActivation::STATUS_EXPIRED)
                    Activation expired
                @else
                    Activation fee required
                @endif
            </h2>
            <p style="margin:.35rem 0 0;font-size:.82rem;color:var(--muted);line-height:1.45">
                @if($feeEnabled)
                    Journals are not listed publicly until this fee is paid. When it expires, the journal is unlisted and major management tools lock again.
                @else
                    Platform activation fee collection is currently off. New journals are waived automatically; existing unpaid or expired journals stay locked until a platform admin re-enables payments or waives them.
                @endif
            </p>
        </div>
    </div>
    <div class="admin-panel__body" style="display:grid;gap:1.1rem">
        <div style="display:grid;gap:.65rem;padding:1rem;border:1px solid var(--line);border-radius:.9rem;background:#f8fafc">
            <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap">
                <div>
                    <p style="margin:0;font-size:.72rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--muted)">Fee</p>
                    <p style="margin:.25rem 0 0;font-size:1.35rem;font-weight:800">{{ number_format($price) }} {{ $currency }}</p>
                </div>
                <div>
                    <p style="margin:0;font-size:.72rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--muted)">Period</p>
                    <p style="margin:.25rem 0 0;font-size:1.05rem;font-weight:700">{{ $days }} days</p>
                </div>
                <div>
                    <p style="margin:0;font-size:.72rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--muted)">Status</p>
                    <p style="margin:.25rem 0 0;font-size:1.05rem;font-weight:700;text-transform:capitalize;{{ $unlocked ? 'color:#15803d' : '' }}">{{ $status }}</p>
                </div>
            </div>
            @if($journal->activation_expires_at)
                <p style="margin:0;font-size:.84rem;color:var(--muted)">
                    {{ $unlocked ? 'Renews / expires' : 'Expired' }}:
                    <strong style="color:var(--ink)">{{ $journal->activation_expires_at->timezone(config('app.timezone'))->toDayDateTimeString() }}</strong>
                </p>
            @elseif($unlocked)
                <p style="margin:0;font-size:.84rem;color:var(--muted)">No expiry (platform-waived listing).</p>
            @endif
        </div>

        <ul style="margin:0;padding-left:1.1rem;font-size:.84rem;color:var(--muted);line-height:1.55;display:grid;gap:.35rem">
            <li>Public catalog, sitemap, and author submissions require an active activation.</li>
            <li>You will get email reminders at 90, 60, 30, 7, and 1 day before expiry, then when it expires.</li>
            <li>After expiry, the journal stays intact but catalog/volumes/submissions tools stay locked until renewal.</li>
        </ul>

        <div style="display:flex;flex-wrap:wrap;gap:.65rem;align-items:center">
            @if($feeEnabled)
                @if($unlocked)
                    <button
                        type="button"
                        class="admin-btn"
                        disabled
                        aria-disabled="true"
                        title="Activation is already active. Renew after it expires."
                        style="background:#e2e8f0;color:#94a3b8;border:1px solid #cbd5e1;cursor:not-allowed;box-shadow:none"
                    >
                        Renew activation ({{ $amountLabel }})
                    </button>
                @else
                    <form method="POST" action="{{ route('journal.manage.activation.pay', $journal) }}">
                        @csrf
                        <button type="submit" class="admin-btn admin-btn-primary">
                            Pay activation fee ({{ $amountLabel }})
                        </button>
                    </form>
                    <form method="POST" action="{{ route('journal.manage.activation.skip', $journal) }}">
                        @csrf
                        <button type="submit" class="admin-btn admin-btn-secondary">Skip for now</button>
                    </form>
                @endif
            @endif
            <a href="{{ route('journal.manage.billing.index', $journal) }}" class="admin-btn admin-btn-secondary">Payment audit</a>
            <a href="{{ route('journal.manage.settings.edit', $journal) }}" class="admin-btn admin-btn-ghost">Settings</a>
        </div>
        @if($feeEnabled && $unlocked)
            <p style="margin:0;font-size:.78rem;color:var(--muted);line-height:1.45">
                Renewal will unlock when this activation expires
                @if($journal->activation_expires_at)
                    ({{ $journal->activation_expires_at->timezone(config('app.timezone'))->toDayDateTimeString() }}).
                @else
                    .
                @endif
            </p>
        @endif
    </div>
</section>
@endsection
