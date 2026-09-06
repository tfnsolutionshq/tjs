{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach([
    ['loc' => route('journals.show', $journal), 'changefreq' => 'weekly', 'priority' => '0.95'],
    ['loc' => route('journals.about', $journal), 'changefreq' => 'monthly', 'priority' => '0.7'],
    ['loc' => route('journals.archive', $journal), 'changefreq' => 'weekly', 'priority' => '0.75'],
    ['loc' => route('journals.browse', $journal), 'changefreq' => 'daily', 'priority' => '0.85'],
    ['loc' => route('journals.editorial-board', $journal), 'changefreq' => 'monthly', 'priority' => '0.55'],
    ['loc' => route('journals.reviewers', $journal), 'changefreq' => 'monthly', 'priority' => '0.5'],
    ['loc' => route('journals.announcements', $journal), 'changefreq' => 'weekly', 'priority' => '0.65'],
] as $page)
    <url>
        <loc>{{ $page['loc'] }}</loc>
        <lastmod>{{ optional($journal->updated_at)->toAtomString() }}</lastmod>
        <changefreq>{{ $page['changefreq'] }}</changefreq>
        <priority>{{ $page['priority'] }}</priority>
    </url>
@endforeach
@foreach($issues ?? [] as $issue)
    <url>
        <loc>{{ route('journals.issues.show', [$journal, $issue]) }}</loc>
        <lastmod>{{ optional($issue->updated_at)->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
@endforeach
@foreach($announcements ?? [] as $announcement)
    <url>
        <loc>{{ route('journals.announcements.show', [$journal, $announcement]) }}</loc>
        <lastmod>{{ optional($announcement->updated_at)->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
@endforeach
@foreach($articles as $article)
    <url>
        <loc>{{ route('journals.articles.show', [$journal, $article]) }}</loc>
        <lastmod>{{ optional($article->updated_at)->toAtomString() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.9</priority>
    </url>
    @if($article->visibility === 'open')
    <url>
        <loc>{{ route('journals.articles.pdf', [$journal, $article->slug]) }}</loc>
        <lastmod>{{ optional($article->updated_at)->toAtomString() }}</lastmod>
        <changefreq>yearly</changefreq>
        <priority>0.7</priority>
    </url>
    @endif
@endforeach
</urlset>
