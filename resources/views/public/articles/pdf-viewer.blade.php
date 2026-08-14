@extends('layouts.journal')

@section('title', $article->title.' (PDF) | '.$journal->title)
@section('meta_description', $meta['description'] ?? '')
@section('canonical', $meta['canonical'] ?? $article->publicUrl())

@section('seo')
    @include('seo.article-meta', ['article' => $article, 'journal' => $journal, 'meta' => $meta])
    <meta name="citation_pdf_url" content="{{ $pdfStreamUrl }}">
    <link rel="alternate" type="application/pdf" href="{{ $pdfStreamUrl }}">
@endsection

@section('content')
<style>
    .pdf-shell { max-width: 72rem; margin: 0 auto; padding: 1rem 1rem 2rem; }
    .pdf-toolbar {
        display: flex; flex-wrap: wrap; gap: .65rem; align-items: flex-start;
        justify-content: space-between; margin-bottom: .85rem;
    }
    .pdf-toolbar__meta { min-width: 0; flex: 1; }
    .pdf-toolbar__title {
        margin: 0; font-size: 1.05rem; font-weight: 750; line-height: 1.35;
        color: var(--j-text); letter-spacing: -.015em;
    }
    .pdf-toolbar__byline { margin: .3rem 0 0; font-size: .8rem; color: var(--j-muted); line-height: 1.4; }
    .pdf-toolbar__actions { display: flex; flex-wrap: wrap; gap: .45rem; }
    .pdf-frame-wrap {
        border: 1px solid color-mix(in srgb, var(--j-text) 12%, transparent);
        border-radius: 1rem; overflow: hidden; background: #111827;
        box-shadow: 0 16px 40px rgba(15,23,42,.08);
        min-height: 75vh;
    }
    .pdf-frame-wrap iframe, .pdf-frame-wrap embed {
        display: block; width: 100%; height: 80vh; min-height: 32rem; border: 0; background: #525659;
    }
    .pdf-note {
        margin-top: .75rem; font-size: .75rem; color: var(--j-muted); line-height: 1.45;
    }
</style>

<div class="pdf-shell">
    <div class="pdf-toolbar">
        <div class="pdf-toolbar__meta">
            <p class="pdf-toolbar__title">{{ $article->title }}</p>
            <p class="pdf-toolbar__byline">
                {{ $article->authors->pluck('name')->join(', ') }}
                @if(!empty($meta['doi'])) · <a class="j-link" href="{{ $meta['doi_url'] }}" target="_blank" rel="noopener">doi:{{ $meta['doi'] }}</a>@endif
                @if($article->issue) · {{ $article->issue->label() }}@endif
            </p>
        </div>
        <div class="pdf-toolbar__actions">
            <a href="{{ route('journals.articles.show', [$journal, $article]) }}" class="j-btn-ghost">Article page</a>
            <a href="{{ $pdfStreamUrl }}" class="j-btn" target="_blank" rel="noopener">Open / download PDF</a>
        </div>
    </div>

    <div class="pdf-frame-wrap">
        <iframe
            title="PDF: {{ $article->title }}"
            src="{{ $pdfStreamUrl }}#toolbar=1&navpanes=0"
            type="application/pdf"
        ></iframe>
    </div>

    <p class="pdf-note">
        This reader page includes full scholarly metadata in the document head (visible in View Source / Elements).
        The PDF file itself also carries Title, Author, Subject, Keywords, and DOI in its document properties.
    </p>
</div>
@endsection
