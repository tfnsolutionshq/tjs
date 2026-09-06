@extends('layouts.journal-manage')

@section('title', 'Payment gateway | '.$journal->title)
@section('page_title', 'Payment gateway')
@section('page_subtitle', 'Where article and membership payments settle')

@section('page_actions')
    <a href="{{ route('journal.manage.billing.index', $journal) }}" class="admin-btn admin-btn-ghost">Payments &amp; income</a>
@endsection

@section('content')
@php
    $pref = old('payment_gateway_preference', $journal->payment_gateway_preference ?: 'unset');
    $modeLabels = [
        'personal' => 'Personal Paystack',
        'split' => 'Platform split',
        'platform' => 'Platform',
        'unavailable' => 'Not ready',
    ];
    $supportEmail = config('tjs.support.email');
    $supportPhone = config('tjs.support.phone');
    $supportPhoneDial = $supportPhone ? preg_replace('/[^\d+]/', '', $supportPhone) : null;
    $splitRequestSubject = 'Split code request — '.$journal->title;
    $splitRequestBody = implode("\n", [
        'Hello,',
        '',
        'I would like to request a Paystack split code for my journal.',
        '',
        'Journal: '.$journal->title,
        'Slug: '.$journal->slug,
        'Contact name: '.auth()->user()->name,
        'Contact email: '.auth()->user()->email,
        '',
        'Paystack account email (if already set up):',
        'Preferred revenue share (optional):',
        '',
        'Thank you.',
    ]);
    $splitRequestMailto = 'mailto:'.$supportEmail.'?subject='.rawurlencode($splitRequestSubject).'&body='.rawurlencode($splitRequestBody);
@endphp

