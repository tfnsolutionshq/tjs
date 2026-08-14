{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($journals as $journal)
    <sitemap>
        <loc>{{ route('sitemap.journal', $journal) }}</loc>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
    </sitemap>
@endforeach
</sitemapindex>
