@extends('layouts.admin')

@section('title', 'Settings | Admin')
@section('page_title', 'Settings')
@section('page_subtitle', 'Platform defaults and catalog taxonomy')

@section('content')
@php
    $tab = $tab ?? 'general';
@endphp

<style>
    .as-tabs {
        display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: 1rem;
        background: #fff; border: 1px solid var(--line); border-radius: 999px; padding: .35rem;
        width: fit-content; max-width: 100%;
        box-shadow: 0 8px 24px rgba(15,23,42,.035);
    }
    .as-tab {
        display: inline-flex; align-items: center; gap: .4rem;
        border: 0; background: transparent; border-radius: 999px;
        padding: .55rem .95rem; font: inherit; font-size: .82rem; font-weight: 750;
        color: var(--muted); text-decoration: none; cursor: pointer;
    }
    .as-tab:hover { color: var(--ink); }
    .as-tab.is-active {
        background: #eff6ff; color: #1d4ed8;
        box-shadow: 0 0 0 1px #bfdbfe;
    }
    .as-card {
        background: #fff; border: 1px solid var(--line); border-radius: 1.05rem;
        box-shadow: 0 8px 24px rgba(15,23,42,.035); overflow: hidden;
    }
    .as-card__head { padding: 1rem 1.15rem .15rem; }
    .as-card__title { margin: 0; font-size: .95rem; font-weight: 800; color: var(--ink); }
    .as-card__desc { margin: .3rem 0 0; font-size: .8rem; color: var(--muted); line-height: 1.45; }
    .as-card__body { padding: 1rem 1.15rem 1.2rem; }
    .as-grid { display: grid; gap: .9rem; }
    @media (min-width: 720px) {
        .as-grid--2 { grid-template-columns: 1fr 1fr; }
    }
    .as-field label {
        display: block; margin-bottom: .4rem;
        font-size: .78rem; font-weight: 700; color: #334155;
    }
    .as-req { color: #dc2626; font-weight: 800; margin-left: .15rem; }
    .as-hint { margin: .4rem 0 0; font-size: .72rem; color: var(--muted); }
    .as-error { margin: .4rem 0 0; font-size: .75rem; font-weight: 600; color: #b91c1c; }
    .as-input, .as-select {
        width: 100%; border: 1px solid #e2e8f0; background: #fff; border-radius: .7rem;
        padding: .7rem .85rem; font: inherit; font-size: .9rem; color: var(--ink); outline: none;
    }
    .as-input:focus, .as-select:focus {
        border-color: #93c5fd; box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    }
    .as-flash {
        margin-bottom: 1rem; border-radius: .85rem; padding: .75rem .9rem;
        border: 1px solid #bbf7d0; background: #f0fdf4; color: #166534;
        font-size: .84rem; font-weight: 650;
    }
    .as-flash--err {
        border-color: #fecaca; background: #fef2f2; color: #991b1b;
    }
    .as-flash--err ul { margin: .35rem 0 0; padding-left: 1.1rem; }
    .as-row {
        display: grid; gap: .75rem; align-items: start;
        padding: .85rem 0; border-bottom: 1px solid #f1f5f9;
    }
    .as-row:last-child { border-bottom: 0; padding-bottom: 0; }
    @media (min-width: 900px) {
        .as-row { grid-template-columns: minmax(0, 1.4fr) 5.5rem 5.5rem auto; align-items: end; }
    }
    .as-badge {
        display: inline-flex; align-items: center; border-radius: 999px;
        padding: .2rem .55rem; font-size: .68rem; font-weight: 800;
        background: #f1f5f9; color: #64748b;
    }
    .as-badge--on { background: #dcfce7; color: #15803d; }
    .as-badge--off { background: #f1f5f9; color: #64748b; }
    .as-actions { display: flex; flex-wrap: wrap; gap: .4rem; align-items: center; }
    .as-links {
        display: grid; gap: .75rem; margin-top: 1rem;
    }
    @media (min-width: 720px) {
        .as-links { grid-template-columns: 1fr 1fr; }
    }
    .as-link {
        display: block; text-decoration: none; color: inherit;
        background: #fff; border: 1px solid var(--line); border-radius: 1rem;
        padding: 1rem 1.1rem; box-shadow: 0 8px 24px rgba(15,23,42,.035);
        transition: border-color .15s ease;
    }
    .as-link:hover { border-color: #93c5fd; }
    .as-link__title { margin: 0; font-size: .9rem; font-weight: 800; color: var(--ink); }
    .as-link__desc { margin: .3rem 0 0; font-size: .78rem; color: var(--muted); line-height: 1.4; }
    .as-pay {
        display: grid; gap: 1rem;
        margin-top: 1rem;
    }
    @media (min-width: 900px) {
        .as-pay { grid-template-columns: 1fr 1fr; }
    }
    .as-pay-card {
        border: 1px solid var(--line);
        border-radius: 1rem;
        background: #f8fafc;
        padding: 1rem 1.05rem;
        display: grid;
        gap: .9rem;
        transition: border-color .15s ease, box-shadow .15s ease, opacity .15s ease;
    }
    .as-pay-card.is-off { opacity: .78; background: #f1f5f9; }
    .as-pay-card__top {
        display: flex; align-items: flex-start; justify-content: space-between; gap: .85rem;
    }
    .as-pay-card__title { margin: 0; font-size: .92rem; font-weight: 800; color: var(--ink); }
    .as-pay-card__desc { margin: .3rem 0 0; font-size: .76rem; color: var(--muted); line-height: 1.45; }
    .as-pay-status {
        display: inline-flex; align-items: center; gap: .35rem;
        margin-top: .45rem;
        border-radius: 999px;
        padding: .18rem .55rem;
        font-size: .68rem; font-weight: 800;
    }
    .as-pay-status.is-on { background: #dcfce7; color: #15803d; }
    .as-pay-status.is-off { background: #fee2e2; color: #b91c1c; }
    .as-switch {
        position: relative; width: 2.55rem; height: 1.45rem; flex-shrink: 0; margin-top: .15rem;
    }
    .as-switch input {
        position: absolute; inset: 0; z-index: 2;
        width: 100%; height: 100%; margin: 0;
        opacity: 0; cursor: pointer;
    }
    .as-switch__track {
        position: absolute; inset: 0; border-radius: 999px; background: #cbd5e1;
        transition: background .18s ease; pointer-events: none;
    }
    .as-switch__thumb {
        position: absolute; top: .15rem; left: .15rem;
        width: 1.15rem; height: 1.15rem; border-radius: 999px; background: #fff;
        box-shadow: 0 1px 3px rgba(15,23,42,.2);
        transition: transform .18s ease; pointer-events: none;
    }
    .as-switch input:checked + .as-switch__track { background: #2563eb; }
    .as-switch input:checked + .as-switch__track .as-switch__thumb { transform: translateX(1.1rem); }
    .as-pay-fields { display: grid; gap: .75rem; }
    @media (min-width: 560px) {
        .as-pay-fields { grid-template-columns: 1fr 1fr; }
    }
    .as-pay-fields.is-disabled { opacity: .55; pointer-events: none; }
</style>

@if (session('status'))
    <div class="as-flash">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="as-flash as-flash--err">
        Please fix the following:
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="as-tabs" role="tablist">
    <a href="{{ route('admin.settings.index', ['tab' => 'general']) }}" class="as-tab {{ $tab === 'general' ? 'is-active' : '' }}">General</a>
    <a href="{{ route('admin.settings.index', ['tab' => 'categories']) }}" class="as-tab {{ $tab === 'categories' ? 'is-active' : '' }}">Categories</a>
</div>

@if($tab === 'general')
    @php
        $currencyValue = old('currency', $settings['currency']);
        $currencyCode = strtoupper((string) ($currencyValue ?: 'NGN'));
        $currencyMeta = [
            'NGN' => ['flag' => 'ng', 'label' => 'Naira'],
            'USD' => ['flag' => 'us', 'label' => 'US Dollar'],
            'EUR' => ['flag' => 'eu', 'label' => 'Euro'],
            'GBP' => ['flag' => 'gb', 'label' => 'Pound Sterling'],
        ];
        $currentCurrency = $currencyMeta[$currencyCode] ?? null;
        $currentFlagUrl = $currentCurrency ? 'https://flagcdn.com/'.$currentCurrency['flag'].'.svg' : '';
        $membershipEnabled = (string) old('membership_platform_enabled', $settings['membership_platform_enabled'] ? '1' : '0') === '1';
        $activationEnabled = (string) old('journal_activation_enabled', $settings['journal_activation_enabled'] ? '1' : '0') === '1';
    @endphp

    <form
        method="POST"
        action="{{ route('admin.settings.general') }}"
        x-data="{
            membershipOn: @js($membershipEnabled),
            activationOn: @js($activationEnabled),
        }"
    >
        @csrf
        @method('PUT')

        <div class="as-card">
            <div class="as-card__head">
                <h2 class="as-card__title">Platform defaults</h2>
                <p class="as-card__desc">Branding, citations, and the default currency for platform charges.</p>
            </div>
            <div class="as-card__body">
                <div class="as-grid as-grid--2">
                    <div class="as-field">
                        <x-form-label for="name" field="settings.name" :required="true" reqClass="as-req">Short name</x-form-label>
                        <input id="name" name="name" type="text" required class="as-input" value="{{ old('name', $settings['name']) }}">
                    </div>
                    <div class="as-field">
                        <x-form-label for="full_name" field="settings.full_name" :required="true" reqClass="as-req">Full name</x-form-label>
                        <input id="full_name" name="full_name" type="text" required class="as-input" value="{{ old('full_name', $settings['full_name']) }}">
                    </div>
                    <div class="as-field">
                        <x-form-label for="organization" field="settings.organization" :required="true" reqClass="as-req">Organization</x-form-label>
                        <input id="organization" name="organization" type="text" required class="as-input" value="{{ old('organization', $settings['organization']) }}">
                    </div>
                    <div class="as-field">
                        <x-form-label for="publisher" field="settings.publisher" :required="true" reqClass="as-req">Publisher</x-form-label>
                        <input id="publisher" name="publisher" type="text" required class="as-input" value="{{ old('publisher', $settings['publisher']) }}">
                    </div>
                    <div class="as-field">
                        <x-form-label for="default_license" field="settings.default_license" :required="true" reqClass="as-req">Default license</x-form-label>
                        <x-license-picker
                            name="default_license"
                            id="default_license"
                            :value="old('default_license', $settings['default_license'])"
                            :required="true"
                            :allow-empty="false"
                            select-class="as-input"
                        />
                    </div>
                    <div class="as-field">
                        <x-form-label for="default_language" field="settings.default_language" :required="true" reqClass="as-req">Default language</x-form-label>
                        <input id="default_language" name="default_language" type="text" required class="as-input" value="{{ old('default_language', $settings['default_language']) }}" placeholder="en">
                    </div>
                    <div class="as-field">
                        <x-form-label for="currency" field="settings.currency" :required="true" reqClass="as-req">Currency</x-form-label>
                        <div style="display:flex;gap:.65rem;align-items:center">
                            <img
                                id="currency-flag"
                                src="{{ $currentFlagUrl }}"
                                alt=""
                                style="width:1.6rem;height:1.2rem;border-radius:.25rem;object-fit:cover;border:1px solid #e2e8f0;background:#fff;display:{{ $currentFlagUrl ? 'block' : 'none' }}"
                            >
                            <select id="currency" name="currency" required class="as-input" style="flex:1" aria-label="Currency">
                                @foreach($currencyMeta as $code => $m)
                                    <option value="{{ $code }}" @selected($currencyCode === $code)>
                                        {{ $code }} — {{ $m['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="as-card" style="margin-top:1rem">
            <div class="as-card__head">
                <h2 class="as-card__title">Payments</h2>
                <p class="as-card__desc">Turn platform-charged products on or off, and set their price and duration.</p>
            </div>
            <div class="as-card__body">
                <div class="as-pay">
                    <div class="as-pay-card" :class="!membershipOn && 'is-off'">
                        <div class="as-pay-card__top">
                            <div>
                                <h3 class="as-pay-card__title">Platform membership</h3>
                                <p class="as-pay-card__desc">Allows members to buy platform-wide access. Journal-scoped plans stay available separately.</p>
                                <span class="as-pay-status" :class="membershipOn ? 'is-on' : 'is-off'" x-text="membershipOn ? 'Active' : 'Inactive'"></span>
                            </div>
                            <label class="as-switch" title="Activate or deactivate platform membership purchases">
                                <input type="hidden" name="membership_platform_enabled" :value="membershipOn ? '1' : '0'">
                                <input type="checkbox" x-model="membershipOn" aria-label="Platform membership active">
                                <span class="as-switch__track"><span class="as-switch__thumb"></span></span>
                            </label>
                        </div>
                        <div class="as-pay-fields" :class="!membershipOn && 'is-disabled'">
                            <div class="as-field">
                                <x-form-label for="membership_platform_price" field="settings.membership_platform_price" :required="true" reqClass="as-req">Price</x-form-label>
                                <input id="membership_platform_price" name="membership_platform_price" type="number" min="0" required class="as-input" value="{{ old('membership_platform_price', $settings['membership_platform_price']) }}">
                            </div>
                            <div class="as-field">
                                <x-form-label for="membership_platform_days" field="settings.membership_platform_days" :required="true" reqClass="as-req">Duration (days)</x-form-label>
                                <input id="membership_platform_days" name="membership_platform_days" type="number" min="1" required class="as-input" value="{{ old('membership_platform_days', $settings['membership_platform_days']) }}">
                            </div>
                        </div>
                    </div>

                    <div class="as-pay-card" :class="!activationOn && 'is-off'">
                        <div class="as-pay-card__top">
                            <div>
                                <h3 class="as-pay-card__title">Journal activation fee</h3>
                                <p class="as-pay-card__desc">Required for new journals to list publicly and unlock management. Turning off waives the fee for new journals only.</p>
                                <span class="as-pay-status" :class="activationOn ? 'is-on' : 'is-off'" x-text="activationOn ? 'Active' : 'Inactive'"></span>
                            </div>
                            <label class="as-switch" title="Activate or deactivate journal activation fees">
                                <input type="hidden" name="journal_activation_enabled" :value="activationOn ? '1' : '0'">
                                <input type="checkbox" x-model="activationOn" aria-label="Journal activation fee active">
                                <span class="as-switch__track"><span class="as-switch__thumb"></span></span>
                            </label>
                        </div>
                        <div class="as-pay-fields" :class="!activationOn && 'is-disabled'">
                            <div class="as-field">
                                <x-form-label for="journal_activation_price" field="settings.journal_activation_price" :required="true" reqClass="as-req">Price</x-form-label>
                                <input id="journal_activation_price" name="journal_activation_price" type="number" min="0" required class="as-input" value="{{ old('journal_activation_price', $settings['journal_activation_price']) }}">
                            </div>
                            <div class="as-field">
                                <x-form-label for="journal_activation_days" field="settings.journal_activation_days" :required="true" reqClass="as-req">Duration (days)</x-form-label>
                                <input id="journal_activation_days" name="journal_activation_days" type="number" min="1" required class="as-input" value="{{ old('journal_activation_days', $settings['journal_activation_days']) }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="as-pay-card" style="margin-top:.85rem">
                    <div class="as-pay-card__top">
                        <div>
                            <h3 class="as-pay-card__title">Platform-enabled splits</h3>
                            <p class="as-pay-card__desc">Journal income using platform Paystack is routed with a Paystack <code>split_code</code> (SPL_…) created for that journal. Recipient shares and fees are defined on the split in Paystack, not as a percent here.</p>
                        </div>
                    </div>
                    <div class="as-pay-fields">
                        <div class="as-field">
                            <label for="payments_split_fee_percent" style="display:block;margin-bottom:.4rem;font-size:.78rem;font-weight:700;color:#334155">Platform fee %</label>
                            <input id="payments_split_fee_percent" name="payments_split_fee_percent" type="number" min="0" max="100" required class="as-input" value="{{ old('payments_split_fee_percent', $settings['payments_split_fee_percent'] ?? 0) }}">
                            <p class="as-hint" style="margin:.4rem 0 0;font-size:.74rem;color:#64748b">Not sent to Paystack. Checkout uses each journal’s split code.</p>
                            @error('payments_split_fee_percent')<p class="as-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="as-pay-card" style="margin-top:.85rem" x-data="{ doiOn: @js(old('doi_enabled', $settings['doi_enabled'] ?? true)) }">
                    <div class="as-pay-card__top">
                        <div>
                            <h3 class="as-pay-card__title">DOI deposits</h3>
                            <p class="as-pay-card__desc">Platform Crossref pool pricing and low-balance alerts. Top up journal credits under Admin → DOI credits.</p>
                            <span class="as-pay-status" :class="doiOn ? 'is-on' : 'is-off'" x-text="doiOn ? 'Active' : 'Inactive'"></span>
                        </div>
                        <label class="as-switch" title="Enable DOI deposit features">
                            <input type="hidden" name="doi_enabled" :value="doiOn ? '1' : '0'">
                            <input type="checkbox" x-model="doiOn" aria-label="DOI deposits active">
                            <span class="as-switch__track"><span class="as-switch__thumb"></span></span>
                        </label>
                    </div>
                    <div class="as-pay-fields" :class="!doiOn && 'is-disabled'">
                        <div class="as-field">
                            <label for="doi_usd_to_ngn" style="display:block;margin-bottom:.4rem;font-size:.78rem;font-weight:700;color:#334155">USD → NGN rate</label>
                            <input id="doi_usd_to_ngn" name="doi_usd_to_ngn" type="number" min="1" required class="as-input" value="{{ old('doi_usd_to_ngn', $settings['doi_usd_to_ngn'] ?? 2000) }}">
                        </div>
                        <div class="as-field">
                            <label for="doi_credit_price_usd" style="display:block;margin-bottom:.4rem;font-size:.78rem;font-weight:700;color:#334155">Credit price (USD)</label>
                            <input id="doi_credit_price_usd" name="doi_credit_price_usd" type="number" step="0.01" min="0" required class="as-input" value="{{ old('doi_credit_price_usd', $settings['doi_credit_price_usd'] ?? 1) }}">
                        </div>
                        <div class="as-field">
                            <label for="doi_threshold_absolute" style="display:block;margin-bottom:.4rem;font-size:.78rem;font-weight:700;color:#334155">Low balance (count)</label>
                            <input id="doi_threshold_absolute" name="doi_threshold_absolute" type="number" min="0" required class="as-input" value="{{ old('doi_threshold_absolute', $settings['doi_threshold_absolute'] ?? 20) }}">
                        </div>
                        <div class="as-field">
                            <label for="doi_threshold_percent" style="display:block;margin-bottom:.4rem;font-size:.78rem;font-weight:700;color:#334155">Low balance (%)</label>
                            <input id="doi_threshold_percent" name="doi_threshold_percent" type="number" min="0" max="100" required class="as-input" value="{{ old('doi_threshold_percent', $settings['doi_threshold_percent'] ?? 10) }}">
                        </div>
                        <div class="as-field" style="grid-column:1/-1">
                            <label for="doi_platform_prefix" style="display:block;margin-bottom:.4rem;font-size:.78rem;font-weight:700;color:#334155">Platform DOI prefix</label>
                            <input id="doi_platform_prefix" name="doi_platform_prefix" type="text" required class="as-input" value="{{ old('doi_platform_prefix', $settings['doi_platform_prefix'] ?? '10.0000/tjs') }}" placeholder="10.xxxx/tjs">
                        </div>
                    </div>
                </div>

                <div style="display:flex;gap:.55rem;flex-wrap:wrap;padding-top:1rem">
                    <button type="submit" class="admin-btn admin-btn-primary">Save settings</button>
                </div>
            </div>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const select = document.getElementById('currency');
            const img = document.getElementById('currency-flag');
            if (!select || !img) return;

            const meta = {
                NGN: { flag: 'ng' },
                USD: { flag: 'us' },
                EUR: { flag: 'eu' },
                GBP: { flag: 'gb' },
            };

            const update = () => {
                const code = String(select.value || '').toUpperCase();
                const m = meta[code];
                if (!m) {
                    img.src = '';
                    img.style.display = 'none';
                    return;
                }
                img.src = `https://flagcdn.com/${m.flag}.svg`;
                img.style.display = 'block';
            };

            select.addEventListener('change', update);
            update();
        });
    </script>

    <div class="as-links">
        <a class="as-link" href="{{ route('admin.membership-plans.index') }}">
            <p class="as-link__title">Membership plans</p>
            <p class="as-link__desc">Create and review platform or journal membership products.</p>
        </a>
        <a class="as-link" href="{{ route('admin.journals.index') }}">
            <p class="as-link__title">Journals &amp; branding</p>
            <p class="as-link__desc">Manage journal logos, header images, themes, and volumes.</p>
        </a>
    </div>
@endif

@if($tab === 'categories')
    <div class="as-card">
        <div class="as-card__head">
            <h2 class="as-card__title">Journal categories</h2>
            <p class="as-card__desc">Categories are unique to each journal. Manage them from that journal’s settings in Journal Manage.</p>
        </div>
        <div class="as-card__body">
            <p style="margin:0;font-size:.86rem;color:#475569;line-height:1.5">
                Each journal has its own taxonomy (for example “Research Article” in Journal A is separate from Journal B).
                Open a journal → <strong>Settings</strong> → Categories to create, rename, or deactivate entries.
            </p>
            <a href="{{ route('admin.journals.index') }}" class="admin-btn admin-btn-primary" style="margin-top:1rem">Browse journals</a>
        </div>
    </div>
@endif
@endsection