<style>
    .jg-card { background:#fff; border:1px solid var(--line); border-radius:1rem; padding:1.1rem 1.2rem; box-shadow:0 8px 24px rgba(15,23,42,.035); margin-bottom:1rem; }
    .jg-status { display:flex; flex-wrap:wrap; gap:.75rem; align-items:center; justify-content:space-between; padding:.9rem 1.05rem; border-radius:.95rem; border:1px solid #dbeafe; background:linear-gradient(135deg,#f8fbff,#eff6ff); margin-bottom:1rem; }
    .jg-pill { display:inline-flex; padding:.2rem .55rem; border-radius:999px; font-size:.72rem; font-weight:750; background:#dbeafe; color:#1d4ed8; }
    .jg-pill--ok { background:#dcfce7; color:#166534; }
    .jg-pill--warn { background:#fef3c7; color:#92400e; }
    .jg-pill--bad { background:#fee2e2; color:#991b1b; }
    .jg-option { border:1px solid #e2e8f0; border-radius:.9rem; padding:.95rem 1rem; display:grid; gap:.35rem; cursor:pointer; }
    .jg-option:has(input:checked) { border-color:#93c5fd; background:#f8fbff; box-shadow:0 0 0 3px rgba(59,130,246,.12); }
    .jg-option input { margin-right:.45rem; }
    .jg-grid { display:grid; gap:.85rem; }
    @media (min-width:720px) { .jg-grid--2 { grid-template-columns:1fr 1fr; } }
    .jg-field label { display:block; margin-bottom:.35rem; font-size:.78rem; font-weight:700; color:#334155; }
    .jg-input { width:100%; border:1px solid #e2e8f0; border-radius:.7rem; padding:.55rem .7rem; font:inherit; font-size:.84rem; }
    .jg-hint { margin:.35rem 0 0; font-size:.74rem; color:var(--muted); line-height:1.45; }
    .jg-code { font-family:ui-monospace,monospace; font-size:.78rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:.5rem; padding:.35rem .5rem; word-break:break-all; }
    .jg-flow { display:grid; gap:1rem; }
    .jg-flow__steps { margin:0; padding:0; list-style:none; display:grid; gap:.75rem; counter-reset:jg-step; }
    .jg-flow__step {
        display:grid; grid-template-columns:auto 1fr; gap:.75rem; align-items:start;
        padding:.85rem .95rem; border:1px solid #e2e8f0; border-radius:.85rem; background:#f8fafc;
    }
    .jg-flow__n {
        width:1.65rem; height:1.65rem; border-radius:999px; display:inline-flex; align-items:center; justify-content:center;
        background:#dbeafe; color:#1d4ed8; font-size:.72rem; font-weight:800; flex-shrink:0;
    }
    .jg-flow__title { margin:0; font-size:.84rem; font-weight:800; color:var(--ink); }
    .jg-flow__text { margin:.25rem 0 0; font-size:.76rem; color:var(--muted); line-height:1.45; }
    .jg-flow__actions { display:flex; flex-wrap:wrap; gap:.55rem; margin-top:.15rem; }
    .jg-contact-btn {
        display:inline-flex; align-items:center; gap:.4rem; padding:.58rem .85rem; border-radius:.7rem;
        font:inherit; font-size:.8rem; font-weight:700; text-decoration:none; border:1px solid #e2e8f0; background:#fff; color:#0f172a;
    }
    .jg-contact-btn:hover { border-color:#cbd5e1; background:#f8fafc; }
    .jg-contact-btn--primary { background:#2563eb; border-color:#2563eb; color:#fff; }
    .jg-contact-btn--primary:hover { background:#1d4ed8; border-color:#1d4ed8; color:#fff; }
    .jg-contact-btn svg { width:.95rem; height:.95rem; flex-shrink:0; }
</style>

<div class="jg-status">
    <div>
        <p style="margin:0;font-weight:800;font-size:.9rem">Active checkout path</p>
        <p style="margin:.3rem 0 0;font-size:.8rem;color:#475569">
            Resolved mode:
            <span class="jg-pill {{ $resolvedMode === 'unavailable' ? 'jg-pill--bad' : 'jg-pill--ok' }}">
                {{ $modeLabels[$resolvedMode] ?? $resolvedMode }}
            </span>
            @unless($journal->personal_gateway_allowed)
                <span class="jg-pill jg-pill--warn">Personal gateway disabled by platform</span>
            @endunless
        </p>
    </div>
</div>

@unless($canMutate ?? true)
    <div class="jg-card" style="border-color:#fde68a;background:#fffbeb">
        <p style="margin:0;font-weight:700;color:#92400e">View only — you cannot change gateway settings right now.</p>
    </div>
@endunless

@if($personalKeysCorrupted ?? false)
    <div class="jg-card" style="border-color:#fecaca;background:#fef2f2;margin-bottom:1rem">
        <p style="margin:0;font-weight:700;color:#991b1b">Stored Paystack keys could not be read</p>
        <p style="margin:.4rem 0 0;font-size:.82rem;color:#7f1d1d;line-height:1.45">
            They were encrypted with a different application key (often after copying a database or changing <code>APP_KEY</code>).
            Re-enter your Paystack keys below, or tick “Clear stored personal keys” and save to remove the unreadable values.
        </p>
    </div>
@endif

<form method="POST" action="{{ route('journal.manage.payments.gateway.update', $journal) }}" class="jg-card" x-data="{ pref: @js($pref) }">
    @csrf
    @method('PUT')

    <h2 style="margin:0 0 .35rem;font-size:.95rem;font-weight:800">How should income payments run?</h2>
    <p class="jg-hint" style="margin-bottom:1rem">Activation / listing fees always use the platform Paystack account. This setting only affects article sales and journal memberships.</p>

    <div style="display:grid;gap:.65rem;margin-bottom:1.1rem">
        <label class="jg-option">
            <div style="font-weight:750">
                <input type="radio" name="payment_gateway_preference" value="personal" x-model="pref" @disabled(! ($canMutate ?? true))>
                Personal Paystack keys
            </div>
            <p class="jg-hint">Charges settle directly into this journal’s Paystack account. Set your Paystack webhook to the URL below.</p>
        </label>
        <label class="jg-option">
            <div style="font-weight:750">
                <input type="radio" name="payment_gateway_preference" value="split" x-model="pref" @disabled(! ($canMutate ?? true))>
                Platform-enabled split
            </div>
            <p class="jg-hint">Platform charges with its Paystack keys and applies a Paystack split (SPL_…) created for this journal. Shares and fees are defined on that split in Paystack. Request a split code from {{ config('tjs.name') }} — see the flow below.</p>
        </label>
        <label class="jg-option">
            <div style="font-weight:750">
                <input type="radio" name="payment_gateway_preference" value="unset" x-model="pref" @disabled(! ($canMutate ?? true))>
                Not configured yet
            </div>
            <p class="jg-hint">Income checkout stays blocked until personal keys or a split code are ready.</p>
        </label>
    </div>

    <div class="jg-grid jg-grid--2" x-show="pref === 'personal'" x-cloak>
        <div class="jg-field">
            <label for="paystack_public_key">Paystack public key</label>
            <input id="paystack_public_key" name="paystack_public_key" type="text" class="jg-input" @disabled(! ($canMutate ?? true))
                value="{{ old('paystack_public_key', $maskedPublicKey ?? '') }}"
                placeholder="pk_live_… or pk_test_…">
            <p class="jg-hint">Leave masked value unchanged to keep the current key.</p>
            @error('paystack_public_key')<p style="color:#b91c1c;font-size:.75rem;margin:.3rem 0 0">{{ $message }}</p>@enderror
        </div>
        <div class="jg-field">
            <label for="paystack_secret_key">Paystack secret key</label>
            <input id="paystack_secret_key" name="paystack_secret_key" type="password" class="jg-input" @disabled(! ($canMutate ?? true))
                value="{{ old('paystack_secret_key', $hasPersonal ? '••••••••••••' : '') }}"
                placeholder="sk_live_… or sk_test_…">
            @error('paystack_secret_key')<p style="color:#b91c1c;font-size:.75rem;margin:.3rem 0 0">{{ $message }}</p>@enderror
        </div>
        @if($hasPersonal && ($canMutate ?? true))
            <label style="grid-column:1/-1;font-size:.8rem;display:flex;gap:.4rem;align-items:center">
                <input type="checkbox" name="clear_personal_keys" value="1"> Clear stored personal keys
            </label>
        @endif
        <div style="grid-column:1/-1">
            <p class="jg-hint" style="margin-bottom:.35rem">Webhook URL for your Paystack dashboard:</p>
            <div class="jg-code">{{ $webhookUrl }}</div>
        </div>
    </div>

    <div class="jg-grid" x-show="pref === 'split'" x-cloak style="margin-top:.25rem">
        <div class="jg-field">
            <label for="paystack_split_code">Paystack split code</label>
            <input id="paystack_split_code" name="paystack_split_code" type="text" class="jg-input" @disabled(! ($canMutate ?? true))
                value="{{ old('paystack_split_code', $journal->paystack_split_code) }}"
                placeholder="SPL_xxxxxxxxxx">
            <p class="jg-hint">Paste the SPL_ code the platform team sends you after your request is processed.</p>
            @error('paystack_split_code')<p style="color:#b91c1c;font-size:.75rem;margin:.3rem 0 0">{{ $message }}</p>@enderror
        </div>
        @if($hasSplit && ($canMutate ?? true))
            <label style="font-size:.8rem;display:flex;gap:.4rem;align-items:center">
                <input type="checkbox" name="clear_split_code" value="1"> Clear split code
            </label>
        @endif
    </div>

    @if($canMutate ?? true)
        <div style="margin-top:1.1rem;display:flex;gap:.55rem;flex-wrap:wrap">
            <button type="submit" class="admin-btn admin-btn-primary">Save gateway settings</button>
        </div>
    @endif
</form>

<section class="jg-card jg-flow">
    <div>
        <h2 style="margin:0;font-size:.95rem;font-weight:800">Request a Paystack split code</h2>
        <p class="jg-hint" style="margin-top:.35rem">
            Split codes are created by the {{ config('tjs.name') }} team in Paystack — not inside your journal dashboard.
            Contact us by email or phone to start the setup.
        </p>
    </div>

    <ol class="jg-flow__steps">
        <li class="jg-flow__step">
            <span class="jg-flow__n">1</span>
            <div>
                <p class="jg-flow__title">Choose platform split</p>
                <p class="jg-flow__text">Select <strong>Platform-enabled split</strong> above (you can save the preference before the code arrives).</p>
            </div>
        </li>
        <li class="jg-flow__step">
            <span class="jg-flow__n">2</span>
            <div>
                <p class="jg-flow__title">Contact {{ config('tjs.name') }}</p>
                <p class="jg-flow__text">
                    Email or call the platform team with your journal name (<strong>{{ $journal->title }}</strong>),
                    slug (<code>{{ $journal->slug }}</code>), and the Paystack account email that should receive journal income.
                </p>
                <div class="jg-flow__actions">
                    <a href="{{ $splitRequestMailto }}" class="jg-contact-btn jg-contact-btn--primary">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                        Email {{ config('tjs.name') }}
                    </a>
                    @if($supportPhone && $supportPhoneDial)
                        <a href="tel:{{ $supportPhoneDial }}" class="jg-contact-btn">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                            Call {{ $supportPhone }}
                        </a>
                    @endif
                </div>
                <p class="jg-hint" style="margin-top:.55rem">
                    Reach us at <a href="mailto:{{ $supportEmail }}" style="font-weight:700;color:#1d4ed8">{{ $supportEmail }}</a>@if($supportPhone) or {{ $supportPhone }}@endif.
                </p>
            </div>
        </li>
        <li class="jg-flow__step">
            <span class="jg-flow__n">3</span>
            <div>
                <p class="jg-flow__title">Platform creates the split in Paystack</p>
                <p class="jg-flow__text">We configure a Paystack split with your journal as a recipient and define revenue shares on the split itself.</p>
            </div>
        </li>
        <li class="jg-flow__step">
            <span class="jg-flow__n">4</span>
            <div>
                <p class="jg-flow__title">Receive your SPL_ code</p>
                <p class="jg-flow__text">The team replies with a split code (format <code>SPL_xxxxxxxxxx</code>) by email or your preferred channel.</p>
            </div>
        </li>
        <li class="jg-flow__step">
            <span class="jg-flow__n">5</span>
            <div>
                <p class="jg-flow__title">Paste and save</p>
                <p class="jg-flow__text">Enter the code in the field above, save gateway settings, and article or membership checkout will route through the platform split.</p>
            </div>
        </li>
    </ol>

    @unless($hasSplit ?? false)
        <div style="padding:.85rem .95rem;border-radius:.85rem;border:1px solid #fde68a;background:#fffbeb">
            <p style="margin:0;font-size:.82rem;font-weight:700;color:#92400e">No split code on file yet</p>
            <p style="margin:.35rem 0 0;font-size:.78rem;color:#a16207;line-height:1.45">
                Income checkout stays blocked for platform split until an SPL_ code is saved. Use the email or phone option above to request one.
            </p>
        </div>
    @endunless
</section>
@endsection
