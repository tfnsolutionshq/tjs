@extends('layouts.journal-manage')

@section('title', 'Fee catalog | '.$journal->title)
@section('page_title', 'Fee catalog')
@section('page_subtitle', 'Reusable fees for submissions, memberships, and publication (APC)')

@section('page_actions')
    <button type="button" class="admin-btn admin-btn-primary" onclick="window.dispatchEvent(new CustomEvent('jf-open-create'))">
        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 8v8M8 12h8"/></svg>
        New fee
    </button>
@endsection

@section('content')
@php
    $purposes = \App\Support\JournalFeePurpose::all();
    $feesPayload = $fees->map(fn ($f) => [
        'id' => $f->id,
        'name' => $f->name,
        'purpose' => $f->purpose,
        'amount' => (int) $f->amount,
        'currency' => $f->currency ?: 'NGN',
        'description' => $f->description,
        'is_active' => (bool) $f->is_active,
    ])->values();

    $currencyOptions = collect(['NGN', 'USD', 'EUR', 'GBP'])->map(fn ($code) => [
        'value' => $code,
        'label' => $code,
        'hint' => match ($code) {
            'NGN' => 'Nigerian naira · ₦',
            'USD' => 'US dollar · $',
            'EUR' => 'Euro · €',
            'GBP' => 'British pound · £',
            default => '',
        },
        'icon' => view('components.currency-icon', ['code' => $code])->render(),
    ])->values()->all();

    $purposeOptions = collect($purposes)->map(fn ($p) => [
        'value' => $p,
        'label' => \App\Support\JournalFeePurpose::label($p),
        'hint' => \App\Support\JournalFeePurpose::description($p),
    ])->values()->all();
@endphp

