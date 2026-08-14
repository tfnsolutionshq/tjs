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
    $isActive = (string) old('is_active', $journal->exists ? ($journal->is_active ? '1' : '0') : '1') === '1';
    $isFeatured = (string) old('is_featured', $journal->exists ? ($journal->is_featured ? '1' : '0') : '0') === '1';
@endphp

<style>
    .jf { display: grid; gap: 1rem; max-width: 72rem; }
    @media (min-width: 1100px) {
        .jf { grid-template-columns: minmax(0, 1fr) 17.5rem; align-items: start; gap: 1.15rem; }
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

    .jf-field label {
        display: block; margin-bottom: .4rem;
        font-size: .78rem; font-weight: 700; color: #334155; letter-spacing: .01em;
    }
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
    .jf-switch input { position: absolute; opacity: 0; width: 0; height: 0; }
    .jf-switch__track {
        position: absolute; inset: 0; border-radius: 999px; background: #cbd5e1;
        transition: background .18s ease;
    }
    .jf-switch__thumb {
        position: absolute; top: .15rem; left: .15rem;
        width: 1.15rem; height: 1.15rem; border-radius: 999px; background: #fff;
        box-shadow: 0 1px 3px rgba(15,23,42,.2);
        transition: transform .18s ease;
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
    .jf-lang > label {
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
    x-data="{ slug: @js($slugValue) }"
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
                        <label for="title">Title</label>
                        <input id="title" name="title" type="text" required value="{{ $titleValue }}" class="jf-input" placeholder="Journal title">
                        @error('title')<p class="jf-error">{{ $message }}</p>@enderror
        </div>

                    <div class="jf-field">
                        <label for="slug">Slug</label>
                        <div class="jf-slug">
                            <span class="jf-slug__prefix">/journals/</span>
                            <input id="slug" name="slug" type="text" required x-model="slug" value="{{ $slugValue }}" placeholder="journal-slug" autocomplete="off">
                        </div>
                        @error('slug')<p class="jf-error">{{ $message }}</p>@enderror
                        <p class="jf-hint">Used in public URLs. Letters, numbers, and dashes only.</p>
        </div>

                    <div class="jf-field">
                        <label for="subtitle">Subtitle</label>
                        <input id="subtitle" name="subtitle" type="text" value="{{ old('subtitle', $journal->subtitle ?? '') }}" class="jf-input" placeholder="Optional short line">
        </div>

                    <div class="jf-field jf-span-2">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4" class="jf-textarea" placeholder="What this journal publishes…">{{ old('description', $journal->description ?? '') }}</textarea>
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
                        <label for="issn">ISSN</label>
                        <input id="issn" name="issn" type="text" value="{{ old('issn', $journal->issn ?? '') }}" class="jf-input jf-input--mono" placeholder="0000-0000">
                    </div>
                    <div class="jf-field">
                        <label for="eissn">eISSN</label>
                        <input id="eissn" name="eissn" type="text" value="{{ old('eissn', $journal->eissn ?? '') }}" class="jf-input jf-input--mono" placeholder="0000-0000">
                    </div>
                    <div class="jf-field">
                        <label for="publisher">Publisher</label>
                        <input id="publisher" name="publisher" type="text" value="{{ old('publisher', $journal->publisher ?? '') }}" class="jf-input">
                    </div>
                    <div class="jf-field">
                        <label for="default_license">Default license</label>
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
                        <label for="logo">Logo</label>
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
                            <p class="jf-hint">PNG, JPG, or WebP up to 10MB.</p>
                            @error('logo')<p class="jf-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="jf-field">
                        <label for="header_image">Header / banner</label>
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
                            <p class="jf-hint">Prefer photos without overlaid text; the title renders on top. Max 15MB.</p>
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
                            <label for="theme_header_size">Header size</label>
                            <select id="theme_header_size" name="theme[header_size]" class="jf-select">
                                @foreach(['small','medium','large'] as $size)
                                    <option value="{{ $size }}" @selected(($theme['header_size'] ?? 'large') === $size)>{{ ucfirst($size) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="jf-field">
                            <label for="theme_header_align">Header alignment</label>
                            <select id="theme_header_align" name="theme[header_align]" class="jf-select">
                                <option value="left" @selected(($theme['header_align'] ?? 'left') === 'left')>Left</option>
                                <option value="center" @selected(($theme['header_align'] ?? 'left') === 'center')>Center</option>
                            </select>
                        </div>
                        <div class="jf-field">
                            <label for="theme_font_style">Title font</label>
                            <select id="theme_font_style" name="theme[font_style]" class="jf-select">
                                <option value="serif" @selected(($theme['font_style'] ?? 'serif') === 'serif')>Serif</option>
                                <option value="sans" @selected(($theme['font_style'] ?? 'serif') === 'sans')>Sans</option>
                            </select>
                        </div>
                        <div class="jf-field">
                            <label for="theme_hero_overlay">Image overlay</label>
                            <input id="theme_hero_overlay" name="theme[hero_overlay]" type="number" min="0" max="0.9" step="0.05"
                                value="{{ $theme['hero_overlay'] ?? 0.45 }}" class="jf-input" placeholder="0.45">
                            <p class="jf-hint">0 = none, 0.9 = strongest darken.</p>
        </div>
    </div>

                    <label class="jf-toggle" style="margin-top:.9rem">
                        <span class="jf-toggle__copy">
                            <span class="jf-toggle__label">Show subtitle in header</span>
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
                            <span class="jf-toggle__label">Assign a journal admin now</span>
                            <span class="jf-toggle__hint">You can also do this later from the journal edit page.</span>
                        </span>
                        <span class="jf-switch">
                            <input type="checkbox" value="1" x-model="assign">
                            <span class="jf-switch__track"><span class="jf-switch__thumb"></span></span>
                        </span>
                    </label>

                    <div x-show="assign" x-cloak style="display:grid;gap:.9rem">
                        <div class="jf-field">
                            <label>How to assign</label>
                            <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                                <button type="button" class="admin-btn admin-btn-secondary" :style="mode === 'create' && 'border-color:#94a3b8'" @click="mode = 'create'">Create new account</button>
                                <button type="button" class="admin-btn admin-btn-secondary" :style="mode === 'existing' && 'border-color:#94a3b8'" @click="mode = 'existing'">Existing user</button>
                            </div>
                            <input type="hidden" name="admin_mode" :value="mode">
                        </div>

                        <div class="jf-field" x-show="mode === 'create'">
                            <label for="admin_name">Full name</label>
                            <input id="admin_name" name="admin_name" type="text" class="jf-input" value="{{ old('admin_name') }}" :disabled="mode !== 'create'">
                            @error('admin_name')<p class="jf-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="jf-field">
                            <label for="admin_email">Email</label>
                            <input id="admin_email" name="admin_email" type="email" class="jf-input" value="{{ old('admin_email') }}" placeholder="admin@example.com">
                            <p class="jf-hint" x-show="mode === 'existing'">Must already have a TJS account.</p>
                            @error('admin_email')<p class="jf-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="jf-grid jf-grid--2" x-show="mode === 'create'">
                            <div class="jf-field">
                                <label for="admin_password">Temporary password</label>
                                <input id="admin_password" name="admin_password" type="password" class="jf-input" autocomplete="new-password" :disabled="mode !== 'create'">
                                @error('admin_password')<p class="jf-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="jf-field">
                                <label for="admin_password_confirmation">Confirm password</label>
                                <input id="admin_password_confirmation" name="admin_password_confirmation" type="password" class="jf-input" autocomplete="new-password" :disabled="mode !== 'create'">
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endunless
    </div>

    <aside class="jf-side">
        <section class="jf-card">
            <div class="jf-card__head">
                <h2 class="jf-card__title">Visibility</h2>
                <p class="jf-card__desc">Control listing and homepage placement.</p>
            </div>
            <div class="jf-card__body">
                <input type="hidden" name="is_active" value="0">
                <input type="hidden" name="is_featured" value="0">
                <label class="jf-toggle">
                    <span class="jf-toggle__copy">
                        <span class="jf-toggle__label">Active</span>
                        <span class="jf-toggle__hint">Visible in public browse.</span>
                    </span>
                    <span class="jf-switch">
                        <input type="checkbox" name="is_active" value="1" @checked($isActive)>
                        <span class="jf-switch__track"><span class="jf-switch__thumb"></span></span>
                    </span>
                </label>
                <label class="jf-toggle">
                    <span class="jf-toggle__copy">
                        <span class="jf-toggle__label">Featured</span>
                        <span class="jf-toggle__hint">Highlight on the homepage.</span>
                    </span>
                    <span class="jf-switch">
                        <input type="checkbox" name="is_featured" value="1" @checked($isFeatured)>
                        <span class="jf-switch__track"><span class="jf-switch__thumb"></span></span>
                    </span>
                </label>
            </div>
            @isset($manageJournal)
                <div class="jf-card__body" style="border-top:1px solid var(--line);padding-top:1rem">
                    <input type="hidden" name="allow_platform_admin_edits" value="0">
                    <label class="jf-toggle">
                        <span class="jf-toggle__copy">
                            <span class="jf-toggle__label">Allow platform admin edits</span>
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
                <button type="submit" class="admin-btn admin-btn-primary" style="flex:1" @disabled($platformEditsLocked)>
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
                            <strong>/journals/<span x-text="slug || '…'"></span></strong>
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
