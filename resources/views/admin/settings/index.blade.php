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
                    <label for="name">Short name <span class="as-req">*</span></label>
                    <input id="name" name="name" type="text" required class="as-input" value="{{ old('name', $settings['name']) }}">
                </div>
                <div class="as-field">
                    <label for="full_name">Full name <span class="as-req">*</span></label>
                    <input id="full_name" name="full_name" type="text" required class="as-input" value="{{ old('full_name', $settings['full_name']) }}">
                </div>
                <div class="as-field">
                    <label for="organization">Organization <span class="as-req">*</span></label>
                    <input id="organization" name="organization" type="text" required class="as-input" value="{{ old('organization', $settings['organization']) }}">
                </div>
                <div class="as-field">
                    <label for="publisher">Publisher <span class="as-req">*</span></label>
                    <input id="publisher" name="publisher" type="text" required class="as-input" value="{{ old('publisher', $settings['publisher']) }}">
                </div>
                <div class="as-field">
                    <label for="default_license">Default license <span class="as-req">*</span></label>
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
                    <label for="default_language">Default language <span class="as-req">*</span></label>
                    <input id="default_language" name="default_language" type="text" required class="as-input" value="{{ old('default_language', $settings['default_language']) }}" placeholder="en">
                </div>
                <div class="as-field">
                    <label for="currency">Currency <span class="as-req">*</span></label>
                    <input id="currency" name="currency" type="text" required class="as-input" value="{{ old('currency', $settings['currency']) }}">
                </div>
                <div class="as-field">
                    <label for="membership_platform_price">Platform membership price <span class="as-req">*</span></label>
                    <input id="membership_platform_price" name="membership_platform_price" type="number" min="0" required class="as-input" value="{{ old('membership_platform_price', $settings['membership_platform_price']) }}">
                    <p class="as-hint">Whole currency units (e.g. NGN), not kobo/cents.</p>
                </div>
                <div class="as-field">
                    <label for="membership_platform_days">Platform membership days <span class="as-req">*</span></label>
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
    <div class="as-card" style="margin-bottom:1rem">
        <div class="as-card__head">
            <h2 class="as-card__title">Add category</h2>
            <p class="as-card__desc">Categories appear as multi-select chips on the article form.</p>
        </div>
        <div class="as-card__body">
            <form method="POST" action="{{ route('admin.settings.categories.store') }}" class="as-grid as-grid--2">
                @csrf
                <div class="as-field">
                    <label for="new_category_name">Name <span class="as-req">*</span></label>
                    <input id="new_category_name" name="name" type="text" required class="as-input" value="{{ old('name') }}" placeholder="e.g. Methods Paper">
                </div>
                <div class="as-field">
                    <label for="new_category_active">Status</label>
                    <select id="new_category_active" name="is_active" class="as-select">
                        <option value="1" @selected(old('is_active', '1') === '1')>Active</option>
                        <option value="0" @selected(old('is_active') === '0')>Inactive</option>
                    </select>
                </div>
                <div style="grid-column:1/-1">
                    <button type="submit" class="admin-btn admin-btn-primary">Create category</button>
                </div>
            </form>
        </div>
    </div>

    <div class="as-card">
        <div class="as-card__head">
            <h2 class="as-card__title">All categories</h2>
            <p class="as-card__desc">Rename, reorder, activate/deactivate, or delete unused categories.</p>
        </div>
        <div class="as-card__body">
            @forelse($categories as $category)
                <form method="POST" action="{{ route('admin.settings.categories.update', $category) }}" class="as-row">
                    @csrf
                    @method('PUT')
                    <div class="as-field">
                        <label>Name <span class="as-req">*</span></label>
                        <input name="name" type="text" required class="as-input" value="{{ old('name_'.$category->id, $category->name) }}">
                        <p class="as-hint">
                            <span class="as-badge">{{ $category->articles_count }} {{ \Illuminate\Support\Str::plural('article', $category->articles_count) }}</span>
                            <span class="as-badge {{ $category->is_active ? 'as-badge--on' : 'as-badge--off' }}">{{ $category->is_active ? 'Active' : 'Inactive' }}</span>
                        </p>
                    </div>
                    <div class="as-field">
                        <label>Sort</label>
                        <input name="sort_order" type="number" min="0" class="as-input" value="{{ old('sort_order_'.$category->id, $category->sort_order) }}">
                    </div>
                    <div class="as-field">
                        <label>Status</label>
                        <select name="is_active" class="as-select">
                            <option value="1" @selected($category->is_active)>Active</option>
                            <option value="0" @selected(! $category->is_active)>Inactive</option>
                        </select>
                    </div>
                    <div class="as-actions">
                        <button type="submit" class="admin-btn admin-btn-primary" style="padding:.5rem .8rem;font-size:.78rem">Save</button>
                        @if($category->articles_count === 0)
                            <button
                                type="submit"
                                form="delete-category-{{ $category->id }}"
                                class="admin-btn admin-btn-secondary"
                                style="padding:.5rem .8rem;font-size:.78rem;color:#b91c1c;border-color:#fecaca"
                                onclick="return confirm('Delete category {{ $category->name }}?')"
                            >Delete</button>
                        @endif
                    </div>
                </form>
                @if($category->articles_count === 0)
                    <form id="delete-category-{{ $category->id }}" method="POST" action="{{ route('admin.settings.categories.destroy', $category) }}" style="display:none">
                        @csrf
                        @method('DELETE')
                    </form>
                @endif
            @empty
                <p style="margin:0;font-size:.86rem;color:var(--muted)">No categories yet.</p>
            @endforelse
        </div>
    </div>
@endif
@endsection
