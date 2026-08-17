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
    <div class="as-card">
        <div class="as-card__head">
            <h2 class="as-card__title">Platform defaults</h2>
            <p class="as-card__desc">These values feed citations, PDFs, membership defaults, and public branding copy.</p>
        </div>
        <div class="as-card__body">
            <form method="POST" action="{{ route('admin.settings.general') }}" class="as-grid as-grid--2">
                @csrf
                @method('PUT')

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
                    <input id="currency" name="currency" type="text" required class="as-input" value="{{ old('currency', $settings['currency']) }}">
                </div>
                <div class="as-field">
                    <x-form-label for="membership_platform_price" field="settings.membership_platform_price" :required="true" reqClass="as-req">Platform membership price</x-form-label>
                    <input id="membership_platform_price" name="membership_platform_price" type="number" min="0" required class="as-input" value="{{ old('membership_platform_price', $settings['membership_platform_price']) }}">
                </div>
                <div class="as-field">
                    <x-form-label for="membership_platform_days" field="settings.membership_platform_days" :required="true" reqClass="as-req">Platform membership days</x-form-label>
                    <input id="membership_platform_days" name="membership_platform_days" type="number" min="1" required class="as-input" value="{{ old('membership_platform_days', $settings['membership_platform_days']) }}">
                </div>

                <div style="grid-column:1/-1;display:flex;gap:.55rem;flex-wrap:wrap;padding-top:.25rem">
                    <button type="submit" class="admin-btn admin-btn-primary">Save general settings</button>
                </div>
            </form>
        </div>
    </div>

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
