{{-- Indexable metadata for catalog / journal pages (not only PDFs) --}}
<meta name="robots" content="{{ $meta['robots'] ?? 'index,follow,max-image-preview:large' }}">
@if(!empty($meta['description']))
    <meta name="description" content="{{ $meta['description'] }}">
@endif
@if(!empty($meta['citation_journal_title']))
    <meta name="citation_journal_title" content="{{ $meta['citation_journal_title'] }}">
@endif
@if(!empty($meta['citation_issn']))
    <meta name="citation_issn" content="{{ $meta['citation_issn'] }}">
@endif
@if(!empty($meta['citation_eissn']))
    <meta name="citation_eissn" content="{{ $meta['citation_eissn'] }}">
@endif
@if(!empty($meta['citation_publisher']))
    <meta name="citation_publisher" content="{{ $meta['citation_publisher'] }}">
@endif
@if(!empty($meta['citation_language']))
    <meta name="citation_language" content="{{ $meta['citation_language'] }}">
@endif
@if(!empty($meta['citation_volume']))
    <meta name="citation_volume" content="{{ $meta['citation_volume'] }}">
@endif
@if(!empty($meta['citation_issue']))
    <meta name="citation_issue" content="{{ $meta['citation_issue'] }}">
@endif
@if(!empty($meta['issn']))
    <meta name="dc.identifier" content="ISSN {{ $meta['issn'] }}">
@endif
@if(!empty($meta['publisher']))
    <meta name="dc.publisher" content="{{ $meta['publisher'] }}">
@endif
@if(!empty($meta['license']))
    <meta name="license" content="{{ $meta['license'] }}">
    @if(!empty($meta['license_url']))
        <link rel="license" href="{{ $meta['license_url'] }}">
    @endif
@endif

<meta property="og:title" content="{{ $meta['og_title'] ?? $meta['title'] ?? '' }}">
<meta property="og:description" content="{{ $meta['og_description'] ?? ($meta['description'] ?? '') }}">
<meta property="og:type" content="{{ $meta['og_type'] ?? 'website' }}">
<meta property="og:url" content="{{ $meta['og_url'] ?? ($meta['canonical'] ?? url()->current()) }}">
<meta property="og:site_name" content="{{ $meta['og_site_name'] ?? config('tjs.full_name') }}">
@if(!empty($meta['og_image']))
    <meta property="og:image" content="{{ $meta['og_image'] }}">
@endif

<meta name="twitter:card" content="{{ $meta['twitter_card'] ?? 'summary' }}">
<meta name="twitter:title" content="{{ $meta['twitter_title'] ?? ($meta['title'] ?? '') }}">
<meta name="twitter:description" content="{{ $meta['twitter_description'] ?? ($meta['description'] ?? '') }}">
@if(!empty($meta['twitter_image']))
    <meta name="twitter:image" content="{{ $meta['twitter_image'] }}">
@endif

@if(!empty($meta['json_ld']))
    <script type="application/ld+json">{!! json_encode($meta['json_ld'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endif
