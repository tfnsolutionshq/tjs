<?php

namespace App\Services\Seo;

use App\Models\Issue;
use App\Models\Journal;
use App\Models\JournalAnnouncement;
use App\Support\Licenses;
use Illuminate\Support\Str;

class PageMeta
{
    /**
     * @return array<string, mixed>
     */
    public function site(string $title, ?string $description, string $url, ?string $image = null): array
    {
        $image ??= asset('images/tfns-logo.jpeg');
        $description = filled($description)
            ? (string) $description
            : (string) (config('tjs.pitch')
                ?: config('tjs.tagline')
                ?: ('Modernize your journal publishing process with '.config('tjs.full_name').'.'));

        return $this->pack(
            title: $title,
            description: $description,
            url: $url,
            image: $image,
            siteName: config('tjs.full_name'),
            type: 'website',
            extra: [
                'publisher' => config('tjs.publisher'),
            ],
            jsonLd: [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => config('tjs.full_name'),
                'url' => $url,
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => config('tjs.organization') ?: config('tjs.publisher'),
                ],
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function journal(Journal $journal, string $title, string $description, string $url, ?string $image = null): array
    {
        $image ??= $journal->headerImageUrl() ?: $journal->logoUrl();
        $issn = $journal->issn;
        $license = Licenses::normalize($journal->default_license) ?: $journal->default_license;

        return $this->pack(
            title: $title,
            description: $description,
            url: $url,
            image: $image,
            siteName: $journal->title,
            type: 'website',
            extra: [
                'citation_journal_title' => $journal->title,
                'citation_issn' => $issn,
                'citation_eissn' => $journal->eissn,
                'citation_publisher' => $journal->publisher ?: config('tjs.publisher'),
                'citation_language' => $journal->language ?: config('tjs.default_language', 'en'),
                'publisher' => $journal->publisher ?: config('tjs.publisher'),
                'issn' => $issn,
                'eissn' => $journal->eissn,
                'license' => $license,
                'license_url' => Licenses::url($license),
            ],
            jsonLd: array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'Periodical',
                'name' => $journal->title,
                'description' => $description,
                'url' => $url,
                'issn' => $issn,
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => $journal->publisher ?: config('tjs.publisher'),
                ],
                'inLanguage' => $journal->language ?: 'en',
                'license' => Licenses::url($license),
                'image' => $image,
            ]),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function issue(Journal $journal, Issue $issue): array
    {
        $issue->loadMissing('volume');
        $url = route('journals.issues.show', [$journal, $issue]);
        $description = $issue->title
            ?: ('Articles in '.$issue->label().' of '.$journal->title);
        $image = $issue->coverUrl() ?: $journal->headerImageUrl() ?: $journal->logoUrl();
        $base = $this->journal($journal, $issue->label().' | '.$journal->title, $description, $url, $image);

        $base['citation_volume'] = $issue->volume?->volume_number ? (string) $issue->volume->volume_number : null;
        $base['citation_issue'] = (string) $issue->issue_number;
        $base['json_ld'] = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'PublicationIssue',
            'name' => $issue->label(),
            'issueNumber' => (string) $issue->issue_number,
            'image' => $image,
            'url' => $url,
            'description' => $description,
            'isPartOf' => [
                '@type' => 'PublicationVolume',
                'volumeNumber' => (string) $issue->volume?->volume_number,
                'isPartOf' => [
                    '@type' => 'Periodical',
                    'name' => $journal->title,
                    'issn' => $issue->volume?->issn ?: $journal->issn,
                ],
            ],
        ]);

        return $base;
    }

    /**
     * @return array<string, mixed>
     */
    public function announcement(Journal $journal, JournalAnnouncement $announcement): array
    {
        $url = route('journals.announcements.show', [$journal, $announcement]);
        $description = $announcement->summary
            ?: Str::limit(strip_tags((string) $announcement->body), 160);

        return $this->journal(
            $journal,
            $announcement->title.' | '.$journal->title,
            $description ?: $journal->title,
            $url,
        );
    }

    /**
     * @param  array<string, mixed>  $extra
     * @param  array<string, mixed>  $jsonLd
     * @return array<string, mixed>
     */
    private function pack(
        string $title,
        string $description,
        string $url,
        ?string $image,
        string $siteName,
        string $type,
        array $extra,
        array $jsonLd,
    ): array {
        $description = trim(strip_tags($description));

        return array_filter([
            'title' => $title,
            'description' => $description,
            'canonical' => $url,
            'robots' => 'index,follow,max-image-preview:large',
            'og_title' => $title,
            'og_description' => $description,
            'og_type' => $type,
            'og_url' => $url,
            'og_image' => $image,
            'og_site_name' => $siteName,
            'twitter_card' => $image ? 'summary_large_image' : 'summary',
            'twitter_title' => $title,
            'twitter_description' => $description,
            'twitter_image' => $image,
            'json_ld' => $jsonLd,
            ...$extra,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
