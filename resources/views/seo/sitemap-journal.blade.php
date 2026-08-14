{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ route('journals.show', $journal) }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc>{{ route('journals.browse', $journal) }}</loc>
        <changefreq>daily</changefreq>
        <priority>0.85</priority>
    </url>
    <url>
        <loc>{{ route('journals.archive', $journal) }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
@foreach($issues ?? [] as $issue)
    <url>
        <loc>{{ route('journals.issues.show', [$journal, $issue]) }}</loc>
        <lastmod>{{ optional($issue->updated_at)->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.75</priority>
    </url>
@endforeach
@foreach($articles as $article)
    <url>
        <loc>{{ route('journals.articles.show', [$journal, $article]) }}</loc>
        <lastmod>{{ optional($article->updated_at)->toAtomString() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
@endforeach
</urlset>
