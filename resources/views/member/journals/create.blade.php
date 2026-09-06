@extends('layouts.member')

@section('title', 'Create a journal | '.config('tjs.name'))
@section('page_title', 'Create a journal')
@section('page_subtitle', 'Set up the essentials — you become the manager automatically')

@section('page_actions')
    <a href="{{ route('dashboard') }}" class="admin-btn admin-btn-ghost">Cancel</a>
@endsection

@section('content')
@php
    $reviewType = old('review_type', $journal->review_type ?: \App\Support\ReviewType::CLOSED);
    $hasOptionalOld = filled(old('subtitle')) || filled(old('description')) || filled(old('publisher')) || filled(old('initials')) || (old('language') && old('language') !== 'en');
@endphp

<div
    class="mj"
    x-data="memberJournalCreate(@js([
        'title' => old('title', ''),
        'slug' => old('slug', ''),
        'initials' => old('initials', ''),
        'reviewType' => $reviewType,
        'checkUrl' => route('journals.check-slug'),
        'showOptional' => $hasOptionalOld || $errors->hasAny(['subtitle', 'description', 'publisher', 'initials', 'language']),
        'appHost' => parse_url(config('app.url'), PHP_URL_HOST) ?: request()->getHost(),
    ]))"
>
    <ol class="mj-steps" aria-label="What happens next">
        <li class="mj-steps__item is-current">
            <span class="mj-steps__num">1</span>
            <span class="mj-steps__copy">
                <strong>Basics</strong>
                <span>Title, URL, review model</span>
            </span>
        </li>
        <li class="mj-steps__item">
            <span class="mj-steps__num">2</span>
            <span class="mj-steps__copy">
                <strong>You manage it</strong>
                <span>Assigned as journal manager</span>
            </span>
        </li>
        <li class="mj-steps__item">
            <span class="mj-steps__num">3</span>
            <span class="mj-steps__copy">
                <strong>Activation</strong>
                <span>Pay to list, or skip (tools locked)</span>
            </span>
        </li>
        <li class="mj-steps__item">
            <span class="mj-steps__num">4</span>
            <span class="mj-steps__copy">
                <strong>Polish later</strong>
                <span>Theme, ISSN, volumes, team</span>
            </span>
        </li>
    </ol>

    <form
        method="POST"
        action="{{ route('member.journals.store') }}"
        class="mj-shell"
        @submit="if (slugUnavailable()) { $event.preventDefault() }"
    >
        @csrf

        <div class="mj-main">
            <section class="mj-card">
                <header class="mj-card__head">
                    <h2 class="mj-card__title">Journal identity</h2>
                    <p class="mj-card__desc">This is what readers see first on your public site.</p>
                </header>

                <div class="mj-card__body">
                    <div class="mj-field">
                        <label class="mj-label" for="title">Journal title</label>
                        <input
                            id="title"
                            name="title"
                            type="text"
                            class="mj-input mj-input--lg"
                            required
                            maxlength="255"
                            autofocus
                            autocomplete="organization"
                            placeholder="e.g. TFN Open Research"
                            x-model="title"
                            @input="syncFromTitle()"
                        >
                        <x-input-error :messages="$errors->get('title')" class="mj-error" />
                    </div>

                    <div class="mj-field">
                        <div class="mj-label-row">
                            <label class="mj-label" for="slug">Public URL</label>
                            <button type="button" class="mj-linkish" @click="resetSlugFromTitle()" x-show="slugTouched" x-cloak>
                                Match title
                            </button>
                        </div>
                        <div class="mj-slug" :class="{ 'is-ok': slugStatus === 'available', 'is-bad': slugStatus === 'taken' }">
                            <span class="mj-slug__prefix">/j/</span>
                            <input
                                id="slug"
                                name="slug"
                                type="text"
                                class="mj-input mj-slug__input"
                                required
                                maxlength="255"
                                autocomplete="off"
                                placeholder="tfn-open-research"
                                x-model="slug"
                                @input="normalizeSlug(); queueSlugCheck()"
                            >
                            <span class="mj-slug__status" aria-live="polite">
                                <template x-if="slugStatus === 'checking'">
                                    <span class="mj-spinner" title="Checking"></span>
                                </template>
                                <template x-if="slugStatus === 'available'">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#047857" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                                <template x-if="slugStatus === 'taken'">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#b42318" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
                                </template>
                            </span>
                        </div>
                        <p class="mj-help" x-show="slugMessage" x-text="slugMessage" x-cloak
                           :class="{
                               'is-ok': slugStatus === 'available',
                               'is-bad': slugStatus === 'taken',
                               'is-muted': slugStatus === 'checking' || slugStatus === 'idle'
                           }"></p>
                        <x-input-error :messages="$errors->get('slug')" class="mj-error" />
                    </div>
                </div>
            </section>

            <section class="mj-card">
                <header class="mj-card__head">
                    <h2 class="mj-card__title">Peer review</h2>
                    <p class="mj-card__desc">You can change this later in journal settings.</p>
                </header>
                <div class="mj-card__body">
                    <div class="mj-review" role="radiogroup" aria-label="Peer review type">
                        <label class="mj-choice" :class="{ 'is-selected': reviewType === 'closed' }">
                            <input type="radio" name="review_type" value="closed" class="sr-only" x-model="reviewType" @checked($reviewType === 'closed')>
                            <span class="mj-choice__check" aria-hidden="true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span class="mj-choice__body">
                                <strong>Closed review</strong>
                                <small>Reviewers do not see author identity. Best for most journals.</small>
                            </span>
                        </label>
                        <label class="mj-choice" :class="{ 'is-selected': reviewType === 'open' }">
                            <input type="radio" name="review_type" value="open" class="sr-only" x-model="reviewType" @checked($reviewType === 'open')>
                            <span class="mj-choice__check" aria-hidden="true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span class="mj-choice__body">
                                <strong>Open review</strong>
                                <small>Authors are visible to reviewers during peer review.</small>
                            </span>
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('review_type')" class="mj-error" />
                </div>
            </section>

            <section class="mj-card mj-card--soft">
                <button
                    type="button"
                    class="mj-optional-toggle"
                    @click="showOptional = !showOptional"
                    :aria-expanded="showOptional.toString()"
                >
                    <span>
                        <strong>Optional details</strong>
                        <span class="mj-optional-toggle__hint">Subtitle, about, publisher, language, initials</span>
                    </span>
                    <svg class="mj-optional-toggle__chevron" :class="{ 'is-open': showOptional }" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                </button>

                <div class="mj-card__body mj-optional" x-show="showOptional" x-cloak>
                    <div class="mj-grid-2">
                        <div class="mj-field">
                            <label class="mj-label" for="initials">Initials</label>
                            <input id="initials" name="initials" type="text" class="mj-input" maxlength="8" x-model="initials" @input="initialsTouched = true" placeholder="Auto from title" style="text-transform:uppercase">
                            <x-input-error :messages="$errors->get('initials')" class="mj-error" />
                        </div>
                        <div class="mj-field">
                            <x-language-picker
                                name="language"
                                id="language"
                                :value="old('language', 'en')"
                                :use-form-label="false"
                            />
                            <x-input-error :messages="$errors->get('language')" class="mj-error" />
                        </div>
                    </div>

                    <div class="mj-field">
                        <label class="mj-label" for="subtitle">Subtitle</label>
                        <input id="subtitle" name="subtitle" type="text" class="mj-input" maxlength="255" value="{{ old('subtitle') }}" placeholder="A short line under the title">
                        <x-input-error :messages="$errors->get('subtitle')" class="mj-error" />
                    </div>

                    <div class="mj-field">
                        <label class="mj-label" for="description">About</label>
                        <x-rich-text
                            id="description"
                            name="description"
                            :value="old('description')"
                            placeholder="Scope, audience, and what this journal publishes."
                            :rows="4"
                        />
                        <x-input-error :messages="$errors->get('description')" class="mj-error" />
                    </div>

                    <div class="mj-field">
                        <label class="mj-label" for="publisher">Publisher</label>
                        <input id="publisher" name="publisher" type="text" class="mj-input" maxlength="255" value="{{ old('publisher', config('tjs.publisher')) }}" placeholder="{{ config('tjs.publisher') }}">
                        <x-input-error :messages="$errors->get('publisher')" class="mj-error" />
                    </div>
                </div>
            </section>
        </div>

        <aside class="mj-side">
            <div class="mj-preview">
                <p class="mj-preview__label">Live preview</p>
                <div class="mj-preview__card">
                    <div class="mj-preview__mark" x-text="(initials || previewInitials()).slice(0, 3) || 'JN'"></div>
                    <div class="min-w-0">
                        <p class="mj-preview__title" x-text="title.trim() || 'Your journal title'"></p>
                        <p class="mj-preview__url">
                            <span x-text="appHost"></span><span>/j/</span><span x-text="slug.trim() || 'your-slug'"></span>
                        </p>
                    </div>
                </div>
                <ul class="mj-preview__list">
                    <li>Public site opens at the URL above</li>
                    <li>You are set as journal manager</li>
                    <li>Branding & volumes come next</li>
                </ul>
            </div>

            <div class="mj-submit">
                <button
                    type="submit"
                    class="mj-submit__btn"
                    :disabled="slugUnavailable() || slugStatus === 'checking' || !title.trim() || !slug.trim()"
                    :class="{ 'is-disabled': slugUnavailable() || slugStatus === 'checking' || !title.trim() || !slug.trim() }"
                >
                    Create journal
                </button>
                <a href="{{ route('dashboard') }}" class="mj-submit__cancel">Back to dashboard</a>
                <p class="mj-submit__note">Next you’ll choose to pay the listing activation fee or skip (management tools stay locked until paid).</p>
            </div>
        </aside>
    </form>
