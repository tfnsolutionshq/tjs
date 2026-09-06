{{--
  Head tags for authenticated / auth portals.
  Pass brandJournal explicitly for journal-scoped pages (manage / journal login).
  Omitting it keeps platform branding even if the page loops over $journal.
--}}
@php
    $brandJournal = ($brandJournal ?? null) instanceof \App\Models\Journal ? $brandJournal : null;
    $indexable = (bool) ($indexable ?? false);
    $siteName = $brandJournal?->title ?: config('tjs.full_name');
    $description = $description
        ?? ($brandJournal
            ? ('Private area for '.$brandJournal->title.' on '.config('tjs.name').'.')
            : (config('tjs.organization').' — member and publishing portal.'));
    $robots = $indexable ? 'index,follow,max-image-preview:large' : 'noindex,nofollow';
@endphp
<meta name="description" content="{{ $description }}">
<meta name="robots" content="{{ $robots }}">
<meta name="application-name" content="{{ $siteName }}">
<meta name="apple-mobile-web-app-title" content="{{ $siteName }}">
<meta property="og:site_name" content="{{ $siteName }}">
@include('seo.brand-icons', ['brandJournal' => $brandJournal])
@if($brandJournal)
    <link rel="sitemap" type="application/xml" title="{{ $brandJournal->title }}" href="{{ route('sitemap.journal', $brandJournal) }}">
@else
    <link rel="sitemap" type="application/xml" title="{{ config('tjs.full_name') }}" href="{{ route('sitemap') }}">
@endif
