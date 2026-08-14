@php
    $isEdit = isset($article);
    $manageJournal = $manageJournal ?? null;
    if ($manageJournal) {
        $action = $isEdit
            ? route('journal.manage.articles.update', [$manageJournal, $article])
            : route('journal.manage.articles.store', $manageJournal);
        $extractUrl = route('journal.manage.articles.extract', $manageJournal);
        $quickVolumeUrl = route('journal.manage.articles.quick-volume', $manageJournal);
        $quickIssueUrl = route('journal.manage.articles.quick-issue', $manageJournal);
        $quickCategoryUrl = route('journal.manage.articles.quick-category', $manageJournal);
        $cancelUrl = route('journal.manage.articles.index', $manageJournal);
        $defaultJournalId = (string) $manageJournal->id;
    } else {
        $action = $isEdit ? route('admin.articles.update', $article) : route('admin.articles.store');
        $extractUrl = route('admin.articles.extract');
        $quickVolumeUrl = route('admin.articles.quick-volume');
        $quickIssueUrl = route('admin.articles.quick-issue');
        $quickCategoryUrl = route('admin.articles.quick-category');
        $cancelUrl = route('admin.articles.index');
        $defaultJournalId = (string) old('journal_id', $article->journal_id ?? '');
    }
    $ocrAvailable = $ocrAvailable ?? false;
    $visibility = old('visibility', $article->visibility ?? 'open');
    $status = old('status', $article->status ?? 'draft');
    $categories = $categories ?? collect();
    $selectedCategoryIds = old('category_ids', $selectedCategoryIds ?? []);
    $selectedCategoryIds = array_map('strval', (array) $selectedCategoryIds);

    $authorsPayload = old('authors', $authorsPayload ?? []);
    if (! is_array($authorsPayload)) {
        $authorsPayload = [];
    }
    // Normalize old() flat authors[i][field] into Alpine field rows when validation fails.
    $authorsForAlpine = [];
    foreach ($authorsPayload as $row) {
        if (! is_array($row)) {
            continue;
        }
        if (isset($row['fields']) && is_array($row['fields'])) {
            $authorsForAlpine[] = [
                'fields' => array_values(array_filter(array_map(function ($f) {
                    if (! is_array($f)) {
                        return null;
                    }
                    $key = (string) ($f['key'] ?? '');
                    $value = trim((string) ($f['value'] ?? ''));
                    if ($key === '' || $value === '') {
                        return null;
                    }

                    return ['key' => $key, 'value' => $value];
                }, $row['fields']))),
                'is_corresponding' => filter_var($row['is_corresponding'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];
            continue;
        }
        $fields = [];
        foreach (['surname', 'given_names', 'middle_name', 'email', 'affiliation', 'nationality', 'orcid', 'role'] as $key) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '') {
                $fields[] = ['key' => $key, 'value' => $value];
            }
        }
        $authorsForAlpine[] = [
            'fields' => $fields,
            'is_corresponding' => filter_var($row['is_corresponding'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
    }

    $licenseOptions = \App\Support\Licenses::options();
    $licenseValue = \App\Support\Licenses::normalize(old('license', $article->license ?? '')) ?? '';
@endphp

<style>
    .af { display: grid; gap: 1rem; max-width: 72rem; }
    @media (min-width: 1100px) {
        .af { grid-template-columns: minmax(0, 1fr) 17.5rem; align-items: start; gap: 1.15rem; }
    }
    .af-main { display: grid; gap: 1rem; min-width: 0; }
    .af-side { display: grid; gap: 1rem; }
    @media (min-width: 1100px) {
        .af-side { position: sticky; top: 1rem; }
    }

    .af-card {
        background: #fff; border: 1px solid var(--line); border-radius: 1.05rem;
        box-shadow: 0 8px 24px rgba(15,23,42,.035); overflow: hidden;
    }
    .af-card__head { padding: 1rem 1.15rem .15rem; }
    .af-card__title { margin: 0; font-size: .95rem; font-weight: 800; color: var(--ink); letter-spacing: -.01em; }
    .af-card__desc { margin: .3rem 0 0; font-size: .8rem; color: var(--muted); line-height: 1.45; }
    .af-card__body { padding: 1rem 1.15rem 1.2rem; }

    .af-mode {
        display: grid; grid-template-columns: 1fr 1fr; gap: .55rem;
        background: #f8fafc; border: 1px solid var(--line); border-radius: .95rem; padding: .4rem;
    }
    .af-mode__btn {
        appearance: none; border: 0; background: transparent; cursor: pointer;
        border-radius: .75rem; padding: .7rem .85rem; text-align: left;
        font: inherit; color: var(--muted); transition: background .15s ease, color .15s ease, box-shadow .15s ease;
    }
    .af-mode__btn strong { display: block; font-size: .84rem; font-weight: 800; color: inherit; }
    .af-mode__btn span { display: block; margin-top: .15rem; font-size: .72rem; line-height: 1.35; }
    .af-mode__btn.is-active {
        background: #fff; color: var(--ink);
        box-shadow: 0 1px 2px rgba(15,23,42,.06), 0 0 0 1px #e2e8f0;
    }

    .af-drop {
        border: 1.5px dashed #cbd5e1; border-radius: .95rem; background: #f8fafc;
        padding: 1.25rem 1rem; text-align: center; cursor: pointer;
        transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
    }
    .af-drop:hover, .af-drop.is-drag {
        border-color: #93c5fd; background: #eff6ff;
        box-shadow: 0 0 0 3px rgba(37,99,235,.1);
    }
    .af-drop__icon {
        width: 2.4rem; height: 2.4rem; margin: 0 auto .65rem; border-radius: .75rem;
        display: grid; place-items: center; background: #dbeafe; color: #1d4ed8;
    }
    .af-drop__icon svg { width: 1.15rem; height: 1.15rem; }
    .af-drop__title { margin: 0; font-size: .9rem; font-weight: 800; color: var(--ink); }
    .af-drop__hint { margin: .35rem 0 0; font-size: .76rem; color: var(--muted); line-height: 1.4; }
    .af-drop__file {
        margin: .7rem 0 0; display: none; align-items: center; justify-content: center; gap: .45rem;
        font-size: .78rem; font-weight: 700; color: #1d4ed8;
    }
    .af-drop__file.is-on { display: inline-flex; }

    .af-extract-actions { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .85rem; align-items: center; }
    .af-extract-status { font-size: .78rem; color: var(--muted); }
    .af-extract-status.is-busy { color: #1d4ed8; font-weight: 700; }
    .af-extract-status.is-ok { color: #047857; font-weight: 700; }
    .af-extract-status.is-err { color: #b91c1c; font-weight: 700; }

    .af-banner {
        border-radius: .85rem; padding: .75rem .9rem; font-size: .78rem; line-height: 1.45;
        border: 1px solid #bfdbfe; background: #eff6ff; color: #1e3a8a;
    }
    .af-banner--warn { border-color: #fde68a; background: #fffbeb; color: #92400e; }
    .af-banner ul { margin: .35rem 0 0; padding-left: 1.1rem; }

    .af-preview {
        margin-top: .85rem; border: 1px solid #dbe3ef; border-radius: 1rem;
        background: linear-gradient(180deg, #f8fbff 0%, #fff 42%);
        overflow: hidden;
    }
    .af-preview__head {
        display: flex; align-items: center; justify-content: space-between; gap: .75rem;
        padding: .8rem 1rem; border-bottom: 1px solid #e8eef7;
    }
    .af-preview__label {
        margin: 0; font-size: .72rem; font-weight: 800; letter-spacing: .08em;
        text-transform: uppercase; color: #64748b;
    }
    .af-preview__method {
        font-size: .68rem; font-weight: 700; color: #2563eb;
        background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 999px;
        padding: .2rem .55rem;
    }
    .af-preview__body { padding: 1rem 1.05rem 1.15rem; display: grid; gap: .85rem; }
    .af-preview__journal {
        margin: 0; font-size: .78rem; font-weight: 700; color: #475569; line-height: 1.4;
    }
    .af-preview__meta {
        display: flex; flex-wrap: wrap; gap: .35rem .55rem; margin-top: .4rem;
    }
    .af-preview__chip {
        display: inline-flex; align-items: center; gap: .3rem;
        font-size: .7rem; font-weight: 700; color: #334155;
        background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 999px;
        padding: .22rem .55rem;
    }
    .af-preview__chip a { color: #1d4ed8; text-decoration: none; }
    .af-preview__chip a:hover { text-decoration: underline; }
    .af-preview__title {
        margin: 0; font-size: 1.05rem; font-weight: 800; letter-spacing: -.02em;
        color: var(--ink); line-height: 1.35;
    }
    .af-preview__authors {
        margin: 0; font-size: .86rem; font-weight: 650; color: #1e293b; line-height: 1.45;
    }
    .af-preview__aff {
        margin: .25rem 0 0; font-size: .76rem; color: var(--muted); line-height: 1.45;
    }
    .af-preview__section {
        padding-top: .75rem; border-top: 1px solid #eef2f7;
    }
    .af-preview__section-title {
        margin: 0 0 .45rem; font-size: .68rem; font-weight: 800;
        letter-spacing: .07em; text-transform: uppercase; color: #64748b;
    }
    .af-preview__history {
        display: grid; gap: .35rem;
    }
    @media (min-width: 640px) {
        .af-preview__history { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .5rem; }
    }
    .af-preview__hist-item {
        background: #f8fafc; border: 1px solid #eef2f7; border-radius: .65rem; padding: .55rem .65rem;
    }
    .af-preview__hist-item span {
        display: block; font-size: .65rem; font-weight: 800; letter-spacing: .06em;
        text-transform: uppercase; color: #94a3b8;
    }
    .af-preview__hist-item strong {
        display: block; margin-top: .15rem; font-size: .8rem; font-weight: 700; color: #0f172a;
    }
    .af-preview__abstract {
        margin: 0; font-size: .82rem; color: #334155; line-height: 1.55;
    }
    .af-preview__keywords {
        margin: .45rem 0 0; font-size: .76rem; color: #475569; line-height: 1.45;
    }
    .af-preview__keywords strong { color: #0f172a; }

    .af-grid { display: grid; gap: .9rem; }
    @media (min-width: 720px) {
        .af-grid--2 { grid-template-columns: 1fr 1fr; }
        .af-span-2 { grid-column: 1 / -1; }
    }

    .af-field label {
        display: block; margin-bottom: .4rem;
        font-size: .78rem; font-weight: 700; color: #334155; letter-spacing: .01em;
    }
    .af-req { color: #dc2626; font-weight: 800; margin-left: .15rem; }
    .af-field .af-hint { margin: .4rem 0 0; font-size: .72rem; color: var(--muted); line-height: 1.4; }
    .af-field .af-error { margin: .4rem 0 0; font-size: .75rem; font-weight: 600; color: #b91c1c; }

    .af-authors { display: grid; gap: .75rem; }
    .af-author {
        border: 1px solid #e2e8f0; border-radius: .95rem; background: #f8fafc;
        padding: .85rem .9rem; display: grid; gap: .7rem;
    }
    .af-author__top {
        display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem;
    }
    .af-author__who { min-width: 0; }
    .af-author__badge {
        display: inline-flex; align-items: center; gap: .35rem;
        font-size: .72rem; font-weight: 800; color: #1d4ed8; letter-spacing: .02em;
        text-transform: uppercase;
    }
    .af-author__preview {
        margin: .25rem 0 0; font-size: .92rem; font-weight: 800; color: var(--ink);
        letter-spacing: -.01em; word-break: break-word;
    }
    .af-author__preview.is-empty { color: #94a3b8; font-weight: 600; font-size: .84rem; }
    .af-author__remove {
        appearance: none; border: 0; background: transparent; color: #94a3b8;
        cursor: pointer; font: inherit; font-size: .75rem; font-weight: 700;
        padding: .25rem .4rem; border-radius: .45rem; flex-shrink: 0;
    }
    .af-author__remove:hover { color: #b91c1c; background: #fef2f2; }
    .af-author__rows { display: grid; gap: .4rem; }
    .af-author__row {
        display: grid; grid-template-columns: 7.5rem minmax(0, 1fr) auto; gap: .4rem; align-items: center;
        background: #fff; border: 1px solid #e2e8f0; border-radius: .65rem; padding: .35rem .4rem .35rem .55rem;
    }
    @media (max-width: 560px) {
        .af-author__row { grid-template-columns: 1fr auto; }
        .af-author__row-key { grid-column: 1 / -1; }
    }
    .af-author__row-key {
        font-size: .72rem; font-weight: 800; color: #475569; text-transform: capitalize;
    }
    .af-author__row-val {
        border: 0; background: transparent; font: inherit; font-size: .84rem; color: var(--ink);
        width: 100%; min-width: 0; padding: .25rem .15rem; outline: none;
    }
    .af-author__row-del {
        appearance: none; border: 0; background: transparent; color: #94a3b8;
        cursor: pointer; width: 1.7rem; height: 1.7rem; border-radius: .45rem;
        display: grid; place-items: center; font-size: 1rem; line-height: 1;
    }
    .af-author__row-del:hover { color: #b91c1c; background: #fef2f2; }
    .af-author__add {
        display: grid; grid-template-columns: minmax(8rem, 11rem) minmax(0, 1fr) auto;
        gap: .45rem; align-items: center;
    }
    @media (max-width: 560px) {
        .af-author__add { grid-template-columns: 1fr; }
    }
    .af-author__add .af-select,
    .af-author__add .af-input { margin: 0; }
    .af-author__add-actions {
        display: flex; align-items: center; gap: .35rem; flex-wrap: wrap;
    }
    .af-author__add-btn {
        appearance: none; border: 1px solid #cbd5e1; background: #fff; color: #334155;
        border-radius: .65rem; padding: .55rem .85rem; font: inherit; font-size: .78rem;
        font-weight: 800; cursor: pointer; white-space: nowrap;
    }
    .af-author__add-btn:hover { border-color: #2563eb; color: #1d4ed8; background: #eff6ff; }
    .af-author__add-btn:disabled { opacity: .45; cursor: not-allowed; }
    .af-author__skip {
        appearance: none; border: 0; background: transparent; color: #64748b;
        font: inherit; font-size: .75rem; font-weight: 700; cursor: pointer;
        padding: .45rem .4rem; border-radius: .45rem; white-space: nowrap;
    }
    .af-author__skip:hover { color: #1d4ed8; background: #eff6ff; }
    .af-author__row-val--select {
        cursor: pointer; appearance: auto;
        padding: .2rem .15rem;
    }
    .af-author__meta {
        display: flex; flex-wrap: wrap; align-items: center; gap: .75rem;
        padding-top: .15rem; border-top: 1px dashed #e2e8f0;
    }
    .af-author__check {
        display: inline-flex; align-items: center; gap: .4rem;
        font-size: .78rem; font-weight: 700; color: #475569; cursor: pointer; user-select: none;
    }
    .af-author__check input { accent-color: #2563eb; }
    .af-authors__empty {
        border: 1.5px dashed #cbd5e1; border-radius: .95rem; background: #fff;
        padding: 1.1rem 1rem; text-align: center; color: var(--muted); font-size: .82rem; line-height: 1.45;
    }
    .af-authors__actions { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }

    .af-cats {
        display: flex; flex-wrap: wrap; gap: .45rem;
    }
    .af-cat {
        display: inline-flex; align-items: center; gap: .35rem;
        border: 1px solid #e2e8f0; background: #fff; border-radius: 999px;
        padding: .42rem .75rem; font-size: .78rem; font-weight: 700; color: #64748b;
        cursor: pointer; user-select: none;
    }
    .af-cat input { position: absolute; opacity: 0; pointer-events: none; }
    .af-cat.is-on {
        border-color: #2563eb; color: #1d4ed8; background: #eff6ff;
        box-shadow: 0 0 0 2px rgba(37,99,235,.12);
    }
    .af-quick__head {
        display: flex; flex-wrap: wrap; gap: .45rem; align-items: center; justify-content: space-between;
        margin-bottom: .65rem;
    }
    .af-quick__title { margin: 0; font-size: .78rem; font-weight: 800; color: var(--ink); }
    .af-quick__msg { margin: .55rem 0 0; font-size: .74rem; font-weight: 650; }
    .af-quick__msg.is-ok { color: #047857; }
    .af-quick__msg.is-err { color: #b91c1c; }

    .af-input, .af-textarea, .af-select {
        width: 100%; border: 1px solid #e2e8f0; background: #fff; border-radius: .7rem;
        padding: .7rem .85rem; font: inherit; font-size: .9rem; color: var(--ink); outline: none;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .af-textarea { resize: vertical; min-height: 7rem; line-height: 1.5; }
    .af-textarea--mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .82rem; }
    .af-input:hover, .af-textarea:hover, .af-select:hover { border-color: #cbd5e1; }
    .af-input:focus, .af-textarea:focus, .af-select:focus {
        border-color: #93c5fd; box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    }
    .af-input.is-filled, .af-textarea.is-filled {
        border-color: #86efac; background: #f0fdf4;
    }

    .af-file {
        width: 100%; border: 1px solid #e2e8f0; border-radius: .7rem; background: #fff;
        padding: .55rem .7rem; font: inherit; font-size: .82rem; color: var(--muted);
    }

    .af-toggle {
        display: flex; align-items: center; justify-content: space-between; gap: .75rem;
        padding: .75rem 0; border-bottom: 1px solid #f1f5f9;
    }
    .af-toggle:last-child { border-bottom: 0; padding-bottom: 0; }
    .af-toggle__label { font-size: .84rem; font-weight: 750; color: var(--ink); }
    .af-toggle__hint { margin: .15rem 0 0; font-size: .72rem; color: var(--muted); }
    .af-switch {
        position: relative; width: 2.55rem; height: 1.45rem; border-radius: 999px;
        background: #cbd5e1; border: 0; cursor: pointer; flex-shrink: 0; padding: 0;
        transition: background .15s ease;
    }
    .af-switch.is-on { background: #2563eb; }
    .af-switch::after {
        content: ''; position: absolute; top: .15rem; left: .15rem;
        width: 1.15rem; height: 1.15rem; border-radius: 999px; background: #fff;
        transition: transform .15s ease; box-shadow: 0 1px 2px rgba(15,23,42,.2);
    }
    .af-switch.is-on::after { transform: translateX(1.1rem); }

    .af-actions {
        display: flex; flex-wrap: wrap; gap: .55rem; align-items: center;
        padding-top: .25rem;
    }
</style>

@if ($errors->any())
    <div class="af-banner af-banner--warn" style="margin-bottom:1rem;max-width:72rem">
        Please fix the highlighted fields and try again.
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form
    method="POST"
    action="{{ $action }}"
    enctype="multipart/form-data"
    class="af"
    x-data="articleForm({
        extractUrl: @js($extractUrl),
        quickVolumeUrl: @js($quickVolumeUrl),
        quickIssueUrl: @js($quickIssueUrl),
        quickCategoryUrl: @js($quickCategoryUrl),
        csrf: @js(csrf_token()),
        isEdit: @js($isEdit),
        ocrAvailable: @js($ocrAvailable),
        initialMode: @js(old('_entry_mode', $isEdit ? 'manual' : 'manual')),
        visibility: @js($visibility),
        status: @js($status),
        journalId: @js($defaultJournalId),
        issueId: @js((string) old('issue_id', $article->issue_id ?? '')),
        catalog: @js($catalog ?? ['volumes' => [], 'issues' => []]),
        categories: @js($categories->map(fn ($c) => ['id' => (string) $c->id, 'name' => $c->name, 'slug' => $c->slug])->values()),
        selectedCategoryIds: @js($selectedCategoryIds),
        initialAuthors: @js($authorsForAlpine),
        licenseOptions: @js($licenseOptions),
        license: @js($licenseValue),
    })"
>
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif
    <input type="hidden" name="_entry_mode" :value="mode">

    <div class="af-main">
        @unless($isEdit)
            <div class="af-card">
                <div class="af-card__head">
                    <h2 class="af-card__title">How do you want to start?</h2>
                    <p class="af-card__desc">Fill details yourself, or upload a document and auto-fill what we can detect.</p>
                </div>
                <div class="af-card__body">
                    <div class="af-mode" role="tablist">
                        <button type="button" class="af-mode__btn" :class="mode === 'manual' && 'is-active'" @click="mode = 'manual'">
                            <strong>Fill details</strong>
                            <span>Enter metadata manually, attach a galley when ready.</span>
                        </button>
                        <button type="button" class="af-mode__btn" :class="mode === 'extract' && 'is-active'" @click="mode = 'extract'">
                            <strong>Upload &amp; extract</strong>
                            <span>Pull title, abstract, keywords, DOI from PDF/DOCX{{ $ocrAvailable ? ' (OCR on)' : '' }}.</span>
                        </button>
                    </div>
                </div>
            </div>
        @endunless

        @unless($isEdit)
        <div class="af-card" x-show="mode === 'extract'" x-cloak>
            <div class="af-card__head">
                <h2 class="af-card__title">Document extraction</h2>
                <p class="af-card__desc">
                    Best with text-based PDFs and DOCX.
                    @if($ocrAvailable)
                        Tesseract OCR is available for scanned images and sparse PDFs.
                    @else
                        Scanned-page OCR needs Tesseract on the server (optional).
                    @endif
                </p>
            </div>
            <div class="af-card__body">
                <div
                    class="af-drop"
                    :class="dragging && 'is-drag'"
                    @click="$refs.extractInput.click()"
                    @dragover.prevent="dragging = true"
                    @dragleave.prevent="dragging = false"
                    @drop.prevent="onDrop($event)"
                >
                    <div class="af-drop__icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0 4 4m-4-4-4 4M4 16.5V18a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-1.5"/></svg>
                    </div>
                    <p class="af-drop__title">Drop PDF, DOCX{{ $ocrAvailable ? ', or image' : '' }} here</p>
                    <p class="af-drop__hint">or click to browse · max 50 MB</p>
                    <p class="af-drop__file" :class="extractName && 'is-on'" x-text="extractName"></p>
                    <input
                        x-ref="extractInput"
                        type="file"
                        accept=".pdf,.doc,.docx{{ $ocrAvailable ? ',.png,.jpg,.jpeg,.webp,.tif,.tiff' : '' }}"
                        style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);border:0"
                        @change="onExtractFile($event.target.files[0])"
                    >
                </div>

                <div class="af-extract-actions">
                    <button type="button" class="admin-btn admin-btn-primary" @click="runExtract()" :disabled="!extractFile || extracting">
                        <span x-show="!extracting">Extract details</span>
                        <span x-show="extracting" x-cloak>Extracting…</span>
                    </button>
                    <button type="button" class="admin-btn admin-btn-secondary" x-show="extractFile" x-cloak @click="clearExtract()">Clear</button>
                    <span class="af-extract-status" :class="statusClass" x-text="statusText"></span>
                </div>

                <template x-if="warnings.length">
                    <div class="af-banner af-banner--warn" style="margin-top:.85rem">
                        <strong>Notes</strong>
                        <ul>
                            <template x-for="(w, i) in warnings" :key="i">
                                <li x-text="w"></li>
                            </template>
                        </ul>
                    </div>
                </template>

                <template x-if="preview && (preview.title || preview.journal || preview.abstract || preview.plain)">
                    <div class="af-preview">
                        <div class="af-preview__head">
                            <p class="af-preview__label">Text preview</p>
                            <span class="af-preview__method" x-show="extractMethod" x-text="extractMethod"></span>
                        </div>
                        <div class="af-preview__body">
                            <div x-show="preview.journal || preview.citation || preview.doi || preview.issn">
                                <p class="af-preview__journal" x-show="preview.journal" x-text="preview.journal"></p>
                                <div class="af-preview__meta">
                                    <span class="af-preview__chip" x-show="preview.citation" x-text="preview.citation"></span>
                                    <span class="af-preview__chip" x-show="preview.issn" x-text="'ISSN ' + preview.issn"></span>
                                    <span class="af-preview__chip" x-show="preview.doi">
                                        DOI
                                        <a :href="preview.doi_url || ('https://doi.org/' + preview.doi)" target="_blank" rel="noopener" x-text="preview.doi"></a>
                                    </span>
                                    <span class="af-preview__chip" x-show="preview.homepage">
                                        <a :href="preview.homepage" target="_blank" rel="noopener">Journal homepage</a>
                                    </span>
                                </div>
                            </div>

                            <div x-show="preview.title || (preview.authors && preview.authors.length)">
                                <h3 class="af-preview__title" x-show="preview.title" x-text="preview.title"></h3>
                                <p class="af-preview__authors" x-show="preview.authors && preview.authors.length" x-text="(preview.authors || []).join(' · ')"></p>
                                <p class="af-preview__aff" x-show="preview.affiliation" x-text="preview.affiliation"></p>
                            </div>

                            <div class="af-preview__section" x-show="preview.history && (preview.history.received || preview.history.revised || preview.history.accepted)">
                                <p class="af-preview__section-title">Article history</p>
                                <div class="af-preview__history">
                                    <div class="af-preview__hist-item" x-show="preview.history.received">
                                        <span>Received</span>
                                        <strong x-text="preview.history.received"></strong>
                                    </div>
                                    <div class="af-preview__hist-item" x-show="preview.history.revised">
                                        <span>Revised</span>
                                        <strong x-text="preview.history.revised"></strong>
                                    </div>
                                    <div class="af-preview__hist-item" x-show="preview.history.accepted">
                                        <span>Accepted</span>
                                        <strong x-text="preview.history.accepted"></strong>
                                    </div>
                                </div>
                            </div>

                            <div class="af-preview__section" x-show="preview.abstract || preview.keywords">
                                <p class="af-preview__section-title" x-show="preview.abstract">Abstract</p>
                                <p class="af-preview__abstract" x-show="preview.abstract" x-text="preview.abstract"></p>
                                <p class="af-preview__keywords" x-show="preview.keywords">
                                    <strong>Keywords:</strong> <span x-text="preview.keywords"></span>
                                </p>
                            </div>

                            <div class="af-preview__section" x-show="!preview.title && !preview.journal && preview.plain">
                                <p class="af-preview__abstract" style="white-space:pre-wrap" x-text="preview.plain"></p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
        @endunless

        <div class="af-card">
            <div class="af-card__head">
                <h2 class="af-card__title">Placement</h2>
                <p class="af-card__desc">Choose the journal and issue. Create a volume or issue here if it does not exist yet.</p>
            </div>
            <div class="af-card__body">
                <div class="af-grid af-grid--2">
                    <div class="af-field">
                        <label for="journal_id">Journal <span class="af-req" title="Required">*</span></label>
                        @if($manageJournal)
                            <input type="hidden" name="journal_id" value="{{ $manageJournal->id }}">
                            <div class="af-input" style="display:flex;align-items:center;background:#f8fafc;font-weight:700">{{ $manageJournal->title }}</div>
                            <p class="af-hint">Locked to this journal’s manage portal.</p>
                        @else
                            <select id="journal_id" name="journal_id" required class="af-select" x-model="journalId" @change="onJournalChange()">
                                <option value="">Select journal</option>
                                @foreach($journals as $j)
                                    <option value="{{ $j->id }}">{{ $j->title }}</option>
                                @endforeach
                            </select>
                            @error('journal_id')<p class="af-error">{{ $message }}</p>@enderror
                        @endif
                    </div>
                    <div class="af-field">
                        <label for="issue_id">Issue</label>
                        <select id="issue_id" name="issue_id" class="af-select" x-model="issueId" :disabled="!journalId">
                <option value="">None</option>
                            <template x-for="issue in filteredIssues" :key="issue.id">
                                <option :value="String(issue.id)" x-text="issue.label + (issue.title ? ' — ' + issue.title : '')"></option>
                            </template>
            </select>
                        <p class="af-hint" x-show="journalId && filteredIssues.length === 0" x-cloak>No issues for this journal yet — create one below.</p>
                        @error('issue_id')<p class="af-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="af-quick" x-show="journalId" x-cloak>
                    <div class="af-quick__head">
                        <p class="af-quick__title">Create volume / issue on the go</p>
                        <div style="display:flex;gap:.4rem;flex-wrap:wrap">
                            <button type="button" class="admin-btn admin-btn-secondary" style="padding:.4rem .7rem;font-size:.74rem" @click="showVolumeForm = !showVolumeForm; showIssueForm = false">
                                <span x-text="showVolumeForm ? 'Hide volume' : '+ Volume'"></span>
                            </button>
                            <button type="button" class="admin-btn admin-btn-secondary" style="padding:.4rem .7rem;font-size:.74rem" @click="showIssueForm = !showIssueForm; showVolumeForm = false" :disabled="filteredVolumes.length === 0">
                                <span x-text="showIssueForm ? 'Hide issue' : '+ Issue'"></span>
                            </button>
                        </div>
        </div>

                    <div x-show="showVolumeForm" x-cloak>
                        <div class="af-grid af-grid--2">
                            <div class="af-field">
                                <label>Volume number <span class="af-req">*</span></label>
                                <input type="number" min="1" class="af-input" x-model="newVolume.volume_number" placeholder="e.g. 5">
                            </div>
                            <div class="af-field">
                                <label>Year <span class="af-req">*</span></label>
                                <input type="number" min="1900" max="2100" class="af-input" x-model="newVolume.year" placeholder="{{ now()->year }}">
                            </div>
                            <div class="af-field">
                                <label>Title</label>
                                <input type="text" class="af-input" x-model="newVolume.title" placeholder="Optional">
                            </div>
                            <div class="af-field">
                                <label>Status <span class="af-req">*</span></label>
                                <select class="af-select" x-model="newVolume.status">
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                </select>
                            </div>
                        </div>
                        <div style="margin-top:.7rem">
                            <button type="button" class="admin-btn admin-btn-primary" style="padding:.45rem .8rem;font-size:.78rem" @click="createVolume()" :disabled="creatingVolume">
                                <span x-show="!creatingVolume">Create volume</span>
                                <span x-show="creatingVolume" x-cloak>Creating…</span>
                            </button>
                        </div>
        </div>

                    <div x-show="showIssueForm" x-cloak>
                        <div class="af-grid af-grid--2">
                            <div class="af-field">
                                <label>Volume <span class="af-req">*</span></label>
                                <select class="af-select" x-model="newIssue.volume_id">
                                    <option value="">Select volume</option>
                                    <template x-for="volume in filteredVolumes" :key="volume.id">
                                        <option :value="String(volume.id)" x-text="volume.label"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="af-field">
                                <label>Issue number <span class="af-req">*</span></label>
                                <input type="number" min="1" class="af-input" x-model="newIssue.issue_number" placeholder="e.g. 2">
                            </div>
                            <div class="af-field">
                                <label>Title</label>
                                <input type="text" class="af-input" x-model="newIssue.title" placeholder="Optional">
                            </div>
                            <div class="af-field">
                                <label>Status <span class="af-req">*</span></label>
                                <select class="af-select" x-model="newIssue.status">
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                </select>
                            </div>
                        </div>
                        <div style="margin-top:.7rem">
                            <button type="button" class="admin-btn admin-btn-primary" style="padding:.45rem .8rem;font-size:.78rem" @click="createIssue()" :disabled="creatingIssue">
                                <span x-show="!creatingIssue">Create issue</span>
                                <span x-show="creatingIssue" x-cloak>Creating…</span>
                            </button>
                        </div>
        </div>

                    <p class="af-quick__msg" :class="quickMsgClass" x-show="quickMsg" x-text="quickMsg" x-cloak></p>
                </div>
            </div>
        </div>

        <div class="af-card">
            <div class="af-card__head">
                <h2 class="af-card__title">Article details</h2>
                <p class="af-card__desc">Core bibliographic fields. Extracted values can be edited before saving.</p>
            </div>
            <div class="af-card__body">
                <div class="af-grid af-grid--2">
                    <div class="af-field af-span-2">
                        <label for="title">Title <span class="af-req" title="Required">*</span></label>
                        <input id="title" name="title" type="text" required class="af-input" x-ref="title"
                            value="{{ old('title', $article->title ?? '') }}">
                        @error('title')<p class="af-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="af-field">
                        <label for="slug">Slug</label>
                        <input id="slug" name="slug" type="text" class="af-input" x-ref="slug"
                            value="{{ old('slug', $article->slug ?? '') }}"
                            placeholder="Auto from title if blank">
                        <p class="af-hint">Leave blank to generate from the title.</p>
                        @error('slug')<p class="af-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="af-field">
                        <label for="author_user_id">Author user ID</label>
                        <input id="author_user_id" name="author_user_id" type="number" class="af-input"
                            value="{{ old('author_user_id', $article->author_user_id ?? '') }}"
                            placeholder="Optional linked account">
                        @error('author_user_id')<p class="af-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="af-field af-span-2">
                        <label for="abstract">Abstract</label>
                        <textarea id="abstract" name="abstract" rows="5" class="af-textarea" x-ref="abstract">{{ old('abstract', $article->abstract ?? '') }}</textarea>
                        @error('abstract')<p class="af-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="af-field af-span-2">
                        <label>Categories</label>
                        <div class="af-cats">
                            <template x-for="cat in categories" :key="cat.id">
                                <label class="af-cat" :class="selectedCategoryIds.includes(String(cat.id)) && 'is-on'">
                                    <input type="checkbox" name="category_ids[]" :value="cat.id" x-model="selectedCategoryIds">
                                    <span x-text="cat.name"></span>
                                </label>
                            </template>
                        </div>
                        <p class="af-hint" x-show="categories.length === 0" x-cloak>No categories yet — create one below.</p>
                        @error('category_ids')<p class="af-error">{{ $message }}</p>@enderror
                        @error('category_ids.*')<p class="af-error">{{ $message }}</p>@enderror

                        <div class="af-quick" style="margin-top:.75rem">
                            <div class="af-quick__head">
                                <p class="af-quick__title">Create category on the go</p>
                            </div>
                            <div style="display:flex;gap:.45rem;flex-wrap:wrap;align-items:center">
                                <input
                                    type="text"
                                    class="af-input"
                                    style="flex:1;min-width:12rem"
                                    x-model="newCategoryName"
                                    @keydown.enter.prevent="createCategory()"
                                    placeholder="e.g. Methods Paper"
                                >
                                <button type="button" class="admin-btn admin-btn-primary" style="padding:.55rem .85rem;font-size:.78rem" @click="createCategory()" :disabled="creatingCategory">
                                    <span x-show="!creatingCategory">Add category</span>
                                    <span x-show="creatingCategory" x-cloak>Adding…</span>
                                </button>
                            </div>
                            <p class="af-quick__msg" :class="categoryMsgClass" x-show="categoryMsg" x-text="categoryMsg" x-cloak></p>
                        </div>
                    </div>
                    <div class="af-field">
                        <label for="keywords">Keywords</label>
                        <input id="keywords" name="keywords" type="text" class="af-input" x-ref="keywords"
                            value="{{ old('keywords', $article->keywords ?? '') }}"
                            placeholder="Comma-separated">
                    </div>
                    <div class="af-field af-span-2">
                        <label>Authors</label>
                        <p class="af-hint" style="margin-top:0;margin-bottom:.65rem">
                            Add only the details you have — skip optional fields like Middle name when unknown.
                            Display name is arranged as <strong>Surname, First name</strong>.
                        </p>

                        <div class="af-authors">
                            <template x-if="authors.length === 0">
                                <div class="af-authors__empty">
                                    No authors yet. Add the first author, then attach Surname, First name, and other details.
                                </div>
                            </template>

                            <template x-for="(author, ai) in authors" :key="author._id">
                                <div class="af-author">
                                    <div class="af-author__top">
                                        <div class="af-author__who">
                                            <div class="af-author__badge" x-text="'Author ' + (ai + 1)"></div>
                                            <div
                                                class="af-author__preview"
                                                :class="!authorDisplayName(author) && 'is-empty'"
                                                x-text="authorDisplayName(author) || 'Add Surname and First name…'"
                                            ></div>
                                        </div>
                                        <button type="button" class="af-author__remove" @click="removeAuthor(ai)">Remove</button>
        </div>

                                    <div class="af-author__rows" x-show="author.fields.length">
                                        <template x-for="(field, fi) in author.fields" :key="field.key + '-' + fi">
                                            <div class="af-author__row">
                                                <div class="af-author__row-key" x-text="authorKeyLabel(field.key)"></div>
                                                <template x-if="field.key === 'role'">
                                                    <select class="af-author__row-val af-author__row-val--select" x-model="field.value">
                                                        <option value="" disabled>Select role…</option>
                                                        <template x-for="role in authorRoleOptions" :key="'r-' + role.value">
                                                            <option :value="role.value" x-text="role.label"></option>
                                                        </template>
                                                    </select>
                                                </template>
                                                <template x-if="field.key !== 'role'">
                                                    <input
                                                        class="af-author__row-val"
                                                        type="text"
                                                        :placeholder="authorKeyPlaceholder(field.key)"
                                                        x-model="field.value"
                                                        @keydown.enter.prevent
                                                    >
                                                </template>
                                                <button type="button" class="af-author__row-del" title="Remove field" @click="removeAuthorField(ai, fi)">×</button>
                                            </div>
                                        </template>
        </div>

                                    <div class="af-author__add" x-show="availableAuthorKeys(author).length" x-cloak>
                                        <select class="af-select" x-model="author.pendingKey" @change="onPendingKeyChange(ai)">
                                            <template x-for="opt in availableAuthorKeys(author)" :key="opt.key">
                                                <option :value="opt.key" x-text="opt.optional ? (opt.label + ' (optional)') : opt.label"></option>
                                            </template>
                                        </select>
                                        <template x-if="author.pendingKey === 'role'">
                                            <select class="af-select" x-model="author.pendingValue">
                                                <option value="">Select role…</option>
                                                <template x-for="role in authorRoleOptions" :key="'p-' + role.value">
                                                    <option :value="role.value" x-text="role.label"></option>
                                                </template>
                                            </select>
                                        </template>
                                        <template x-if="author.pendingKey !== 'role'">
                                            <input
                                                class="af-input"
                                                type="text"
                                                x-model="author.pendingValue"
                                                :placeholder="authorKeyPlaceholder(author.pendingKey)"
                                                @keydown.enter.prevent="addAuthorField(ai)"
                                            >
                                        </template>
                                        <div class="af-author__add-actions">
                                            <button
                                                type="button"
                                                class="af-author__add-btn"
                                                @click="addAuthorField(ai)"
                                                :disabled="!author.pendingKey || !String(author.pendingValue || '').trim()"
                                            >Add detail</button>
                                            <button
                                                type="button"
                                                class="af-author__skip"
                                                x-show="isOptionalAuthorKey(author.pendingKey)"
                                                x-cloak
                                                @click="skipAuthorKey(ai)"
                                            >Skip</button>
                                        </div>
        </div>

                                    <div class="af-author__meta">
                                        <label class="af-author__check">
                                            <input type="checkbox" x-model="author.is_corresponding" @change="onCorrespondingChange(ai)">
                                            Corresponding author
                                        </label>
        </div>

                                    {{-- Submitted shape: authors[i][surname], authors[i][email], … --}}
                                    <template x-for="key in authorSubmitKeys" :key="'h-' + ai + '-' + key">
                                        <input type="hidden" :name="'authors[' + ai + '][' + key + ']'" :value="authorFieldValue(author, key)">
                                    </template>
                                    <input type="hidden" :name="'authors[' + ai + '][is_corresponding]'" :value="author.is_corresponding ? '1' : '0'">
                                </div>
                            </template>
        </div>

                        <div class="af-authors__actions" style="margin-top:.75rem">
                            <button type="button" class="admin-btn admin-btn-secondary" @click="addAuthor()">+ Add author</button>
        </div>

                        @error('authors')<p class="af-error">{{ $message }}</p>@enderror
                        @error('authors.*')<p class="af-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="af-card">
            <div class="af-card__head">
                <h2 class="af-card__title">Identifiers &amp; reading</h2>
                <p class="af-card__desc">DOI, license, pages, and estimated reading time.</p>
            </div>
            <div class="af-card__body">
                <div class="af-grid af-grid--2">
                    <div class="af-field">
                        <label for="doi">DOI</label>
                        <input id="doi" name="doi" type="text" class="af-input" x-ref="doi"
                            value="{{ old('doi', $article->doi ?? '') }}" placeholder="10.xxxx/…">
                    </div>
                    <div class="af-field">
                        <label for="license">License</label>
                        <select id="license" name="license" class="af-select" x-model="license" x-ref="license">
                            <option value="">Select a license…</option>
                            @foreach($licenseOptions as $opt)
                                <option value="{{ $opt['label'] }}">{{ $opt['label'] }} — {{ $opt['description'] }}</option>
                @endforeach
            </select>
                        <p class="af-hint" style="margin-top:.45rem" x-show="licenseUrl" x-cloak>
                            <a style="color:#1d4ed8;font-weight:700;text-decoration:none" :href="licenseUrl" target="_blank" rel="noopener">View license terms →</a>
                        </p>
                        @error('license')<p class="af-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="af-field">
                        <label for="page_range">Page range</label>
                        <input id="page_range" name="page_range" type="text" class="af-input" x-ref="page_range"
                            value="{{ old('page_range', $article->page_range ?? '') }}" placeholder="12-28">
                    </div>
                    <div class="af-field">
                        <label for="mins_read">Minutes to read</label>
                        <input id="mins_read" name="mins_read" type="number" min="1" class="af-input"
                            value="{{ old('mins_read', $article->mins_read ?? '') }}"
                            placeholder="e.g. 8">
                        <p class="af-hint">Estimated reading time in whole minutes (not seconds).</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="af-card">
            <div class="af-card__head">
                <h2 class="af-card__title">Galley file</h2>
                <p class="af-card__desc">Full-text document served to authorized readers (PDF / DOC / DOCX).</p>
            </div>
            <div class="af-card__body">
                <div class="af-field">
                    <label for="galley">Document</label>
                    <input id="galley" name="galley" type="file" accept=".pdf,.doc,.docx" class="af-file" x-ref="galley"
                        @change="galleyName = $event.target.files[0]?.name || ''">
                    <p class="af-hint" x-show="galleyName" x-cloak x-text="'Selected: ' + galleyName"></p>
                    @if($isEdit && ($article->document_path ?? null))
                        <p class="af-hint">A galley is already on file. Upload only to replace it.</p>
                    @endif
                    @error('galley')<p class="af-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="af-actions">
            <button type="submit" class="admin-btn admin-btn-primary">{{ $isEdit ? 'Save article' : 'Create article' }}</button>
            <a href="{{ $cancelUrl }}" class="admin-btn admin-btn-secondary">Cancel</a>
        </div>
        </div>

    <aside class="af-side">
        <div class="af-card">
            <div class="af-card__head">
                <h2 class="af-card__title">Publishing</h2>
                <p class="af-card__desc">Visibility and status for this article.</p>
            </div>
            <div class="af-card__body">
                <div class="af-toggle">
        <div>
                        <div class="af-toggle__label">Published <span class="af-req">*</span></div>
                        <p class="af-toggle__hint">Draft stays hidden from the public catalog.</p>
                    </div>
                    <button type="button" class="af-switch" :class="status === 'published' && 'is-on'"
                        @click="status = status === 'published' ? 'draft' : 'published'"
                        :aria-pressed="status === 'published'"></button>
                </div>
                <input type="hidden" name="status" :value="status">

                <div class="af-field" style="margin-top:.85rem">
                    <label for="visibility">Visibility <span class="af-req" title="Required">*</span></label>
                    <select id="visibility" name="visibility" required class="af-select" x-model="visibility">
                        @foreach(['open' => 'Open', 'members_only' => 'Members only', 'paid' => 'Paid', 'closed' => 'Closed'] as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('visibility')<p class="af-error">{{ $message }}</p>@enderror
        </div>

                <div class="af-grid af-grid--2" style="margin-top:.85rem" x-show="visibility === 'paid'" x-cloak>
                    <div class="af-field">
                        <label for="price_amount">Price (minor units) <span class="af-req">*</span></label>
                        <input id="price_amount" name="price_amount" type="number" min="0" class="af-input"
                            value="{{ old('price_amount', $article->price_amount ?? '') }}"
                            :required="visibility === 'paid'">
                        @error('price_amount')<p class="af-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="af-field">
                        <label for="currency">Currency <span class="af-req">*</span></label>
                        <input id="currency" name="currency" type="text" class="af-input"
                            value="{{ old('currency', $article->currency ?? 'NGN') }}"
                            :required="visibility === 'paid'">
                    </div>
                </div>
            </div>
        </div>

        <div class="af-card">
            <div class="af-card__head">
                <h2 class="af-card__title">Tips</h2>
            </div>
            <div class="af-card__body" style="font-size:.78rem;color:var(--muted);line-height:1.5">
                <p style="margin:0">Upload &amp; extract reads embedded text from PDFs/DOCX and fills matching fields. Always review before publishing.</p>
                @unless($ocrAvailable)
                    <p style="margin:.65rem 0 0">For scanned pages, install <strong>Tesseract OCR</strong> and set <code>TESSERACT_PATH</code> in <code>.env</code> if needed.</p>
                @endunless
            </div>
        </div>
    </aside>
</form>

<script>
function articleForm(cfg) {
    const catalog = cfg.catalog || { volumes: [], issues: [] };
    return {
        mode: cfg.isEdit ? 'manual' : (cfg.initialMode || 'manual'),
        visibility: cfg.visibility || 'open',
        status: cfg.status || 'draft',
        journalId: cfg.journalId ? String(cfg.journalId) : '',
        issueId: cfg.issueId ? String(cfg.issueId) : '',
        volumes: catalog.volumes || [],
        issues: catalog.issues || [],
        showVolumeForm: false,
        showIssueForm: false,
        creatingVolume: false,
        creatingIssue: false,
        quickMsg: '',
        quickMsgClass: '',
        newVolume: {
            volume_number: '',
            year: String(new Date().getFullYear()),
            title: '',
            status: 'published',
        },
        newIssue: {
            volume_id: '',
            issue_number: '',
            title: '',
            status: 'published',
        },
        categories: (cfg.categories || []).map((c) => ({ ...c, id: String(c.id) })),
        selectedCategoryIds: (cfg.selectedCategoryIds || []).map(String),
        newCategoryName: '',
        creatingCategory: false,
        categoryMsg: '',
        categoryMsgClass: '',
        extractFile: null,
        extractName: '',
        galleyName: '',
        extracting: false,
        dragging: false,
        statusText: '',
        statusClass: '',
        warnings: [],
        preview: null,
        extractMethod: '',
        ocrAvailable: cfg.ocrAvailable,
        licenseOptions: cfg.licenseOptions || [],
        license: cfg.license || '',

        get licenseUrl() {
            const hit = (this.licenseOptions || []).find((o) => o.label === this.license);
            return hit ? hit.url : '';
        },

        applyLicense(raw) {
            const text = String(raw || '').trim();
            if (!text) return;
            const lower = text.toLowerCase();
            const hit = (this.licenseOptions || []).find((o) =>
                o.label.toLowerCase() === lower
                || o.key === lower
                || lower.includes(o.label.toLowerCase())
                || (o.key === 'cc-by-4.0' && /cc[\s-]?by(?![\s-]?nc|[\s-]?sa|[\s-]?nd)/i.test(text))
                || (o.key === 'cc-by-sa-4.0' && /cc[\s-]?by[\s-]?sa/i.test(text))
                || (o.key === 'cc-by-nc-nd-4.0' && /cc[\s-]?by[\s-]?nc[\s-]?nd/i.test(text))
                || (o.key === 'cc-by-nc-4.0' && /cc[\s-]?by[\s-]?nc(?![\s-]?nd)/i.test(text))
                || (o.key === 'all-rights-reserved' && /all\s+rights\s+reserved/i.test(text))
            );
            if (hit) {
                this.license = hit.label;
                this.$refs.license?.classList.add('is-filled');
                setTimeout(() => this.$refs.license?.classList.remove('is-filled'), 1800);
            }
        },

        authorKeyOptions: [
            { key: 'surname', label: 'Surname', optional: false },
            { key: 'given_names', label: 'First name', optional: false },
            { key: 'email', label: 'Email', optional: true },
            { key: 'affiliation', label: 'Affiliation', optional: true },
            { key: 'orcid', label: 'ORCID', optional: true },
            { key: 'nationality', label: 'Nationality', optional: true },
            { key: 'middle_name', label: 'Middle name', optional: true },
            { key: 'role', label: 'Role', optional: true },
        ],
        authorRoleOptions: [
            { value: 'author', label: 'Author' },
            { value: 'co-author', label: 'Co-author' },
            { value: 'lead-author', label: 'Lead author' },
            { value: 'editor', label: 'Editor' },
            { value: 'contributor', label: 'Contributor' },
            { value: 'translator', label: 'Translator' },
        ],
        authorPreferredKeys: ['surname', 'given_names', 'email', 'affiliation', 'orcid', 'nationality', 'middle_name', 'role'],
        authorSubmitKeys: ['surname', 'given_names', 'middle_name', 'email', 'affiliation', 'nationality', 'orcid', 'role'],
        authors: (cfg.initialAuthors || []).map((a, i) => {
            const fields = Array.isArray(a.fields) ? a.fields.map((f) => ({ key: f.key, value: f.value || '' })) : [];
            const used = new Set(fields.map((f) => f.key));
            const preferred = ['surname', 'given_names', 'email', 'affiliation', 'orcid', 'nationality', 'middle_name', 'role'];
            return {
                _id: 'a' + Date.now() + '-' + i + '-' + Math.random().toString(36).slice(2, 7),
                fields,
                is_corresponding: !!a.is_corresponding,
                pendingKey: preferred.find((k) => !used.has(k)) || 'surname',
                pendingValue: '',
            };
        }),

        nextPreferredAuthorKey(usedSet) {
            return this.authorPreferredKeys.find((k) => !usedSet.has(k)) || '';
        },

        makeAuthor(seed = {}) {
            const fields = Array.isArray(seed.fields) ? seed.fields.map((f) => ({ key: f.key, value: f.value || '' })) : [];
            const used = new Set(fields.map((f) => f.key));
            return {
                _id: 'a' + Date.now() + '-' + Math.random().toString(36).slice(2, 8),
                fields,
                is_corresponding: !!seed.is_corresponding,
                pendingKey: this.nextPreferredAuthorKey(used) || 'surname',
                pendingValue: '',
            };
        },

        addAuthor() {
            const author = this.makeAuthor({
                is_corresponding: this.authors.length === 0,
            });
            this.authors.push(author);
        },

        removeAuthor(index) {
            this.authors.splice(index, 1);
            if (this.authors.length && !this.authors.some((a) => a.is_corresponding)) {
                this.authors[0].is_corresponding = true;
            }
        },

        authorKeyLabel(key) {
            return (this.authorKeyOptions.find((o) => o.key === key) || {}).label || key;
        },

        isOptionalAuthorKey(key) {
            return !!(this.authorKeyOptions.find((o) => o.key === key) || {}).optional;
        },

        authorKeyPlaceholder(key) {
            const map = {
                surname: 'e.g. Okonkwo',
                given_names: 'e.g. Ada',
                middle_name: 'e.g. Chioma',
                email: 'name@example.com',
                affiliation: 'University / Institute',
                nationality: 'e.g. Nigerian',
                orcid: '0000-0000-0000-0000',
            };
            return map[key] || 'Enter value';
        },

        availableAuthorKeys(author) {
            const used = new Set((author.fields || []).map((f) => f.key));
            return this.authorKeyOptions.filter((o) => !used.has(o.key));
        },

        authorFieldValue(author, key) {
            const row = (author.fields || []).find((f) => f.key === key);
            return row ? String(row.value || '') : '';
        },

        authorDisplayName(author) {
            const surname = this.authorFieldValue(author, 'surname').trim();
            const given = this.authorFieldValue(author, 'given_names').trim();
            const middle = this.authorFieldValue(author, 'middle_name').trim();
            const rest = [given, middle].filter(Boolean).join(' ');
            if (surname && rest) return surname + ', ' + rest;
            if (surname) return surname;
            if (rest) return rest;
            return '';
        },

        onPendingKeyChange(index) {
            const author = this.authors[index];
            if (!author) return;
            author.pendingValue = '';
        },

        skipAuthorKey(index) {
            const author = this.authors[index];
            if (!author || !author.pendingKey) return;
            const used = new Set((author.fields || []).map((f) => f.key));
            used.add(author.pendingKey);
            author.pendingValue = '';
            author.pendingKey = this.nextPreferredAuthorKey(used);
        },

        addAuthorField(index) {
            const author = this.authors[index];
            if (!author) return;
            const key = String(author.pendingKey || '').trim();
            const value = String(author.pendingValue || '').trim();
            if (!key || !value) return;
            if (key === 'role' && !this.authorRoleOptions.some((r) => r.value === value)) return;

            const existing = author.fields.findIndex((f) => f.key === key);
            if (existing >= 0) {
                author.fields[existing].value = value;
            } else {
                author.fields.push({ key, value });
            }
            author.pendingValue = '';
            const used = new Set(author.fields.map((f) => f.key));
            author.pendingKey = this.nextPreferredAuthorKey(used);
        },

        removeAuthorField(authorIndex, fieldIndex) {
            const author = this.authors[authorIndex];
            if (!author) return;
            author.fields.splice(fieldIndex, 1);
            const used = new Set(author.fields.map((f) => f.key));
            if (!author.pendingKey || used.has(author.pendingKey)) {
                author.pendingKey = this.nextPreferredAuthorKey(used) || 'surname';
            }
        },

        onCorrespondingChange(index) {
            if (!this.authors[index]?.is_corresponding) return;
            this.authors.forEach((a, i) => {
                if (i !== index) a.is_corresponding = false;
            });
        },

        importAuthorsText(text) {
            const raw = String(text || '').trim();
            if (!raw) return;
            const lines = raw.split(/\r\n|\r|\n/).map((l) => l.trim()).filter(Boolean);
            if (!lines.length) return;

            const imported = lines.map((line, i) => {
                const parts = line.split('|').map((p) => p.trim());
                const name = parts[0] || '';
                let surname = '';
                let given = '';
                if (name.includes(',')) {
                    const bits = name.split(',');
                    surname = (bits[0] || '').trim();
                    given = bits.slice(1).join(',').trim();
                } else {
                    const bits = name.split(/\s+/).filter(Boolean);
                    if (bits.length >= 2) {
                        surname = bits[bits.length - 1];
                        given = bits.slice(0, -1).join(' ');
                    } else {
                        given = name;
                    }
                }
                const fields = [];
                if (surname) fields.push({ key: 'surname', value: surname });
                if (given) fields.push({ key: 'given_names', value: given });
                if (parts[1]) fields.push({ key: 'email', value: parts[1] });
                if (parts[2]) fields.push({ key: 'affiliation', value: parts[2] });
                if (parts[3]) fields.push({ key: 'orcid', value: parts[3] });
                return this.makeAuthor({ fields, is_corresponding: i === 0 });
            });

            this.authors = imported;
        },

        get filteredVolumes() {
            const jid = String(this.journalId || '');
            if (!jid) return [];
            return this.volumes.filter((v) => String(v.journal_id) === jid);
        },

        get filteredIssues() {
            const jid = String(this.journalId || '');
            if (!jid) return [];
            return this.issues.filter((i) => String(i.journal_id) === jid);
        },

        onJournalChange() {
            const stillValid = this.filteredIssues.some((i) => String(i.id) === String(this.issueId));
            if (!stillValid) this.issueId = '';
            this.newIssue.volume_id = '';
            this.quickMsg = '';
            if (this.filteredVolumes.length === 0) {
                this.showIssueForm = false;
            }
        },

        toggleCategory(id) {
            const key = String(id);
            if (this.selectedCategoryIds.includes(key)) {
                this.selectedCategoryIds = this.selectedCategoryIds.filter((v) => v !== key);
            } else {
                this.selectedCategoryIds = [...this.selectedCategoryIds, key];
            }
        },

        async createCategory() {
            const name = (this.newCategoryName || '').trim();
            if (!name || this.creatingCategory) return;
            this.creatingCategory = true;
            this.categoryMsg = '';
            try {
                const res = await fetch(cfg.quickCategoryUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': cfg.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ name }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || Object.values(data.errors || {})[0]?.[0] || 'Could not create category');
                }
                const cat = { id: String(data.category.id), name: data.category.name, slug: data.category.slug };
                if (!this.categories.some((c) => c.id === cat.id)) {
                    this.categories = [...this.categories, cat].sort((a, b) => a.name.localeCompare(b.name));
                }
                if (!this.selectedCategoryIds.includes(cat.id)) {
                    this.selectedCategoryIds = [...this.selectedCategoryIds, cat.id];
                }
                this.newCategoryName = '';
                this.categoryMsg = data.created ? 'Category created and selected.' : 'Category already existed and was selected.';
                this.categoryMsgClass = 'is-ok';
            } catch (err) {
                this.categoryMsg = err.message || 'Could not create category';
                this.categoryMsgClass = 'is-err';
            } finally {
                this.creatingCategory = false;
            }
        },

        async createVolume() {
            if (!this.journalId || this.creatingVolume) return;
            if (!this.newVolume.volume_number || !this.newVolume.year) {
                this.quickMsg = 'Volume number and year are required.';
                this.quickMsgClass = 'is-err';
                return;
            }
            this.creatingVolume = true;
            this.quickMsg = '';
            try {
                const res = await fetch(cfg.quickVolumeUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': cfg.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        journal_id: Number(this.journalId),
                        volume_number: Number(this.newVolume.volume_number),
                        year: Number(this.newVolume.year),
                        title: this.newVolume.title || null,
                        status: this.newVolume.status,
                    }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || Object.values(data.errors || {})[0]?.[0] || 'Could not create volume');
                }
                this.volumes = [data.volume, ...this.volumes];
                this.newIssue.volume_id = String(data.volume.id);
                this.showVolumeForm = false;
                this.showIssueForm = true;
                this.newVolume.volume_number = '';
                this.newVolume.title = '';
                this.quickMsg = 'Volume created. You can add an issue next.';
                this.quickMsgClass = 'is-ok';
            } catch (err) {
                this.quickMsg = err.message || 'Could not create volume';
                this.quickMsgClass = 'is-err';
            } finally {
                this.creatingVolume = false;
            }
        },

        async createIssue() {
            if (!this.newIssue.volume_id || this.creatingIssue) return;
            if (!this.newIssue.issue_number) {
                this.quickMsg = 'Issue number is required.';
                this.quickMsgClass = 'is-err';
                return;
            }
            this.creatingIssue = true;
            this.quickMsg = '';
            try {
                const res = await fetch(cfg.quickIssueUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': cfg.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        volume_id: Number(this.newIssue.volume_id),
                        issue_number: Number(this.newIssue.issue_number),
                        title: this.newIssue.title || null,
                        status: this.newIssue.status,
                    }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || Object.values(data.errors || {})[0]?.[0] || 'Could not create issue');
                }
                this.issues = [data.issue, ...this.issues];
                this.issueId = String(data.issue.id);
                this.showIssueForm = false;
                this.newIssue.issue_number = '';
                this.newIssue.title = '';
                this.quickMsg = 'Issue created and selected.';
                this.quickMsgClass = 'is-ok';
            } catch (err) {
                this.quickMsg = err.message || 'Could not create issue';
                this.quickMsgClass = 'is-err';
            } finally {
                this.creatingIssue = false;
            }
        },

        onDrop(e) {
            this.dragging = false;
            const file = e.dataTransfer?.files?.[0];
            if (file) this.onExtractFile(file);
        },

        onExtractFile(file) {
            if (!file) return;
            this.extractFile = file;
            this.extractName = file.name;
            this.statusText = 'Ready to extract';
            this.statusClass = '';
            this.warnings = [];
            this.preview = null;
            this.extractMethod = '';
            this.syncGalley(file);
        },

        clearExtract() {
            this.extractFile = null;
            this.extractName = '';
            this.statusText = '';
            this.statusClass = '';
            this.warnings = [];
            this.preview = null;
            this.extractMethod = '';
            if (this.$refs.extractInput) this.$refs.extractInput.value = '';
        },

        syncGalley(file) {
            const ok = /\.(pdf|doc|docx)$/i.test(file.name);
            if (!ok || !this.$refs.galley) return;
            try {
                const dt = new DataTransfer();
                dt.items.add(file);
                this.$refs.galley.files = dt.files;
                this.galleyName = file.name;
            } catch (_) {}
        },

        fillField(ref, value) {
            if (!value || !this.$refs[ref]) return;
            this.$refs[ref].value = value;
            this.$refs[ref].classList.add('is-filled');
            setTimeout(() => this.$refs[ref]?.classList.remove('is-filled'), 1800);
        },

        async runExtract() {
            if (!this.extractFile || this.extracting) return;
            this.extracting = true;
            this.statusText = 'Reading document…';
            this.statusClass = 'is-busy';
            this.warnings = [];
            this.preview = null;
            this.extractMethod = '';

            const body = new FormData();
            body.append('document', this.extractFile);
            body.append('_token', cfg.csrf);

            try {
                const res = await fetch(cfg.extractUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': cfg.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body,
                    credentials: 'same-origin',
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || data.errors?.document?.[0] || 'Extraction failed');
                }

                const fields = data.fields || {};
                this.fillField('title', fields.title);
                this.fillField('abstract', fields.abstract);
                this.fillField('keywords', fields.keywords);
                this.fillField('doi', fields.doi);
                this.applyLicense(fields.license);
                this.fillField('page_range', fields.page_range);
                this.importAuthorsText(fields.authors_text);

                this.warnings = data.warnings || [];
                this.preview = data.preview || { plain: data.raw_text_preview || '' };
                this.extractMethod = String(data.method || 'extract').replace(/_/g, ' ');
                const filled = Object.values(fields).filter(Boolean).length;
                this.statusText = filled
                    ? `Filled ${filled} field${filled === 1 ? '' : 's'} via ${data.method || 'extract'}`
                    : 'No fields detected — review the preview and fill manually';
                this.statusClass = filled ? 'is-ok' : 'is-err';
            } catch (err) {
                this.statusText = err.message || 'Extraction failed';
                this.statusClass = 'is-err';
            } finally {
                this.extracting = false;
            }
        },
    }
}
</script>
