<?php

namespace Tests\Feature\Public;

use App\Models\Article;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\Volume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class HomePaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_paginates_journals(): void
    {
        foreach (range(1, 7) as $i) {
            Journal::create([
                'slug' => 'journal-'.$i,
                'title' => 'Listed Journal '.$i,
                'publisher' => 'TFN',
                'is_active' => true,
            ]);
        }

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Listed Journal 1')
            ->assertSee('Listed Journal 6')
            ->assertDontSee('Listed Journal 7')
            ->assertSee('jp-pagination', false);

        $this->get(route('home', ['journal_page' => 2]))
            ->assertOk()
            ->assertSee('Listed Journal 7')
            ->assertDontSee('Listed Journal 1');
    }

    public function test_home_paginates_articles(): void
    {
        ['journal' => $journal] = $this->seedPublicArticle('Article 1');

        foreach (range(2, 7) as $i) {
            Article::create([
                'id' => (string) Str::uuid(),
                'journal_id' => $journal->id,
                'issue_id' => $journal->issues()->firstOrFail()->id,
                'slug' => 'article-'.$i,
                'title' => 'Article '.$i,
                'abstract' => 'Abstract '.$i,
                'visibility' => 'open',
                'status' => 'published',
                'published_at' => now()->subDays($i),
            ]);
        }

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Article 1')
            ->assertSee('Article 6')
            ->assertDontSee('Article 7')
            ->assertSee('jp-pagination', false);

        $this->get(route('home', ['article_page' => 2]))
            ->assertOk()
            ->assertSee('Article 7')
            ->assertDontSee('Article 6');
    }

    public function test_home_pagination_preserves_other_section_page(): void
    {
        foreach (range(1, 7) as $i) {
            Journal::create([
                'slug' => 'journal-'.$i,
                'title' => 'Listed Journal '.$i,
                'publisher' => 'TFN',
                'is_active' => true,
            ]);
        }

        ['journal' => $journal] = $this->seedPublicArticle('Article 1');

        foreach (range(2, 7) as $i) {
            Article::create([
                'id' => (string) Str::uuid(),
                'journal_id' => $journal->id,
                'issue_id' => $journal->issues()->firstOrFail()->id,
                'slug' => 'article-'.$i,
                'title' => 'Article '.$i,
                'abstract' => 'Abstract '.$i,
                'visibility' => 'open',
                'status' => 'published',
                'published_at' => now()->subDays($i),
            ]);
        }

        $this->get(route('home', ['journal_page' => 2, 'article_page' => 2]))
            ->assertOk()
            ->assertSee('Listed Journal 7')
            ->assertSee('Article 7');
    }

    public function test_articles_index_lists_public_articles(): void
    {
        ['article' => $article] = $this->seedPublicArticle('Browseable Article');

        $this->get(route('articles.index'))
            ->assertOk()
            ->assertSee('Browseable Article')
            ->assertSee(route('journals.articles.show', [$article->journal, $article]), false);
    }

    /**
     * @return array{journal: Journal, article: Article}
     */
    private function seedPublicArticle(string $title): array
    {
        $journal = Journal::create([
            'slug' => 'demo-journal',
            'title' => 'Demo Journal',
            'publisher' => 'TFN',
            'is_active' => true,
        ]);
        $volume = Volume::create([
            'journal_id' => $journal->id,
            'volume_number' => 1,
            'year' => 2026,
            'status' => 'published',
        ]);
        $issue = Issue::create([
            'volume_id' => $volume->id,
            'issue_number' => 1,
            'status' => 'published',
        ]);
        $article = Article::create([
            'id' => (string) Str::uuid(),
            'journal_id' => $journal->id,
            'issue_id' => $issue->id,
            'slug' => Str::slug($title),
            'title' => $title,
            'abstract' => 'An abstract.',
            'visibility' => 'open',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $journal->setRelation('issues', collect([$issue]));

        return compact('journal', 'article');
    }
}
