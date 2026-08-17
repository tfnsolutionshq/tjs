<?php

namespace Tests\Unit;

use App\Models\Article;
use App\Models\Journal;
use App\Support\Doi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DoiTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalize_strips_doi_url_prefix(): void
    {
        $this->assertSame('10.1234/example', Doi::normalize('https://doi.org/10.1234/example'));
        $this->assertSame('10.1234/example', Doi::normalize('doi:10.1234/example'));
        $this->assertNull(Doi::normalize('   '));
    }

    public function test_article_doi_must_be_unique(): void
    {
        $journal = Journal::query()->create([
            'slug' => 'demo-journal',
            'title' => 'Demo Journal',
            'is_active' => true,
        ]);

        Article::query()->create([
            'id' => (string) Str::uuid(),
            'journal_id' => $journal->id,
            'slug' => 'first-article',
            'title' => 'First',
            'doi' => '10.1234/unique.1',
            'status' => 'published',
            'visibility' => 'open',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Article::query()->create([
            'id' => (string) Str::uuid(),
            'journal_id' => $journal->id,
            'slug' => 'second-article',
            'title' => 'Second',
            'doi' => '10.1234/unique.1',
            'status' => 'published',
            'visibility' => 'open',
        ]);
    }
}