<style>
    .jf-stats { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.75rem; margin-bottom:1rem; }
    @media (min-width:720px) { .jf-stats { grid-template-columns:repeat(5,minmax(0,1fr)); } }
    .jf-stat { background:#fff; border:1px solid var(--line); border-radius:.95rem; padding:.85rem .9rem; text-decoration:none; color:inherit; }
    .jf-stat.is-active { border-color:#93c5fd; box-shadow:0 0 0 3px rgba(37,99,235,.12); }
    .jf-stat__label { font-size:.68rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--muted); }
    .jf-stat__value { margin-top:.35rem; font-size:1.4rem; font-weight:800; color:var(--ink); }
    .jf-toolbar { display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:1rem; padding:.85rem; background:#fff; border:1px solid var(--line); border-radius:1rem; }
    .jf-grid { display:grid; gap:.85rem; }
    @media (min-width:780px) { .jf-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    .jf-card { background:#fff; border:1px solid var(--line); border-radius:1rem; padding:1rem 1.05rem; box-shadow:0 8px 24px rgba(15,23,42,.035); }
    .jf-card__head { display:flex; justify-content:space-between; gap:.75rem; align-items:flex-start; }
    .jf-card__title { margin:0; font-size:1rem; font-weight:800; color:var(--ink); }
    .jf-card__meta { margin:.35rem 0 0; font-size:.82rem; color:var(--muted); }
    .jf-badge { display:inline-flex; padding:.2rem .55rem; border-radius:999px; font-size:.68rem; font-weight:700; }
    .jf-badge--submission { background:#eff6ff; color:#1d4ed8; }
    .jf-badge--membership { background:#f3e8ff; color:#7e22ce; }
    .jf-badge--article { background:#ecfdf5; color:#047857; }
    .jf-badge--active { background:#dcfce7; color:#15803d; }
    .jf-badge--inactive { background:#f1f5f9; color:#64748b; }
    .jf-actions { display:flex; flex-wrap:wrap; gap:.35rem; margin-top:.85rem; }
    .jf-drawer { position:fixed; inset:0; z-index:60; display:flex; justify-content:flex-end; background:rgba(15,23,42,.35); }
    .jf-drawer__panel { width:min(100%,28rem); background:#fff; height:100%; padding:1.1rem; overflow:auto; box-shadow:-12px 0 40px rgba(15,23,42,.15); }
    .jf-field { margin-top:.85rem; }
    .jf-input, .jf-select, .jf-textarea { width:100%; border:1px solid var(--line); border-radius:.75rem; padding:.58rem .75rem; font:inherit; transition:border-color .15s ease, box-shadow .15s ease; }
    .jf-input:hover, .jf-select:hover, .jf-textarea:hover { border-color:#cbd5e1; }
    .jf-input:focus, .jf-select:focus, .jf-textarea:focus { outline:none; border-color:#93c5fd; box-shadow:0 0 0 3px rgba(37,99,235,.12); }
    .jf-textarea { resize:vertical; min-height:5.75rem; line-height:1.5; color:var(--ink); }
    .jf-textarea::placeholder { color:#94a3b8; }
    .jf-field__hint { margin-top:.35rem; font-size:.72rem; color:var(--muted); line-height:1.4; }
    .jf-field__meta { margin-top:.35rem; display:flex; justify-content:space-between; gap:.75rem; font-size:.68rem; color:var(--muted); }
    .jf-drawer .tjs-select { width:100%; }
    .jf-drawer__foot { display:flex; justify-content:flex-end; gap:.5rem; margin-top:1.25rem; padding-top:1rem; border-top:1px solid var(--line); }
</style>

@if (session('status'))
    <div class="mp-flash">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="mp-flash mp-flash--error">{{ session('error') }}</div>
@endif

<div class="jf-stats">
    <a href="{{ route('journal.manage.fees.index', $journal) }}" class="jf-stat {{ ! $purpose && ! $active ? 'is-active' : '' }}">
        <p class="jf-stat__label">All</p>
        <p class="jf-stat__value">{{ $stats['total'] }}</p>
    </a>
    @foreach($purposes as $p)
        <a href="{{ route('journal.manage.fees.index', [$journal, 'purpose' => $p]) }}" class="jf-stat {{ $purpose === $p ? 'is-active' : '' }}">
            <p class="jf-stat__label">{{ \App\Support\JournalFeePurpose::label($p) }}</p>
            <p class="jf-stat__value">{{ $stats[$p] ?? 0 }}</p>
        </a>
    @endforeach
</div>

<form method="GET" class="jf-toolbar">
    <input type="search" name="q" value="{{ $q }}" placeholder="Search fees…" class="jf-input" style="flex:1;min-width:12rem">
    <select name="active" class="jf-select" style="width:auto" onchange="this.form.requestSubmit()">
        <option value="">Any status</option>
        <option value="1" @selected($active === '1')>Active</option>
        <option value="0" @selected($active === '0')>Inactive</option>
    </select>
    @if($purpose)<input type="hidden" name="purpose" value="{{ $purpose }}">@endif
</form>

@if($fees->isEmpty())
    <div class="jf-card">
        <p style="margin:0;font-weight:700">No fees yet</p>
        <p style="margin:.35rem 0 0;color:var(--muted);font-size:.88rem">Create fees here, then pick them when setting up membership plans, publication pricing, or author submissions.</p>
    </div>
@else
    <div class="jf-grid">
        @foreach($fees as $fee)
            <article class="jf-card">
                <div class="jf-card__head">
                    <div>
                        <h3 class="jf-card__title">{{ $fee->name }}</h3>
                        <p class="jf-card__meta">{{ number_format($fee->amount) }} {{ strtoupper($fee->currency) }}</p>
                        @if($fee->description)
                            <p class="jf-card__meta" style="margin-top:.45rem">{{ $fee->description }}</p>
                        @endif
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:.35rem;justify-content:flex-end">
                        <span class="jf-badge jf-badge--{{ $fee->purpose }}">{{ \App\Support\JournalFeePurpose::label($fee->purpose) }}</span>
                        <span class="jf-badge {{ $fee->is_active ? 'jf-badge--active' : 'jf-badge--inactive' }}">{{ $fee->is_active ? 'Active' : 'Inactive' }}</span>
                    </div>
                </div>
                <div class="jf-actions">
                    <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" onclick="window.dispatchEvent(new CustomEvent('jf-open-edit', { detail: {{ $fee->id }} }))">Edit</button>
                    <form method="POST" action="{{ route('journal.manage.fees.toggle', [$journal, $fee]) }}">
                        @csrf
                        <button type="submit" class="admin-btn admin-btn-secondary admin-btn-sm">{{ $fee->is_active ? 'Deactivate' : 'Activate' }}</button>
                    </form>
                    <form method="POST" action="{{ route('journal.manage.fees.destroy', [$journal, $fee]) }}" onsubmit="return confirm('Delete this fee?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="admin-btn admin-btn-secondary admin-btn-sm">Delete</button>
                    </form>
                </div>
            </article>
        @endforeach
    </div>
@endif

<div
    x-data="journalFees({
        fees: @js($feesPayload),
        purposes: @js(collect($purposes)->mapWithKeys(fn ($p) => [$p => ['label' => \App\Support\JournalFeePurpose::label($p), 'description' => \App\Support\JournalFeePurpose::description($p)]])->all()),
        storeUrl: @js(route('journal.manage.fees.store', $journal)),
        updateUrl: @js(url('/j/'.$journal->slug.'/manage/fees')),
        old: @js(old()),
    })"
    x-show="drawerOpen"
    x-cloak
    class="jf-drawer"
    @jf-open-create.window="openCreate()"
    @jf-open-edit.window="openEdit($event.detail)"
    @keydown.escape.window="closeDrawer()"
>
    <div class="jf-drawer__panel" @click.outside="closeDrawer()">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:.75rem">
            <h2 style="margin:0;font-size:1.1rem;font-weight:800" x-text="editingId ? 'Edit fee' : 'New fee'"></h2>
            <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" @click="closeDrawer()">Close</button>
        </div>

        <form method="POST" :action="formAction" style="margin-top:.5rem">
            @csrf
            <template x-if="editingId"><input type="hidden" name="_method" value="PUT"></template>

            <div class="jf-field">
                <label class="jf-stat__label" for="jf_name">Name</label>
                <input id="jf_name" name="name" type="text" required class="jf-input" x-model="form.name">
            </div>

            <div class="jf-field">
                <label class="jf-stat__label" for="jf_purpose">Purpose</label>
                <div x-ref="purposeSelect" @picker-change="form.purpose = $event.detail">
                    <x-tjs-select
                        variant="rich"
                        input-id="jf_purpose"
                        :options="$purposeOptions"
                        value="submission"
                    />
                </div>
                <input type="hidden" name="purpose" :value="form.purpose">
            </div>

            <div class="jf-field" style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
                <div>
                    <label class="jf-stat__label" for="jf_amount">Amount</label>
                    <input id="jf_amount" name="amount" type="number" min="0" required class="jf-input" x-model="form.amount">
                </div>
                <div>
                    <label class="jf-stat__label" for="jf_currency">Currency</label>
                    <div x-ref="currencySelect" @picker-change="form.currency = $event.detail">
                        <x-tjs-select
                            variant="rich"
                            input-id="jf_currency"
                            :options="$currencyOptions"
                            value="NGN"
                        />
                    </div>
                    <input type="hidden" name="currency" :value="form.currency">
                </div>
            </div>

            <div class="jf-field">
                <label class="jf-stat__label" for="jf_description">Description <span style="font-weight:600;text-transform:none;letter-spacing:0">(optional)</span></label>
                <textarea
                    id="jf_description"
                    name="description"
                    rows="3"
                    maxlength="500"
                    class="jf-textarea"
                    placeholder="Short note for your team — shown on fee cards and when picking this fee elsewhere."
                    x-model="form.description"
                ></textarea>
                <div class="jf-field__meta">
                    <span class="jf-field__hint">Keep it brief. Authors see this when a fee is linked to submissions or plans.</span>
                    <span x-text="(form.description || '').length + '/500'"></span>
                </div>
            </div>

            <div class="jf-field" style="display:flex;align-items:center;justify-content:space-between;gap:.75rem">
                <span class="jf-stat__label">Active</span>
                <input type="hidden" name="is_active" :value="form.is_active ? '1' : '0'">
                <button type="button" class="amp-switch" :class="form.is_active && 'is-on'" @click="form.is_active = !form.is_active"></button>
            </div>

            <div class="jf-drawer__foot">
                <button type="button" class="admin-btn admin-btn-secondary" @click="closeDrawer()">Cancel</button>
                <button type="submit" class="admin-btn admin-btn-primary" x-text="editingId ? 'Save changes' : 'Create fee'"></button>
            </div>
        </form>
    </div>
</div>

<script>
function journalFees(cfg) {
    const blank = () => ({
        name: '',
        purpose: 'submission',
        amount: '',
        currency: 'NGN',
        description: '',
        is_active: true,
    });

    return {
        fees: cfg.fees || [],
        purposes: cfg.purposes || {},
        drawerOpen: false,
        editingId: null,
        form: blank(),
        storeUrl: cfg.storeUrl,
        updateUrl: cfg.updateUrl,
        get formAction() {
            return this.editingId ? `${this.updateUrl}/${this.editingId}` : this.storeUrl;
        },
        syncPickers() {
            this.$nextTick(() => {
                this.$refs.purposeSelect?.firstElementChild?.dispatchEvent(
                    new CustomEvent('select-set', { detail: this.form.purpose })
                );
                this.$refs.currencySelect?.firstElementChild?.dispatchEvent(
                    new CustomEvent('select-set', { detail: this.form.currency })
                );
            });
        },
        openCreate() {
            this.editingId = null;
            this.form = blank();
            if (cfg.old?.name) {
                this.form = { ...blank(), ...cfg.old, is_active: cfg.old.is_active !== '0' };
            }
            this.drawerOpen = true;
            this.syncPickers();
        },
        openEdit(id) {
            const fee = this.fees.find((f) => f.id === id);
            if (!fee) return;
            this.editingId = fee.id;
            this.form = { ...fee, amount: fee.amount, is_active: !!fee.is_active };
            this.drawerOpen = true;
            this.syncPickers();
        },
        closeDrawer() {
            this.drawerOpen = false;
        },
    };
}
</script>
@endsection
