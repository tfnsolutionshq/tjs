{{-- Scholarly / social / Dublin Core metadata for article landing pages --}}
<meta name="robots" content="index,follow,max-image-preview:large">
<meta name="author" content="{{ collect($meta['citation_authors'] ?? [])->join(', ') }}">
@if(!empty($meta['citation_keywords']))
    <meta name="keywords" content="{{ collect($meta['citation_keywords'])->join(', ') }}">
@endif
@if(!empty($meta['doi']))
    <meta name="doi" content="{{ $meta['doi'] }}">
    <meta name="DC.Identifier.DOI" content="{{ $meta['doi'] }}">
    <link rel="doi" href="{{ $meta['doi_url'] }}">
@endif
@if(!empty($meta['license']))
    <meta name="license" content="{{ $meta['license'] }}">
    @if(!empty($meta['license_url']))
        <link rel="license" href="{{ $meta['license_url'] }}">
    @endif
@endif
@if(!empty($meta['citation_pdf_url']))
    <link rel="alternate" type="application/pdf" title="Full text PDF" href="{{ $meta['citation_pdf_url'] }}">
@endif

{{-- Open Graph --}}
<meta property="og:title" content="{{ $meta['og_title'] ?? $article->title }}">
<meta property="og:description" content="{{ $meta['og_description'] ?? ($meta['description'] ?? '') }}">
<meta property="og:type" content="article">
<meta property="og:url" content="{{ $meta['og_url'] ?? ($meta['canonical'] ?? url()->current()) }}">
<meta property="og:site_name" content="{{ $meta['og_site_name'] ?? $journal->title }}">
<meta property="og:locale" content="{{ $meta['og_locale'] ?? 'en_US' }}">
@if(!empty($meta['og_image']))
    <meta property="og:image" content="{{ $meta['og_image'] }}">
    <meta property="og:image:alt" content="{{ $article->title }}">
@endif
@if(!empty($meta['published_iso']))
    <meta property="article:published_time" content="{{ $meta['published_iso'] }}">
@endif
@if(!empty($meta['license']))
    <meta property="article:section" content="{{ $meta['category'] ?? 'Research' }}">
    <meta property="article:tag" content="{{ collect($meta['citation_keywords'] ?? [])->join(',') }}">
@endif
@foreach(($meta['citation_authors'] ?? []) as $authorName)
    <meta property="article:author" content="{{ $authorName }}">
@endforeach

{{-- Twitter --}}
<meta name="twitter:card" content="{{ $meta['twitter_card'] ?? 'summary' }}">
<meta name="twitter:title" content="{{ $meta['twitter_title'] ?? $article->title }}">
<meta name="twitter:description" content="{{ $meta['twitter_description'] ?? ($meta['description'] ?? '') }}">
@if(!empty($meta['twitter_image']))
    <meta name="twitter:image" content="{{ $meta['twitter_image'] }}">
@endif

{{-- Highwire Press (Google Scholar) --}}
<meta name="citation_title" content="{{ $meta['citation_title'] ?? $article->title }}">
@foreach(($meta['citation_authors'] ?? []) as $authorName)
    <meta name="citation_author" content="{{ $authorName }}">
@endforeach
@foreach(($meta['citation_author_institutions'] ?? []) as $institution)
    <meta name="citation_author_institution" content="{{ $institution }}">
@endforeach
@foreach([
    'citation_journal_title','citation_journal_abbrev','citation_publisher','citation_publication_date','citation_online_date',
    'citation_volume','citation_issue','citation_issn','citation_eissn','citation_doi','citation_firstpage','citation_lastpage',
    'citation_language','citation_abstract_html_url','citation_fulltext_html_url','citation_pdf_url','citation_article_type'
] as $key)
    @if(!empty($meta[$key]))
        <meta name="{{ $key }}" content="{{ $meta[$key] }}">
    @endif
@endforeach
@foreach(($meta['citation_keywords'] ?? []) as $kw)
    <meta name="citation_keywords" content="{{ $kw }}">
@endforeach

{{-- Dublin Core --}}
<meta name="dc.title" content="{{ $meta['dc.title'] ?? $article->title }}">
@foreach(($meta['dc.creator'] ?? []) as $creator)
    <meta name="dc.creator" content="{{ $creator }}">
