{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <sitemap>
        <loc>{{ route('sitemap.site') }}</loc>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
    </sitemap>
@foreach($journals as $journal)
    <sitemap>
        <loc>{{ route('sitemap.journal', $journal) }}</loc>
        <lastmod>{{ optional($journal->updated_at)->toAtomString() ?: now()->toAtomString() }}</lastmod>
    </sitemap>
@endforeach
</sitemapindex>
