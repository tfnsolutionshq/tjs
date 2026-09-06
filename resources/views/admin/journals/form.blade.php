@php
    $journal = $journal ?? new \App\Models\Journal;
    $isEdit = $journal->exists;
    $manageJournal = $manageJournal ?? null;
    $platformEditsLocked = $platformEditsLocked ?? false;
    $action = $manageJournal
        ? route('journal.manage.settings.update', $manageJournal)
        : ($isEdit ? route('admin.journals.update', $journal) : route('admin.journals.store'));
    $theme = $theme ?? \App\Support\JournalTheme::DEFAULTS;
    if ($isEdit) {
        $theme = old('theme', $journal->themeConfig());
    } else {
        $theme = old('theme', $theme);
    }
    $titleValue = old('title', $journal->title ?? '');
    $slugValue = old('slug', $journal->slug ?? '');
    $initialsValue = old('initials', $journal->initials ?? '');
    $suggestedInitials = \App\Models\Journal::initialsFromTitle($titleValue);
    $isActive = (string) old('is_active', $journal->exists ? ($journal->is_active ? '1' : '0') : '1') === '1';
    $isFeatured = (string) old('is_featured', $journal->exists ? ($journal->is_featured ? '1' : '0') : '0') === '1';
@endphp

<style>
    .jf { display: grid; gap: 1rem; width: 100%; max-width: none; }
    @media (min-width: 1100px) {
        .jf { grid-template-columns: minmax(0, 1fr) minmax(18rem, 22rem); align-items: start; gap: 1.25rem; }
    }

    .jf-main { display: grid; gap: 1rem; min-width: 0; }
    .jf-side { display: grid; gap: 1rem; }

    .jf-card {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 1.05rem;
        box-shadow: 0 8px 24px rgba(15,23,42,.035);
        overflow: visible;
    }
    .jf-card__head {
        padding: 1rem 1.15rem .15rem;
    }
    .jf-card__title {
        margin: 0; font-size: .95rem; font-weight: 800; color: var(--ink); letter-spacing: -.01em;
    }
    .jf-card__desc {
        margin: .3rem 0 0; font-size: .8rem; color: var(--muted); line-height: 1.45;
    }
    .jf-card__body { padding: 1rem 1.15rem 1.2rem; }

    .jf-grid { display: grid; gap: .9rem; }
    @media (min-width: 720px) {
        .jf-grid--2 { grid-template-columns: 1fr 1fr; }
        .jf-grid--3 { grid-template-columns: 1fr 1fr 1fr; }
        .jf-span-2 { grid-column: 1 / -1; }
    }

    .jf-field label,
    .jf-field .tjs-label-row {
        display: block; margin-bottom: .4rem;
        font-size: .78rem; font-weight: 700; color: #334155; letter-spacing: .01em;
    }
    .jf-req { color: #dc2626; font-weight: 800; margin-left: 0.05rem; }
    .jf-field .jf-hint {
        margin: .4rem 0 0; font-size: .72rem; color: var(--muted); line-height: 1.4;
    }
    .jf-field .jf-error {
        margin: .4rem 0 0; font-size: .75rem; font-weight: 600; color: #b91c1c;
    }
    .jf-input,
    .jf-textarea,
    .jf-select {
        width: 100%;
        border: 1px solid #e2e8f0;
        background: #fff;
        border-radius: .7rem;
        padding: .7rem .85rem;
        font: inherit;
        font-size: .9rem;
        color: var(--ink);
        outline: none;
        transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
    }
    .jf-textarea { resize: vertical; min-height: 7rem; line-height: 1.5; }
    .jf-input:hover,
    .jf-textarea:hover,
    .jf-select:hover { border-color: #cbd5e1; }
    .jf-input:focus,
    .jf-textarea:focus,
    .jf-select:focus {
        border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(37,99,235,.12);
        background: #fff;
    }
    .jf-input--mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .84rem; }

    .jf-slug {
        display: flex; align-items: center; gap: 0;
        border: 1px solid #e2e8f0; border-radius: .7rem; background: #f8fafc; overflow: hidden;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .jf-slug:focus-within {
        border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(37,99,235,.12);
        background: #fff;
    }
    .jf-slug__prefix {
        flex-shrink: 0; padding: .7rem 0 .7rem .85rem;
        font-size: .78rem; font-weight: 600; color: var(--muted); white-space: nowrap;
    }
    .jf-slug input {
        width: 100%; border: 0; outline: 0; background: transparent;
        padding: .7rem .85rem .7rem .35rem; font: inherit; font-size: .9rem; color: var(--ink);
    }
    .jf-slug.is-available { border-color: #86efac; background: #f0fdf4; }
    .jf-slug.is-taken { border-color: #fca5a5; background: #fef2f2; }
    .jf-slug.is-checking { border-color: #cbd5e1; }
    .jf-slug--locked {
        background: #f1f5f9;
        color: #64748b;
    }
    .jf-slug--locked input {
        color: #334155;
        cursor: not-allowed;
    }
    .jf-slug-status {
        margin: .4rem 0 0; font-size: .75rem; font-weight: 600; line-height: 1.4;
    }
    .jf-slug-status.is-available { color: #15803d; }
    .jf-slug-status.is-taken { color: #b91c1c; }
    .jf-slug-status.is-checking { color: var(--muted); font-weight: 500; }

    .jf-toggle {
        display: flex; align-items: flex-start; justify-content: space-between; gap: .85rem;
        padding: .85rem .9rem;
        border: 1px solid var(--line);
        border-radius: .85rem;
        background: #f8fafc;
        cursor: pointer;
        transition: border-color .15s ease, background .15s ease;
    }
    .jf-toggle:hover { border-color: #cbd5e1; background: #fff; }
    .jf-toggle__copy { min-width: 0; }
    .jf-toggle__label { display: block; font-size: .86rem; font-weight: 700; color: var(--ink); }
    .jf-toggle__hint { display: block; margin-top: .2rem; font-size: .74rem; color: var(--muted); line-height: 1.35; }
    .jf-switch {
        position: relative; width: 2.55rem; height: 1.45rem; flex-shrink: 0; margin-top: .1rem;
    }
    .jf-switch input {
        position: absolute; inset: 0; z-index: 2;
        width: 100%; height: 100%; margin: 0;
        opacity: 0; cursor: pointer;
    }
    .jf-switch__track {
        position: absolute; inset: 0; border-radius: 999px; background: #cbd5e1;
        transition: background .18s ease; pointer-events: none;
    }
    .jf-switch__thumb {
        position: absolute; top: .15rem; left: .15rem;
        width: 1.15rem; height: 1.15rem; border-radius: 999px; background: #fff;
        box-shadow: 0 1px 3px rgba(15,23,42,.2);
        transition: transform .18s ease; pointer-events: none;
    }
    .jf-switch input:checked + .jf-switch__track { background: #2563eb; }
    .jf-switch input:checked + .jf-switch__track .jf-switch__thumb { transform: translateX(1.1rem); }
    .jf-switch input:focus-visible + .jf-switch__track {
        box-shadow: 0 0 0 3px rgba(37,99,235,.18);
    }

    .jf-color {
        display: flex; align-items: center; gap: .55rem;
        border: 1px solid #e2e8f0; border-radius: .7rem; padding: .35rem .45rem .35rem .4rem;
        background: #fff;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .jf-color:focus-within {
        border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    }
    .jf-color input[type="color"] {
        width: 2rem; height: 2rem; padding: 0; border: 0; background: transparent;
        border-radius: .45rem; cursor: pointer; flex-shrink: 0;
    }
    .jf-color input[type="color"]::-webkit-color-swatch-wrapper { padding: 0; }
    .jf-color input[type="color"]::-webkit-color-swatch {
        border: 1px solid #e2e8f0; border-radius: .4rem;
    }
    .jf-color input[type="text"] {
        width: 100%; border: 0; outline: 0; background: transparent;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: .8rem; color: var(--ink); padding: .2rem .15rem;
    }

    .jf-theme-group {
        margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--line);
    }
    .jf-theme-group:first-of-type { margin-top: .35rem; padding-top: 0; border-top: 0; }
    .jf-theme-group__label {
        margin: 0 0 .75rem;
        font-size: .68rem; font-weight: 700; letter-spacing: .08em;
        text-transform: uppercase; color: var(--muted);
    }

    .jf-upload {
        border: 1px dashed #d0d7e2;
        border-radius: .9rem;
        padding: .95rem;
        background: #f8fafc;
        transition: border-color .15s ease, background .15s ease;
    }
    .jf-upload.has-preview {
        border-style: solid;
        border-color: #cbd5e1;
        background: #fff;
    }
    .jf-upload__preview {
        display: flex; align-items: center; gap: .75rem; margin-bottom: .75rem;
    }
    .jf-upload__preview--stack {
        display: block;
    }
    .jf-upload__preview img {
        border-radius: .65rem; border: 1px solid var(--line); background: #fff;
        object-fit: contain;
    }
    .jf-upload__preview--banner img {
        width: 100%; height: 5.5rem; object-fit: cover; display: block;
    }
    .jf-upload__preview--logo img {
        width: 3.5rem; height: 3.5rem; object-fit: contain; padding: .25rem;
    }
    .jf-upload__live {
        position: relative; margin-bottom: .75rem;
    }
    .jf-upload__live img {
        display: block; width: 100%; border-radius: .65rem;
        border: 1px solid var(--line); background: #fff;
    }
    .jf-upload__live--logo img {
        width: 4.5rem; height: 4.5rem; object-fit: contain; padding: .35rem;
    }
    .jf-upload__live--banner img {
        height: 7rem; object-fit: cover;
    }
    .jf-upload__live-meta {
        display: flex; align-items: center; justify-content: space-between; gap: .5rem;
        margin-top: .55rem;
    }
    .jf-upload__live-name {
        min-width: 0; font-size: .74rem; font-weight: 600; color: var(--muted);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .jf-upload__clear {
        flex-shrink: 0; border: 1px solid var(--line); background: #fff;
        border-radius: .5rem; padding: .28rem .55rem; cursor: pointer;
        font: inherit; font-size: .72rem; font-weight: 700; color: #475569;
    }
    .jf-upload__clear:hover { color: var(--ink); border-color: #cbd5e1; }
    .jf-file {
        display: block; width: 100%;
        font-size: .8rem; color: var(--muted);
    }
    .jf-file::file-selector-button {
        margin-right: .65rem;
        border: 1px solid var(--line);
        border-radius: .55rem;
        background: #fff;
        padding: .45rem .7rem;
        font: inherit;
        font-size: .78rem;
        font-weight: 700;
        color: var(--ink);
        cursor: pointer;
    }
    .jf-check {
        display: inline-flex; align-items: center; gap: .45rem;
        margin-top: .65rem; font-size: .76rem; font-weight: 600; color: #475569; cursor: pointer;
    }
    .jf-check input { border-radius: .3rem; border-color: #cbd5e1; color: #2563eb; }

    .jf-actions {
        display: flex; flex-wrap: wrap; align-items: center; gap: .55rem;
        padding: .95rem 1.15rem;
        border-top: 1px solid var(--line);
        background: #fafbfc;
        border-radius: 0 0 1.05rem 1.05rem;
    }
    .jf-actions .admin-btn { min-height: 2.5rem; }

    .jf-side .jf-card__body { display: grid; gap: .65rem; }
    .jf-meta {
        display: grid; gap: .55rem;
        padding: .15rem 0 .35rem;
    }
    .jf-meta__row {
        display: flex; align-items: baseline; justify-content: space-between; gap: .75rem;
        font-size: .78rem;
    }
    .jf-meta__row span { color: var(--muted); font-weight: 600; }
    .jf-meta__row strong {
        color: var(--ink); font-weight: 700; text-align: right;
        overflow-wrap: anywhere;
    }
    .jf-side-note {
        margin: 0; font-size: .74rem; color: var(--muted); line-height: 1.45;
    }

    @media (min-width: 1100px) {
        .jf-side { position: sticky; top: 1rem; }
    }

    .jf-lang { position: relative; z-index: 1; }
    .jf-lang:focus-within { z-index: 30; }
    .jf-lang > label,
    .jf-lang > .tjs-label-row {
        display: block; margin-bottom: .4rem;
        font-size: .78rem; font-weight: 700; color: #334155; letter-spacing: .01em;
    }
    .jf-lang__trigger {
        width: 100%; display: flex; align-items: center; gap: .7rem;
        border: 1px solid #e2e8f0; background: #fff; border-radius: .7rem;
        padding: .62rem .75rem; cursor: pointer; text-align: left;
        font: inherit; color: var(--ink);
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .jf-lang__trigger:hover { border-color: #cbd5e1; }
    .jf-lang__trigger:focus {
        outline: none; border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    }
    .jf-lang__flag {
        width: 1.45rem; height: 1.05rem; object-fit: cover;
        border-radius: .2rem; border: 1px solid rgba(15,23,42,.08);
        flex-shrink: 0; background: #f1f5f9;
    }
    .jf-lang__meta {
        display: flex; align-items: baseline; gap: .45rem; min-width: 0; flex: 1;
    }
    .jf-lang__name {
        font-size: .9rem; font-weight: 650; color: var(--ink);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .jf-lang__code {
        font-size: .72rem; font-weight: 700; letter-spacing: .04em;
        text-transform: uppercase; color: var(--muted); flex-shrink: 0;
    }
    .jf-lang__chevron {
        width: 1rem; height: 1rem; color: #94a3b8; flex-shrink: 0;
        transition: transform .15s ease;
    }
    .jf-lang__chevron.is-open { transform: rotate(180deg); }
    .jf-lang__menu {
        position: absolute; z-index: 50; left: 0; right: 0; top: calc(100% + .4rem);
        background: #fff; border: 1px solid var(--line); border-radius: .85rem;
        box-shadow: 0 18px 40px rgba(15,23,42,.12);
        overflow: visible;
    }
    .jf-lang__search {
        display: flex; align-items: center; gap: .5rem;
        padding: .65rem .75rem; border-bottom: 1px solid var(--line); background: #f8fafc;
    }
    .jf-lang__search svg { width: .95rem; height: .95rem; color: var(--muted); flex-shrink: 0; }
    .jf-lang__search input {
        width: 100%; border: 0; outline: 0; background: transparent;
        font: inherit; font-size: .86rem; color: var(--ink);
    }
    .jf-lang__list {
        list-style: none; margin: 0; padding: .35rem;
        max-height: 16rem; overflow: auto;
    }
    .jf-lang__option {
        width: 100%; display: flex; align-items: center; gap: .7rem;
        border: 0; background: transparent; border-radius: .6rem;
        padding: .55rem .6rem; cursor: pointer; text-align: left; font: inherit;
    }
    .jf-lang__option:hover,
    .jf-lang__option.is-hot { background: #f1f5f9; }
    .jf-lang__option.is-active { background: #eff6ff; }
    .jf-lang__check { width: .95rem; height: .95rem; color: #2563eb; flex-shrink: 0; margin-left: auto; }
    .jf-lang__empty {
        padding: .85rem .7rem; font-size: .8rem; color: var(--muted); text-align: center;
    }
    .jf-errors {
        background: #fef2f2; border: 1px solid #fecaca; border-radius: .9rem;
        padding: .9rem 1rem; color: #991b1b;
    }
    .jf-errors__title { margin: 0; font-size: .86rem; font-weight: 800; }
    .jf-errors ul { margin: .45rem 0 0; padding-left: 1.1rem; }
    .jf-errors li { font-size: .8rem; line-height: 1.45; }
</style>

<script>
    window.jfLanguagePicker = function (cfg) {
        return {
            languages: cfg.languages || [],
            value: cfg.value || 'en',
            selectedName: cfg.selectedName || 'English',
            selectedFlag: cfg.selectedFlag || '',
            open: false,
            query: '',
            hot: 0,
            get filtered() {
                const q = this.query.trim().toLowerCase();
                if (! q) return this.languages;
                return this.languages.filter((lang) =>
                    lang.name.toLowerCase().includes(q) || lang.code.toLowerCase().includes(q)
                );
            },
            toggle() {
                this.open = ! this.open;
                if (this.open) {
                    this.query = '';
                    this.hot = Math.max(0, this.filtered.findIndex((l) => l.code === this.value));
                    this.$nextTick(() => this.$refs.search?.focus());
                }
            },
            select(lang) {
                this.value = lang.code;
                this.selectedName = lang.name;
                this.selectedFlag = lang.flag_url;
                this.open = false;
                this.query = '';
            },
            pickFirst() {
                const first = this.filtered[this.hot] || this.filtered[0];
                if (first) this.select(first);
            },
            highlightNext() {
                if (! this.filtered.length) return;
                this.hot = (this.hot + 1) % this.filtered.length;
            },
            highlightPrev() {
                if (! this.filtered.length) return;
                this.hot = (this.hot - 1 + this.filtered.length) % this.filtered.length;
            },
        };
    };

    window.journalForm = function (config) {
        return {
            slug: config.slug || '',
            initials: config.initials || '',
            initialsTouched: config.initialsTouched || false,
            initialSlug: config.initialSlug || '',
            journalId: config.journalId || null,
            slugLocked: config.slugLocked || false,
            checkUrl: config.checkUrl,
            slugStatus: 'idle',
            slugMessage: '',
            slugTimer: null,
            init() {
                if (this.slugLocked || this.slug.trim() === '') {
                    return;
                }
                this.queueSlugCheck();
            },
            onTitleInput(event) {
                if (this.initialsTouched) {
                    return;
                }
                this.initials = this.suggestInitials(event.target.value || '');
            },
            onInitialsInput() {
                this.initialsTouched = true;
                this.initials = (this.initials || '')
                    .toUpperCase()
                    .replace(/[^A-Z0-9]/g, '')
                    .slice(0, 8);
            },
            suggestInitials(title) {
                const words = (title || '').trim().split(/\s+/).filter(Boolean);
                let value = words.slice(0, 3).map((word) => word.charAt(0).toUpperCase()).join('');
                if (value.length < 2) {
                    value = (title || '').replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 3);
                }
                if (! value) {
                    value = 'JN';
                }
                return value.slice(0, 8);
            },
            onSlugInput() {
                if (this.slugLocked) {
                    return;
                }
                this.slug = this.slug
                    .toLowerCase()
                    .replace(/[^a-z0-9-]+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '');
                this.queueSlugCheck();
            },
            queueSlugCheck() {
                clearTimeout(this.slugTimer);
                if (this.slug.trim() === '') {
                    this.slugStatus = 'idle';
                    this.slugMessage = '';
                    return;
                }
                if (this.slug === this.initialSlug) {
                    this.slugStatus = 'available';
                    this.slugMessage = 'Current slug for this journal.';
                    return;
                }
                this.slugStatus = 'checking';
                this.slugMessage = 'Checking availability…';
                this.slugTimer = setTimeout(() => this.checkSlug(), 350);
            },
            async checkSlug() {
                const params = new URLSearchParams({ slug: this.slug });
                if (this.journalId) {
                    params.set('except', String(this.journalId));
                }
                try {
                    const response = await fetch(`${this.checkUrl}?${params.toString()}`, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
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
                } catch (error) {
                    this.slugStatus = 'idle';
                    this.slugMessage = '';
                }
            },
            slugUnavailable() {
                return this.slugStatus === 'taken';
            },
        };
    };

    window.jfImagePreview = function () {
        return {
            preview: null,
            fileName: '',
            onFile(event) {
                const file = event.target.files?.[0];
                if (this.preview) {
                    URL.revokeObjectURL(this.preview);
                    this.preview = null;
                }
                if (! file || ! file.type.startsWith('image/')) {
                    this.fileName = '';
                    return;
                }
                this.fileName = file.name;
                this.preview = URL.createObjectURL(file);
            },
            clear(event) {
                if (this.preview) {
                    URL.revokeObjectURL(this.preview);
                }
                this.preview = null;
                this.fileName = '';
                const input = this.$refs.input;
                if (input) input.value = '';
                event?.preventDefault();
            },
        };
    };
</script>

<form
    method="POST"
    action="{{ $action }}"
    enctype="multipart/form-data"
    class="jf"
    x-data="journalForm({
        slug: @js($slugValue),
        initials: @js($initialsValue),
        initialsTouched: @js(filled(old('initials')) || ($isEdit && filled($journal->initials))),
        initialSlug: @js($slugValue),
        journalId: @js($isEdit ? $journal->id : null),
        slugLocked: @js($isEdit),
        checkUrl: @js(route('journals.check-slug')),
    })"
    @if($platformEditsLocked) style="opacity:.72;pointer-events:none" aria-disabled="true" @endif
>
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    @if ($errors->any())
        <div class="jf-errors" style="grid-column: 1 / -1">
            <p class="jf-errors__title">Couldn’t save this journal</p>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="jf-main">
        <section class="jf-card">
            <div class="jf-card__head">
                <h2 class="jf-card__title">Identity</h2>
                <p class="jf-card__desc">Core naming and public description for this journal.</p>
            </div>
            <div class="jf-card__body">
                <div class="jf-grid jf-grid--2">
                    <div class="jf-field jf-span-2">
                        <x-form-label for="title" field="journal.title" :required="true" reqClass="jf-req">Title</x-form-label>
                        <input id="title" name="title" type="text" required value="{{ $titleValue }}" class="jf-input" placeholder="Journal title" @input="onTitleInput($event)">
                        @error('title')<p class="jf-error">{{ $message }}</p>@enderror
        </div>

                    <div class="jf-field">
                        <x-form-label for="initials" field="journal.initials">Initials</x-form-label>
                        <input
                            id="initials"
                            name="initials"
                            type="text"
                            maxlength="8"
                            x-model="initials"
                            @input="onInitialsInput()"
                            value="{{ $initialsValue }}"
                            class="jf-input jf-input--mono"
                            placeholder="{{ $suggestedInitials }}"
                            autocomplete="off"
                        >
                        @error('initials')<p class="jf-error">{{ $message }}</p>@enderror
        </div>

                    <div class="jf-field">
                        @if($isEdit)
                            <x-form-label for="slug" field="journal.slug">Slug</x-form-label>
                        @else
                            <x-form-label for="slug" field="journal.slug" :required="true" reqClass="jf-req">Slug</x-form-label>
                        @endif
                        @if($isEdit)
                            <div class="jf-slug jf-slug--locked">
                                <span class="jf-slug__prefix">/j/</span>
                                <input
                                    id="slug"
                                    type="text"
                                    value="{{ $slugValue }}"
                                    readonly
                                    disabled
                                    aria-readonly="true"
                                >
                            </div>
                        @else
                            <div
                                class="jf-slug"
                                :class="{
                                    'is-checking': slugStatus === 'checking',
                                    'is-available': slugStatus === 'available',
                                    'is-taken': slugStatus === 'taken',
                                }"
                            >
                                <span class="jf-slug__prefix">/j/</span>
                                <input
                                    id="slug"
                                    name="slug"
                                    type="text"
                                    required
                                    x-model="slug"
                                    @input="onSlugInput()"
                                    value="{{ $slugValue }}"
                                    placeholder="journal-slug"
                                    autocomplete="off"
                                >
                            </div>
                            @error('slug')<p class="jf-error">{{ $message }}</p>@enderror
                            <p
                                class="jf-slug-status"
                                x-show="slugMessage"
                                x-cloak
                                x-text="slugMessage"
                                :class="{
                                    'is-checking': slugStatus === 'checking',
                                    'is-available': slugStatus === 'available',
                                    'is-taken': slugStatus === 'taken',
                                }"
                            ></p>
                        @endif
        </div>

                    <div class="jf-field">
                        <x-form-label for="subtitle" field="journal.subtitle">Subtitle</x-form-label>
                        <input id="subtitle" name="subtitle" type="text" value="{{ old('subtitle', $journal->subtitle ?? '') }}" class="jf-input" placeholder="Optional short line">
        </div>

                    <div class="jf-field jf-span-2">
                        <x-form-label for="description" field="journal.description">Description</x-form-label>
                        <x-rich-text
                            id="description"
                            name="description"
                            :value="old('description', $journal->description ?? '')"
                            placeholder="What this journal publishes…"
                            :rows="5"
                        />
                    </div>
                </div>
            </div>
        </section>

        <section class="jf-card">
            <div class="jf-card__head">
                <h2 class="jf-card__title">Publishing details</h2>
                <p class="jf-card__desc">Identifiers, publisher, and default licensing.</p>
            </div>
            <div class="jf-card__body">
                <div class="jf-grid jf-grid--2">
                    <div class="jf-field">
                        <x-form-label for="issn" field="journal.issn">ISSN</x-form-label>
                        <input id="issn" name="issn" type="text" value="{{ old('issn', $journal->issn ?? '') }}" class="jf-input jf-input--mono" placeholder="0000-0000">
                    </div>
                    <div class="jf-field">
                        <x-form-label for="eissn" field="journal.eissn">eISSN</x-form-label>
                        <input id="eissn" name="eissn" type="text" value="{{ old('eissn', $journal->eissn ?? '') }}" class="jf-input jf-input--mono" placeholder="0000-0000">
                    </div>
                    <div class="jf-field">
                        <x-form-label for="publisher" field="journal.publisher">Publisher</x-form-label>
                        <input id="publisher" name="publisher" type="text" value="{{ old('publisher', $journal->publisher ?? '') }}" class="jf-input">
                    </div>
                    <div class="jf-field">
                        <x-form-label for="default_license" field="journal.default_license">Default license</x-form-label>
                        <x-license-picker
                            name="default_license"
                            id="default_license"
                            :value="old('default_license', $journal->default_license ?? '')"
                            select-class="jf-input"
                        />
                    </div>
                    <div class="jf-field">
                        <x-language-picker
                            name="language"
                            id="language"
                            :value="old('language', $journal->language ?? config('tjs.default_language', 'en'))"
                        />
                    </div>
                    <div class="jf-field">
                        <x-form-label for="review_type" field="journal.review_type" :required="true">Review type</x-form-label>
                        @php $reviewType = old('review_type', $journal->review_type ?? \App\Support\ReviewType::CLOSED); @endphp
                        <select id="review_type" name="review_type" required class="jf-input">
                            @foreach(\App\Support\ReviewType::all() as $type)
                                <option value="{{ $type }}" @selected($reviewType === $type)>
                                    {{ \App\Support\ReviewType::label($type) }}
                                </option>
                            @endforeach
                        </select>
                        <p class="jf-hint">{{ \App\Support\ReviewType::description($reviewType) }}</p>
                        @error('review_type')<p class="jf-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </section>

        <section class="jf-card">
            <div class="jf-card__head">
                <h2 class="jf-card__title">Branding assets</h2>
                <p class="jf-card__desc">Logo and header image shown on the public journal site.</p>
            </div>
            <div class="jf-card__body">
                <div class="jf-grid jf-grid--2">
                    <div class="jf-field">
                        <x-form-label for="logo" field="journal.logo">Logo</x-form-label>
                        <div
                            class="jf-upload"
                            :class="{ 'has-preview': preview }"
                            x-data="jfImagePreview()"
                        >
                            <div class="jf-upload__live jf-upload__live--logo" x-show="preview" x-cloak>
                                <img :src="preview" alt="Selected logo preview">
                                <div class="jf-upload__live-meta">
                                    <span class="jf-upload__live-name" x-text="fileName"></span>
                                    <button type="button" class="jf-upload__clear" @click="clear($event)">Clear</button>
                                </div>
        </div>

                            @if($isEdit && $journal->logoUrl())
                                <div class="jf-upload__preview jf-upload__preview--logo" x-show="!preview">
                                    <img src="{{ $journal->logoUrl() }}" alt="Current logo">
        <div>
                                        <div style="font-size:.8rem;font-weight:700;color:var(--ink)">Current logo</div>
                                        <label class="jf-check">
                                            <input type="checkbox" name="remove_logo" value="1"> Remove
                                        </label>
                                    </div>
        </div>
                            @endif

                            <input
                                id="logo"
                                name="logo"
                                type="file"
                                accept="image/*"
                                class="jf-file"
                                x-ref="input"
                                @change="onFile($event)"
                            >
                            @error('logo')<p class="jf-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="jf-field">
                        <x-form-label for="header_image" field="journal.header_image">Header / banner</x-form-label>
                        <div
                            class="jf-upload"
                            :class="{ 'has-preview': preview }"
                            x-data="jfImagePreview()"
                        >
                            <div class="jf-upload__live jf-upload__live--banner" x-show="preview" x-cloak>
                                <img :src="preview" alt="Selected banner preview">
                                <div class="jf-upload__live-meta">
                                    <span class="jf-upload__live-name" x-text="fileName"></span>
                                    <button type="button" class="jf-upload__clear" @click="clear($event)">Clear</button>
                                </div>
        </div>

                            @if($isEdit && $journal->headerImageUrl())
                                <div class="jf-upload__preview jf-upload__preview--stack jf-upload__preview--banner" x-show="!preview">
                                    <img src="{{ $journal->headerImageUrl() }}" alt="Current header">
                                    <label class="jf-check" style="margin-top:.65rem;margin-bottom:.65rem">
                                        <input type="checkbox" name="remove_header_image" value="1"> Remove banner
                                    </label>
        </div>
                            @endif

                            <input
                                id="header_image"
                                name="header_image"
                                type="file"
                                accept="image/*"
                                class="jf-file"
                                x-ref="input"
                                @change="onFile($event)"
                            >
                            @error('header_image')<p class="jf-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
        </div>
        </section>

        <section class="jf-card">
            <div class="jf-card__head">
                <h2 class="jf-card__title">Website theme</h2>
                <p class="jf-card__desc">Colors and layout for this journal only. Keep contrast strong for readability.</p>
            </div>
            <div class="jf-card__body">
                <div class="jf-theme-group">
                    <p class="jf-theme-group__label">Brand</p>
                    <div class="jf-grid jf-grid--3">
                        @foreach(['primary' => 'Primary', 'accent' => 'Accent / buttons'] as $key => $label)
                            <div class="jf-field">
                                <label for="theme_{{ $key }}">{{ $label }}</label>
                                <div class="jf-color">
                                    <input id="theme_{{ $key }}_picker" type="color" value="{{ $theme[$key] ?? '#000000' }}"
                                        oninput="document.getElementById('theme_{{ $key }}').value = this.value">
                                    <input id="theme_{{ $key }}" name="theme[{{ $key }}]" type="text" value="{{ $theme[$key] ?? '' }}"
                                        oninput="document.getElementById('theme_{{ $key }}_picker').value = this.value">
                                </div>
                            </div>
                        @endforeach
                    </div>
        </div>

                <div class="jf-theme-group">
                    <p class="jf-theme-group__label">Chrome</p>
                    <div class="jf-grid jf-grid--3">
                        @foreach([
                            'nav_bg' => 'Nav background',
                            'nav_text' => 'Nav text',
                            'header_bg' => 'Header background',
                            'header_text' => 'Header text',
                            'page_bg' => 'Page background',
                            'surface' => 'Card surface',
                            'text' => 'Body text',
                            'muted' => 'Muted text',
                        ] as $key => $label)
                            <div class="jf-field">
                                <label for="theme_{{ $key }}">{{ $label }}</label>
                                <div class="jf-color">
                                    <input id="theme_{{ $key }}_picker" type="color" value="{{ $theme[$key] ?? '#000000' }}"
                                        oninput="document.getElementById('theme_{{ $key }}').value = this.value">
                                    <input id="theme_{{ $key }}" name="theme[{{ $key }}]" type="text" value="{{ $theme[$key] ?? '' }}"
                                        oninput="document.getElementById('theme_{{ $key }}_picker').value = this.value">
                                </div>
                            </div>
                        @endforeach
                    </div>
        </div>

                <div class="jf-theme-group">
                    <p class="jf-theme-group__label">Layout</p>
                    <div class="jf-grid jf-grid--2">
                        <div class="jf-field">
                            <x-form-label for="theme_header_size" help="Controls banner title size on the public journal home page.">Header size</x-form-label>
                            <select id="theme_header_size" name="theme[header_size]" class="jf-select">
                                @foreach(['small','medium','large'] as $size)
                                    <option value="{{ $size }}" @selected(($theme['header_size'] ?? 'large') === $size)>{{ ucfirst($size) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="jf-field">
                            <x-form-label for="theme_header_align" help="Horizontal alignment of the journal title on the banner.">Header alignment</x-form-label>
                            <select id="theme_header_align" name="theme[header_align]" class="jf-select">
                                <option value="left" @selected(($theme['header_align'] ?? 'left') === 'left')>Left</option>
                                <option value="center" @selected(($theme['header_align'] ?? 'left') === 'center')>Center</option>
                            </select>
                        </div>
                        <div class="jf-field">
                            <x-form-label for="theme_font_style" help="Serif or sans-serif typeface for the journal title on the public site.">Title font</x-form-label>
                            <select id="theme_font_style" name="theme[font_style]" class="jf-select">
                                <option value="serif" @selected(($theme['font_style'] ?? 'serif') === 'serif')>Serif</option>
                                <option value="sans" @selected(($theme['font_style'] ?? 'serif') === 'sans')>Sans</option>
                            </select>
                        </div>
                        <div class="jf-field">
                            <x-form-label for="theme_hero_overlay" field="journal.header_overlay">Image overlay</x-form-label>
                            <input id="theme_hero_overlay" name="theme[hero_overlay]" type="number" min="0" max="0.9" step="0.05"
                                value="{{ $theme['hero_overlay'] ?? 0.45 }}" class="jf-input" placeholder="0.45">
        </div>
    </div>

                    <label class="jf-toggle" style="margin-top:.9rem">
                        <span class="jf-toggle__copy">
                            <span class="jf-toggle__label">Show subtitle in header <x-field-helper :text="\App\Support\FormHelp::get('journal.show_subtitle_in_header')" /></span>
                            <span class="jf-toggle__hint">Display the subtitle under the journal title on the public site.</span>
                        </span>
                        <span class="jf-switch">
                            <input type="checkbox" name="theme[show_subtitle]" value="1" @checked(! empty($theme['show_subtitle']))>
                            <span class="jf-switch__track"><span class="jf-switch__thumb"></span></span>
                        </span>
        </label>
                </div>
            </div>
        </section>

        @unless($isEdit)
            <section
                class="jf-card"
                x-data="{
                    assign: {{ old('assign_admin', false) ? 'true' : 'false' }},
                    mode: '{{ old('admin_mode', 'create') }}'
                }"
            >
                <div class="jf-card__head">
                    <h2 class="jf-card__title">Journal admin</h2>
                    <p class="jf-card__desc">
                        Optionally assign someone who can log in and manage only this journal via its manage portal.
                    </p>
                </div>
                <div class="jf-card__body">
                    <input type="hidden" name="assign_admin" :value="assign ? '1' : '0'">
                    <label class="jf-toggle" style="margin-bottom:1rem">
                        <span class="jf-toggle__copy">
                            <span class="jf-toggle__label">Assign a journal admin now <x-field-helper :text="\App\Support\FormHelp::get('journal.assign_admin_now')" /></span>
                            <span class="jf-toggle__hint">You can also do this later from the journal edit page.</span>
                        </span>
                        <span class="jf-switch">
                            <input type="checkbox" value="1" x-model="assign">
                            <span class="jf-switch__track"><span class="jf-switch__thumb"></span></span>
                        </span>
                    </label>

                    <div x-show="assign" x-cloak style="display:grid;gap:.9rem">
                        <div class="jf-field">
                            <x-form-label field="journal.team_mode">How to assign</x-form-label>
                            <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                                <button type="button" class="admin-btn admin-btn-secondary" :style="mode === 'create' && 'border-color:#94a3b8'" @click="mode = 'create'">Create new account</button>
                                <button type="button" class="admin-btn admin-btn-secondary" :style="mode === 'existing' && 'border-color:#94a3b8'" @click="mode = 'existing'">Existing user</button>
                            </div>
                            <input type="hidden" name="admin_mode" :value="mode">
                        </div>

                        <div class="jf-field" x-show="mode === 'create'">
                            <x-form-label for="admin_name" field="journal.team_name">Full name</x-form-label>
                            <input id="admin_name" name="admin_name" type="text" class="jf-input" value="{{ old('admin_name') }}" :disabled="mode !== 'create'">
                            @error('admin_name')<p class="jf-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="jf-field">
                            <x-form-label for="admin_email" field="journal.team_email">Email</x-form-label>
                            <input id="admin_email" name="admin_email" type="email" class="jf-input" value="{{ old('admin_email') }}" placeholder="admin@example.com">
                            <p class="jf-hint" x-show="mode === 'existing'">Must already have a TJS account.</p>
                            @error('admin_email')<p class="jf-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="jf-grid jf-grid--2" x-show="mode === 'create'">
                            <div class="jf-field">
                                <x-form-label for="admin_password" field="user.password">Temporary password</x-form-label>
                                <x-password-input id="admin_password" name="admin_password" class="jf-input" autocomplete="new-password" x-bind:disabled="mode !== 'create'" />
                                @error('admin_password')<p class="jf-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="jf-field">
                                <x-form-label for="admin_password_confirmation" field="user.password_confirmation">Confirm password</x-form-label>
                                <x-password-input id="admin_password_confirmation" name="admin_password_confirmation" class="jf-input" autocomplete="new-password" x-bind:disabled="mode !== 'create'" />
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endunless
    </div>

    <aside class="jf-side">
        <section class="jf-card" id="journal-visibility">
            <div class="jf-card__head">
                <h2 class="jf-card__title">Visibility</h2>
                <p class="jf-card__desc">Control listing and homepage placement.</p>
            </div>
            <div class="jf-card__body">
                <input type="hidden" name="is_active" value="0">
                @unless(isset($manageJournal))
                    <input type="hidden" name="is_featured" value="0">
                @endunless
                <label class="jf-toggle">
                    <span class="jf-toggle__copy">
                        <span class="jf-toggle__label">Active <x-field-helper :text="\App\Support\FormHelp::get('journal.status')" /></span>
                    </span>
                    <span class="jf-switch">
                        <input type="checkbox" name="is_active" value="1" @checked($isActive)>
                        <span class="jf-switch__track"><span class="jf-switch__thumb"></span></span>
                    </span>
                </label>
                @if(isset($manageJournal))
                    <div style="margin-top:.85rem;padding:.85rem 1rem;border:1px solid var(--line);border-radius:.85rem;background:#f8fafc">
                        <p style="margin:0;font-size:.88rem;font-weight:700;color:var(--ink)">Homepage featured placement</p>
                        @if($journal->is_featured)
                            <p style="margin:.35rem 0 0;font-size:.8rem;color:#15803d;font-weight:600">This journal is featured on the platform homepage.</p>
                        @elseif($journal->featured_requested_at && $journal->featured_requested_at->greaterThan(now()->subDays(7)))
                            <p style="margin:.35rem 0 0;font-size:.8rem;color:var(--muted)">
                                Featured request sent {{ $journal->featured_requested_at->diffForHumans() }}. The platform team will respond soon.
                            </p>
                        @else
                            <p style="margin:.35rem 0 .65rem;font-size:.8rem;color:var(--muted);line-height:1.45">
                                Only platform administrators can mark a journal as featured. Send a request and we will email them.
                            </p>
                            @if($canMutate ?? true)
                                <button type="submit" form="featured-request-form" class="admin-btn admin-btn-secondary" style="padding:.45rem .75rem;font-size:.78rem">
                                    Request featured on homepage
                                </button>
                            @endif
                        @endif
                    </div>
                @else
                    <label class="jf-toggle">
                        <span class="jf-toggle__copy">
                            <span class="jf-toggle__label">Featured <x-field-helper :text="\App\Support\FormHelp::get('journal.featured')" /></span>
                            <span class="jf-toggle__hint">Highlight on the homepage.</span>
                        </span>
                        <span class="jf-switch">
                            <input type="checkbox" name="is_featured" value="1" @checked($isFeatured)>
                            <span class="jf-switch__track"><span class="jf-switch__thumb"></span></span>
                        </span>
                    </label>
                @endif
                @unless(isset($manageJournal))
                    <input type="hidden" name="personal_gateway_allowed" value="0">
                    <label class="jf-toggle">
                        <span class="jf-toggle__copy">
                            <span class="jf-toggle__label">Allow personal Paystack gateway</span>
                            <span class="jf-toggle__hint">When off, income falls back to platform split (if configured) or sales stop.</span>
                        </span>
                        <span class="jf-switch">
                            <input type="checkbox" name="personal_gateway_allowed" value="1" @checked(old('personal_gateway_allowed', $journal->exists ? ($journal->personal_gateway_allowed ? '1' : '0') : '1') === '1')>
                            <span class="jf-switch__track"><span class="jf-switch__thumb"></span></span>
                        </span>
                    </label>
                @endunless
            </div>
            @isset($manageJournal)
                <div class="jf-card__body" style="border-top:1px solid var(--line);padding-top:1rem">
                    <input type="hidden" name="allow_platform_admin_edits" value="0">
                    <label class="jf-toggle">
                        <span class="jf-toggle__copy">
                            <span class="jf-toggle__label">Allow platform admin edits <x-field-helper :text="\App\Support\FormHelp::get('journal.platform_admin_edits')" /></span>
                            <span class="jf-toggle__hint">When on, platform administrators can change this journal from the platform admin portal.</span>
                        </span>
                        <span class="jf-switch">
                            <input
                                type="checkbox"
                                name="allow_platform_admin_edits"
                                value="1"
                                @checked((string) old('allow_platform_admin_edits', $journal->allow_platform_admin_edits ? '1' : '0') === '1')
                            >
                            <span class="jf-switch__track"><span class="jf-switch__thumb"></span></span>
                        </span>
                    </label>
                </div>
            @endisset
            <div class="jf-actions">
                <button type="submit" class="admin-btn admin-btn-primary" style="flex:1" @disabled($platformEditsLocked) :disabled="! slugLocked && slugUnavailable()">
                    {{ $manageJournal ? 'Save settings' : ($isEdit ? 'Save changes' : 'Create journal') }}
                </button>
                <a href="{{ $manageJournal ? route('journal.manage.dashboard', $manageJournal) : route('admin.journals.index') }}" class="admin-btn admin-btn-secondary">Cancel</a>
            </div>
        </section>

        @if($isEdit)
            <section class="jf-card">
                <div class="jf-card__head">
                    <h2 class="jf-card__title">Quick links</h2>
                </div>
                <div class="jf-card__body">
                    <div class="jf-meta">
                        <div class="jf-meta__row">
                            <span>Public URL</span>
                            <strong>/j/<span x-text="slug || '…'"></span></strong>
                        </div>
                    </div>
                    <a href="{{ route('journals.show', $journal) }}" target="_blank" rel="noopener" class="admin-btn admin-btn-secondary" style="width:100%">
                        Preview site
                    </a>
                    <a href="{{ $manageJournal ? route('journal.manage.volumes.index', $manageJournal) : route('admin.volumes.index', $journal) }}" class="admin-btn admin-btn-secondary" style="width:100%">
                        Manage volumes
                    </a>
                    <p class="jf-side-note">Theme colors apply only to this journal’s public pages.</p>
    </div>
            </section>
        @endif
    </aside>
</form>

@isset($manageJournal)
    @if(! $journal->is_featured && (! $journal->featured_requested_at || ! $journal->featured_requested_at->greaterThan(now()->subDays(7))))
        <form id="featured-request-form" method="POST" action="{{ route('journal.manage.settings.featured-request', $manageJournal) }}" hidden>
            @csrf
        </form>
    @endif
@endisset