@endforeach
@foreach(($meta['dc.subject'] ?? []) as $subject)
    <meta name="dc.subject" content="{{ $subject }}">
@endforeach
@foreach([
    'dc.description','dc.publisher','dc.contributor','dc.date','dc.type','dc.format',
    'dc.identifier','dc.language','dc.rights','dc.relation.ispartof','dc.source'
] as $key)
    @if(!empty($meta[$key]))
        <meta name="{{ $key }}" content="{{ $meta[$key] }}">
    @endif
@endforeach

{{-- PRISM --}}
@foreach([
    'prism.publicationName','prism.issn','prism.eIssn','prism.doi','prism.volume','prism.number',
    'prism.startingPage','prism.endingPage','prism.publicationDate','prism.url','prism.copyright'
] as $key)
    @if(!empty($meta[$key]))
        <meta name="{{ $key }}" content="{{ $meta[$key] }}">
    @endif
@endforeach

<script type="application/ld+json">
{!! json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'ScholarlyArticle',
    'headline' => $article->title,
    'name' => $article->title,
    'description' => $meta['abstract'] ?? ($meta['description'] ?? null),
    'datePublished' => $meta['published_iso'] ?? optional($article->published_at)->toAtomString(),
    'dateModified' => optional($article->updated_at)->toAtomString(),
    'author' => collect($meta['authors_detailed'] ?? [])->map(function ($a) {
        return array_filter([
            '@type' => 'Person',
            'name' => $a['name'] ?? null,
            'affiliation' => !empty($a['affiliation']) ? ['@type' => 'Organization', 'name' => $a['affiliation']] : null,
            'identifier' => !empty($a['orcid']) ? 'https://orcid.org/'.preg_replace('#^https?://orcid.org/#i', '', $a['orcid']) : null,
            'email' => $a['email'] ?? null,
        ]);
    })->values()->all(),
    'image' => $meta['image'] ?? null,
    'keywords' => collect($meta['citation_keywords'] ?? [])->join(', ') ?: null,
    'articleSection' => $meta['category'] ?? null,
    'pageStart' => $meta['citation_firstpage'] ?? null,
    'pageEnd' => $meta['citation_lastpage'] ?? null,
    'pagination' => $meta['page_range'] ?? null,
    'inLanguage' => $meta['citation_language'] ?? null,
    'isAccessibleForFree' => ($meta['visibility'] ?? null) === 'open',
    'isPartOf' => array_filter([
        '@type' => 'PublicationIssue',
        'issueNumber' => $meta['citation_issue'] ?? null,
        'isPartOf' => array_filter([
            '@type' => 'PublicationVolume',
            'volumeNumber' => $meta['citation_volume'] ?? null,
            'isPartOf' => array_filter([
                '@type' => 'Periodical',
                'name' => $journal->title,
                'issn' => $meta['citation_issn'] ?? $journal->issn,
                'publisher' => array_filter([
                    '@type' => 'Organization',
                    'name' => $meta['citation_publisher'] ?? null,
                ]),
            ]),
        ]),
    ]),
    'identifier' => array_values(array_filter([
        !empty($meta['doi']) ? ['@type' => 'PropertyValue', 'propertyID' => 'DOI', 'value' => $meta['doi']] : null,
        ['@type' => 'PropertyValue', 'propertyID' => 'URL', 'value' => $meta['canonical'] ?? url()->current()],
    ])),
    'url' => $meta['canonical'] ?? url()->current(),
    'sameAs' => $meta['doi_url'] ?? null,
    'license' => $meta['license_url'] ?? ($meta['license'] ?? null),
    'encoding' => !empty($meta['citation_pdf_url']) ? [
        '@type' => 'MediaObject',
        'contentUrl' => $meta['citation_pdf_url'],
        'encodingFormat' => 'application/pdf',
    ] : null,
    'timeRequired' => !empty($meta['mins_read']) ? 'PT'.((int) $meta['mins_read']).'M' : null,
], fn ($v) => $v !== null && $v !== '' && $v !== []), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) !!}
</script>