</div>

<style>
    .mj { display: grid; gap: 1.25rem; width: 100%; max-width: none; }
    .mj-steps {
        margin: 0; padding: 0; list-style: none;
        display: grid; gap: .65rem;
    }
    @media (min-width: 720px) {
        .mj-steps { grid-template-columns: repeat(3, 1fr); gap: .75rem; }
    }
    .mj-steps__item {
        display: flex; gap: .7rem; align-items: flex-start;
        padding: .85rem .95rem;
        border-radius: .9rem;
        background: #fff;
        border: 1px solid var(--line);
    }
    .mj-steps__item.is-current {
        border-color: color-mix(in srgb, var(--blue) 35%, var(--line));
        background: linear-gradient(180deg, #fff 0%, #f7faff 100%);
        box-shadow: 0 8px 20px rgba(37, 99, 235, .06);
    }
    .mj-steps__num {
        width: 1.55rem; height: 1.55rem; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 999px; font-size: .72rem; font-weight: 800;
        background: #e8eef7; color: #334155;
    }
    .mj-steps__item.is-current .mj-steps__num {
        background: var(--blue-strong); color: #fff;
    }
    .mj-steps__copy { display: grid; gap: .12rem; min-width: 0; }
    .mj-steps__copy strong { font-size: .84rem; color: var(--ink); }
    .mj-steps__copy span { font-size: .75rem; color: var(--muted); line-height: 1.35; }

    .mj-shell {
        display: grid; gap: 1.15rem; align-items: start;
    }
    @media (min-width: 980px) {
        .mj-shell { grid-template-columns: minmax(0, 1fr) minmax(18rem, 22rem); gap: 1.35rem; }
    }
    .mj-main { display: grid; gap: 1rem; }
    .mj-card {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 1rem;
        overflow: visible;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .035);
    }
    .mj-card--soft { background: #fbfcfe; position: relative; z-index: 2; }
    .mj-card__head {
        padding: 1.1rem 1.2rem .15rem;
    }
    .mj-card__title {
        margin: 0; font-size: 1rem; font-weight: 800; color: var(--ink);
    }
    .mj-card__desc {
        margin: .3rem 0 0; font-size: .82rem; color: var(--muted); line-height: 1.45;
    }
    .mj-card__body {
        padding: 1rem 1.2rem 1.25rem;
        display: grid; gap: 1rem;
    }

    .mj-field { display: grid; gap: .4rem; }
    .mj-label-row { display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
    .mj-label { font-size: .8rem; font-weight: 700; color: #1e293b; }
    .mj-linkish {
        border: 0; background: none; padding: 0; cursor: pointer;
        color: var(--blue-strong); font-size: .75rem; font-weight: 700;
    }
    .mj-linkish:hover { text-decoration: underline; }
    .mj-input {
        width: 100%;
        border: 1px solid #d8dee8;
        border-radius: .7rem;
        padding: .72rem .85rem;
        font-size: .94rem;
        outline: none;
        background: #fff;
        transition: border-color .15s, box-shadow .15s;
        color: var(--ink);
    }
    .mj-input--lg { padding: .85rem .95rem; font-size: 1.02rem; font-weight: 600; }
    .mj-input:focus {
        border-color: var(--blue);
        box-shadow: 0 0 0 3px rgba(47,125,225,.14);
    }
    .mj-textarea { resize: vertical; min-height: 5.5rem; line-height: 1.5; font-weight: 400; }
    select.mj-input { cursor: pointer; }

    .mj-slug {
        display: flex; align-items: stretch;
        border: 1px solid #d8dee8; border-radius: .7rem; overflow: hidden;
        background: #fff;
        transition: border-color .15s, box-shadow .15s;
    }
    .mj-slug:focus-within {
        border-color: var(--blue);
        box-shadow: 0 0 0 3px rgba(47,125,225,.14);
    }
    .mj-slug.is-ok { border-color: #86efac; }
    .mj-slug.is-bad { border-color: #fca5a5; }
    .mj-slug__prefix {
        display: inline-flex; align-items: center;
        padding: 0 .8rem;
        background: #f1f5f9;
        color: #64748b;
        font-size: .82rem; font-weight: 700;
        border-right: 1px solid #e8edf3;
    }
    .mj-slug__input { border: 0 !important; box-shadow: none !important; border-radius: 0; }
    .mj-slug__status {
        display: inline-flex; align-items: center; justify-content: center;
        width: 2.4rem; flex-shrink: 0; color: var(--muted);
    }
    .mj-spinner {
        width: .9rem; height: .9rem;
        border: 2px solid #cbd5e1; border-top-color: var(--blue);
        border-radius: 999px; animation: mj-spin .7s linear infinite;
    }
    @keyframes mj-spin { to { transform: rotate(360deg); } }
    .mj-help { margin: 0; font-size: .76rem; min-height: 1rem; }
    .mj-help.is-ok { color: #047857; }
    .mj-help.is-bad { color: #b42318; }
    .mj-help.is-muted { color: var(--muted); }
    .mj-error { color: #b42318; font-size: .8rem; margin: 0; }
    .mj-grid-2 { display: grid; gap: 1rem; }
    @media (min-width: 640px) { .mj-grid-2 { grid-template-columns: 1fr 1fr; } }

    .mj-review { display: grid; gap: .65rem; }
    @media (min-width: 640px) { .mj-review { grid-template-columns: 1fr 1fr; } }
    .mj-choice {
        display: flex; gap: .75rem; align-items: flex-start;
        padding: 1rem;
        border: 1px solid var(--line);
        border-radius: .85rem;
        cursor: pointer;
        background: #fff;
        transition: border-color .15s, background .15s, box-shadow .15s, transform .15s;
    }
    .mj-choice:hover { border-color: #93c5fd; transform: translateY(-1px); }
    .mj-choice.is-selected {
        border-color: color-mix(in srgb, var(--blue) 50%, #93c5fd);
        background: color-mix(in srgb, var(--blue) 5%, #fff);
        box-shadow: 0 0 0 3px rgba(47,125,225,.08);
    }
    .mj-choice__check {
        width: 1.25rem; height: 1.25rem; margin-top: .1rem; flex-shrink: 0;
        border-radius: 999px; border: 1.5px solid #cbd5e1;
        display: inline-flex; align-items: center; justify-content: center;
        color: transparent; background: #fff;
    }
    .mj-choice.is-selected .mj-choice__check {
        border-color: var(--blue-strong);
        background: var(--blue-strong);
        color: #fff;
    }
    .mj-choice__body strong { display: block; font-size: .9rem; color: var(--ink); }
    .mj-choice__body small { display: block; margin-top: .25rem; color: var(--muted); font-size: .76rem; line-height: 1.4; }
    .sr-only {
        position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
        overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0;
    }

    .mj-optional-toggle {
        width: 100%;
        display: flex; align-items: center; justify-content: space-between; gap: 1rem;
        padding: 1rem 1.2rem;
        border: 0; background: transparent; cursor: pointer; text-align: left;
    }
    .mj-optional-toggle strong { display: block; font-size: .92rem; color: var(--ink); }
    .mj-optional-toggle__hint { display: block; margin-top: .15rem; font-size: .76rem; color: var(--muted); }
    .mj-optional-toggle__chevron { color: var(--muted); transition: transform .2s ease; flex-shrink: 0; }
    .mj-optional-toggle__chevron.is-open { transform: rotate(180deg); }
    .mj-optional { border-top: 1px solid var(--line); }

    .mj-side {
        display: grid; gap: 1rem;
    }
    @media (min-width: 980px) {
        .mj-side { position: sticky; top: 1rem; }
    }
    .mj-preview {
        background: linear-gradient(165deg, #0b1220 0%, #172554 70%, #1d4ed8 150%);
        color: #fff;
        border-radius: 1rem;
        padding: 1.15rem 1.1rem 1.2rem;
        box-shadow: 0 16px 36px rgba(15, 23, 42, .18);
    }
    .mj-preview__label {
        margin: 0 0 .75rem;
        font-size: .65rem; font-weight: 800; letter-spacing: .14em; text-transform: uppercase;
        color: rgba(255,255,255,.5);
    }
    .mj-preview__card {
        display: flex; gap: .75rem; align-items: center;
        padding: .85rem;
        border-radius: .8rem;
        background: rgba(255,255,255,.08);
        border: 1px solid rgba(255,255,255,.1);
    }
    .mj-preview__mark {
        width: 2.5rem; height: 2.5rem; flex-shrink: 0;
        border-radius: .65rem;
        display: inline-flex; align-items: center; justify-content: center;
        background: #fff; color: #0f172a;
        font-size: .72rem; font-weight: 800; letter-spacing: .04em;
    }
    .mj-preview__title {
        margin: 0; font-size: .92rem; font-weight: 700; line-height: 1.3;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .mj-preview__url {
        margin: .2rem 0 0; font-size: .72rem; color: rgba(255,255,255,.62);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .mj-preview__list {
        margin: 1rem 0 0; padding: 0; list-style: none;
        display: grid; gap: .45rem;
    }
    .mj-preview__list li {
        position: relative;
        padding-left: 1rem;
        font-size: .78rem; line-height: 1.4; color: rgba(255,255,255,.78);
    }
    .mj-preview__list li::before {
        content: '';
        position: absolute; left: 0; top: .45rem;
        width: .35rem; height: .35rem; border-radius: 999px;
        background: #93c5fd;
    }

    .mj-submit {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 1rem;
        padding: 1rem;
        display: grid; gap: .65rem;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .035);
    }
    .mj-submit__btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 100%;
        border: 0; border-radius: .7rem;
        padding: .85rem 1rem;
        background: var(--blue-strong); color: #fff;
        font-size: .95rem; font-weight: 800; cursor: pointer;
        box-shadow: 0 10px 22px rgba(37, 99, 235, .28);
        transition: filter .15s, transform .15s, opacity .15s;
    }
    .mj-submit__btn:hover:not(.is-disabled) { filter: brightness(1.05); transform: translateY(-1px); }
    .mj-submit__btn.is-disabled { opacity: .55; cursor: not-allowed; box-shadow: none; }
    .mj-submit__cancel {
        text-align: center; text-decoration: none;
        color: var(--muted); font-size: .82rem; font-weight: 600;
        padding: .35rem;
    }
    .mj-submit__cancel:hover { color: var(--ink); }
    .mj-submit__note {
        margin: 0; text-align: center;
        font-size: .72rem; color: var(--muted); line-height: 1.4;
    }
</style>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('memberJournalCreate', (cfg) => ({
            title: cfg.title || '',
            slug: cfg.slug || '',
            initials: cfg.initials || '',
            reviewType: cfg.reviewType || 'closed',
            showOptional: Boolean(cfg.showOptional),
            appHost: cfg.appHost || '',
            checkUrl: cfg.checkUrl,
            slugStatus: 'idle',
            slugMessage: '',
            slugTimer: null,
            slugTouched: Boolean(cfg.slug),
            initialsTouched: Boolean(cfg.initials),
            syncFromTitle() {
                if (! this.slugTouched || this.slug.trim() === '') {
                    this.slug = this.slugify(this.title);
                    this.queueSlugCheck();
                }
                if (! this.initialsTouched) {
                    this.initials = this.previewInitials();
                }
            },
            resetSlugFromTitle() {
                this.slugTouched = false;
                this.slug = this.slugify(this.title);
                this.queueSlugCheck();
            },
            slugify(value) {
                return String(value || '')
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '');
            },
            previewInitials() {
                const words = String(this.title || '').trim().split(/\s+/).filter(Boolean);
                if (! words.length) return '';
                return words.slice(0, 3).map((w) => w.charAt(0).toUpperCase()).join('');
            },
            normalizeSlug() {
                this.slugTouched = true;
                this.slug = this.slugify(this.slug);
            },
            queueSlugCheck() {
                clearTimeout(this.slugTimer);
                if (this.slug.trim() === '') {
                    this.slugStatus = 'idle';
                    this.slugMessage = '';
                    return;
                }
                this.slugStatus = 'checking';
                this.slugMessage = 'Checking availability…';
                this.slugTimer = setTimeout(() => this.checkSlug(), 350);
            },
            async checkSlug() {
                const params = new URLSearchParams({ slug: this.slug });
                try {
                    const response = await fetch(`${this.checkUrl}?${params.toString()}`, {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    if (! response.ok) {
                        this.slugStatus = 'idle';
                        this.slugMessage = '';
                        return;
                    }
                    const data = await response.json();
                    if (data.slug && data.slug !== this.slug) {
                        this.slug = data.slug;
                    }
                    this.slugStatus = data.available ? 'available' : 'taken';
                    this.slugMessage = data.message || '';
                } catch (e) {
                    this.slugStatus = 'idle';
                    this.slugMessage = '';
                }
            },
            slugUnavailable() {
                return this.slugStatus === 'taken';
            },
            init() {
                if (this.slug.trim() !== '') {
                    this.queueSlugCheck();
                }
            },
        }));
    });
</script>
@endsection
