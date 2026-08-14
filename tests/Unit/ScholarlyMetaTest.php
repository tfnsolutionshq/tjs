<?php

namespace Tests\Unit;

use App\Models\Article;
use App\Models\Journal;
use App\Models\User;
use App\Services\Seo\ScholarlyMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ScholarlyMetaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_builds_rich_article_metadata(): void
    {
        $journal = Journal::query()->create([
            'slug' => 'demo-journal',
            'title' => 'Demo Journal',
            'issn' => '1234-5678',
            'publisher' => 'TFN',
            'is_active' => true,
            'logo_path' => null,
        ]);

        $article = Article::query()->create([
            'journal_id' => $journal->id,
            'slug' => 'demo-article',
            'title' => 'Demo Article Title',
            'abstract' => 'An abstract about open science and repositories.',
            'keywords' => 'open science, repositories',
            'doi' => '10.1234/demo.1',
            'visibility' => 'open',
            'status' => 'published',
            'published_at' => now(),
            'license' => 'CC BY 4.0',
            'page_range' => '10-20',
        ]);

        $article->authors()->create([
            'name' => 'Ada Lovelace',
            'affiliation' => 'Analytical Engine Lab',
            'role' => 'author',
            'sort_order' => 0,
        ]);

        $meta = (new ScholarlyMeta)->forArticle($article->fresh());

        $this->assertSame('Demo Article Title', $meta['citation_title']);
        $this->assertSame(['Ada Lovelace'], $meta['citation_authors']);
        $this->assertSame('10.1234/demo.1', $meta['doi']);
        $this->assertSame('10', $meta['citation_firstpage']);
        $this->assertSame('20', $meta['citation_lastpage']);
        $this->assertNotEmpty($meta['citation_pdf_url']);
        $this->assertSame('Demo Article Title', $meta['dc.title']);
        $this->assertSame('Demo Journal', $meta['prism.publicationName']);
        $this->assertArrayHasKey('og_title', $meta);
        $this->assertContains('open science', $meta['citation_keywords']);
    }
}
