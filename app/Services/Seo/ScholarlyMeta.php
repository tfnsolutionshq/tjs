<?php

namespace App\Services\Seo;

use App\Models\Article;
use App\Support\Licenses;
use Illuminate\Support\Str;

class ScholarlyMeta
{
    public function forArticle(Article $article): array
    {
        $article->loadMissing(['authors', 'journal', 'issue.volume', 'authorUser', 'categories']);
        $journal = $article->journal;
        $volume = $article->issue?->volume;
        $issue = $article->issue;

        $authors = $article->authors->map(function ($author) {
            return [
                'name' => $author->name,
                'affiliation' => $author->affiliation,
                'orcid' => $author->orcid,
                'email' => $author->email,
                'corresponding' => (bool) $author->is_corresponding,
            ];
        })->values()->all();

        if ($authors === [] && $article->authorUser) {
            $authors = [[
                'name' => $article->authorUser->name,
                'affiliation' => null,
                'orcid' => null,
                'email' => $article->authorUser->email,
                'corresponding' => true,
            ]];
        }

        $authorNames = array_values(array_filter(array_map(fn ($a) => $a['name'] ?? null, $authors)));

        [$first, $last] = $this->parsePages($article->page_range);
        $doi = $this->cleanDoi($article->doi);
        $published = optional($article->published_at)->format('Y/m/d');
        $publishedIso = optional($article->published_at)?->toAtomString();

        $pdfUrl = $article->visibility === 'open' ? $article->pdfUrl() : null;

        $fullAbstract = trim(strip_tags((string) $article->abstract));
        $description = $fullAbstract !== ''
            ? Str::limit($fullAbstract, 200)
            : 'Peer-reviewed research from '.config('tjs.full_name');

        $image = $this->shareImage($article);
        $keywords = $this->splitKeywords($article->keywords);
        $license = Licenses::normalize($article->license)
            ?: Licenses::normalize($journal?->default_license)
            ?: Licenses::normalize(config('tjs.default_license'))
            ?: ($article->license ?: $journal?->default_license ?: config('tjs.default_license'));
        $licenseUrl = Licenses::url($license);
        $publisher = $journal?->publisher ?: config('tjs.publisher');
        $language = $journal?->language ?: config('tjs.default_language', 'en');
        $canonical = $article->publicUrl();

        return [
            // Highwire / Google Scholar
            'citation_title' => $article->title,
            'citation_authors' => $authorNames,
            'citation_author_institutions' => array_values(array_filter(array_map(fn ($a) => $a['affiliation'] ?? null, $authors))),
            'citation_journal_title' => $journal?->title,
            'citation_journal_abbrev' => $journal?->slug ? Str::upper(Str::replace('-', ' ', $journal->slug)) : null,
            'citation_publisher' => $publisher,
            'citation_publication_date' => $published,
            'citation_online_date' => $published,
            'citation_volume' => $volume?->volume_number ? (string) $volume->volume_number : null,
            'citation_issue' => $issue?->issue_number ? (string) $issue->issue_number : null,
            'citation_issn' => $volume?->issn ?: $journal?->issn,
            'citation_eissn' => $journal?->eissn,
            'citation_doi' => $doi,
            'citation_firstpage' => $first,
            'citation_lastpage' => $last,
            'citation_language' => $language,
            'citation_abstract_html_url' => $canonical,
            'citation_fulltext_html_url' => $canonical,
            'citation_pdf_url' => $pdfUrl,
            'citation_keywords' => $keywords,
            'citation_article_type' => $article->categoryLabels() ?: 'research-article',

            // Dublin Core
            'dc.title' => $article->title,
            'dc.creator' => $authorNames,
            'dc.subject' => $keywords,
            'dc.description' => $fullAbstract !== '' ? Str::limit($fullAbstract, 500) : $description,
            'dc.publisher' => $publisher,
            'dc.contributor' => $journal?->title,
            'dc.date' => $publishedIso,
            'dc.type' => 'Text.Serial.Journal',
            'dc.format' => 'text/html',
            'dc.identifier' => $doi ? 'doi:'.$doi : $canonical,
            'dc.language' => $language,
            'dc.rights' => $license,
            'dc.relation.ispartof' => $journal?->title,
            'dc.source' => $journal?->title,

            // PRISM
            'prism.publicationName' => $journal?->title,
            'prism.issn' => $volume?->issn ?: $journal?->issn,
            'prism.eIssn' => $journal?->eissn,
            'prism.doi' => $doi,
            'prism.volume' => $volume?->volume_number ? (string) $volume->volume_number : null,
            'prism.number' => $issue?->issue_number ? (string) $issue->issue_number : null,
            'prism.startingPage' => $first,
            'prism.endingPage' => $last,
            'prism.publicationDate' => $publishedIso,
            'prism.url' => $canonical,
            'prism.copyright' => $license,

            // Open Graph / Twitter helpers
            'og_title' => $article->title,
            'og_description' => $description,
            'og_type' => 'article',
            'og_url' => $canonical,
            'og_image' => $image,
            'og_site_name' => $journal?->title ?: config('tjs.full_name'),
            'og_locale' => $this->ogLocale($language),
            'twitter_card' => $image ? 'summary_large_image' : 'summary',
            'twitter_title' => $article->title,
            'twitter_description' => $description,
            'twitter_image' => $image,

            // Misc
            'license' => $license,
            'license_url' => $licenseUrl,
            'doi' => $doi,
            'doi_url' => $doi ? 'https://doi.org/'.$doi : null,
            'canonical' => $canonical,
            'description' => $description,
            'abstract' => $fullAbstract !== '' ? $fullAbstract : null,
            'image' => $image,
            'authors_detailed' => $authors,
            'published_iso' => $publishedIso,
            'mins_read' => $article->mins_read,
            'visibility' => $article->visibility,
            'category' => $article->categoryLabels() ?: null,
            'page_range' => $article->page_range,
        ];
    }

    private function shareImage(Article $article): ?string
    {
        $issue = $article->issue;
        $volume = $issue?->volume;
        $journal = $article->journal;

        return $issue?->coverUrl()
            ?: $volume?->coverUrl()
            ?: $journal?->headerImageUrl()
            ?: $journal?->logoUrl();
    }

    private function ogLocale(?string $language): string
    {
        $language = strtolower((string) $language);
        if (str_contains($language, '_')) {
            return $language;
        }
        if (strlen($language) === 2) {
            return $language.'_'.strtoupper($language === 'en' ? 'US' : $language);
        }

        return 'en_US';
    }

    private function cleanDoi(?string $doi): ?string
    {
        if (! $doi) {
            return null;
        }

        return preg_replace('#^https?://(dx\.)?doi\.org/#i', '', trim($doi));
    }

    private function parsePages(?string $range): array
    {
        if (! $range) {
            return [null, null];
        }
        $cleaned = preg_replace('/^pp?\.\s*/i', '', trim($range));
        $parts = preg_split('/\s*[-–—]\s*/', $cleaned);

        return [$parts[0] ?? null, $parts[1] ?? null];
    }

    private function splitKeywords(?string $keywords): array
    {
        if (! $keywords) {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/[,;]+/', $keywords))));
    }
}
