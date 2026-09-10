<?php

namespace Tests\Feature\Api\V1;

use App\Models\Article;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\User;
use App\Models\Volume;
use App\Support\JournalActivation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_journals_index_lists_only_listed_journals(): void
    {
        Journal::query()->create([
            'slug' => 'listed-journal',
            'title' => 'Listed Journal',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
        ]);
        Journal::query()->create([
            'slug' => 'hidden-journal',
            'title' => 'Hidden Journal',
            'is_active' => false,
        ]);

        $this->getJson('/api/v1/journals')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'listed-journal')
            ->assertJsonMissing(['slug' => 'hidden-journal']);
    }

    public function test_journal_show_returns_detail(): void
    {
        $journal = Journal::query()->create([
            'slug' => 'demo-journal',
            'title' => 'Demo Journal',
            'subtitle' => 'Research for everyone',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
        ]);

        $this->getJson('/api/v1/journals/demo-journal')
            ->assertOk()
            ->assertJsonPath('data.slug', 'demo-journal')
            ->assertJsonPath('data.subtitle', 'Research for everyone')
            ->assertJsonStructure(['data' => ['urls' => ['web', 'browse', 'archive', 'about']]]);
    }

    public function test_platform_articles_index_lists_public_articles(): void
    {
        ['article' => $article] = $this->seedPublicArticle('Catalog Article');

        $this->getJson('/api/v1/articles')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Catalog Article')
            ->assertJsonStructure(['data' => [['access' => ['can_view_full_text', 'denial_reason']]]]);
    }

    public function test_journal_browse_supports_search_and_year_filters(): void
    {
        ['journal' => $journal, 'issue' => $issue] = $this->seedJournalWithIssue();

        Article::create([
            'id' => (string) Str::uuid(),
            'journal_id' => $journal->id,
            'issue_id' => $issue->id,
            'slug' => 'filter-me',
            'title' => 'Filter Me Article',
            'visibility' => 'open',
            'status' => 'published',
            'published_at' => '2025-03-01',
        ]);

        Article::create([
            'id' => (string) Str::uuid(),
            'journal_id' => $journal->id,
            'issue_id' => $issue->id,
            'slug' => 'other-year',
            'title' => 'Other Year Article',
            'visibility' => 'open',
            'status' => 'published',
            'published_at' => '2024-03-01',
        ]);

        $this->getJson('/api/v1/journals/'.$journal->slug.'/browse?q=Filter')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Filter Me Article')
            ->assertJsonMissing(['title' => 'Other Year Article']);

        $this->getJson('/api/v1/journals/'.$journal->slug.'/browse?year=2024')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Other Year Article');
    }

    public function test_article_show_includes_access_hints_for_guest_and_member(): void
    {
        ['journal' => $journal, 'article' => $article] = $this->seedPublicArticle('Paid Article', 'paid');

        $this->getJson('/api/v1/journals/'.$journal->slug.'/articles/'.$article->slug)
            ->assertOk()
            ->assertJsonPath('data.access.can_view_full_text', false)
            ->assertJsonPath('data.access.purchase_required', true)
            ->assertJsonStructure(['data' => ['citation' => ['apa'], 'urls' => ['web', 'pdf']]]);

        $member = User::factory()->create();
        Sanctum::actingAs($member);

        $this->getJson('/api/v1/journals/'.$journal->slug.'/articles/'.$article->slug)
            ->assertOk()
            ->assertJsonPath('data.access.purchase_required', true);
    }

    public function test_closed_article_is_not_found_in_api(): void
    {
        ['journal' => $journal, 'article' => $article] = $this->seedPublicArticle('Closed Article', 'closed');

        $this->getJson('/api/v1/journals/'.$journal->slug.'/articles/'.$article->slug)
            ->assertNotFound();
    }

    /**
     * @return array{journal: Journal, article: Article}
     */
    private function seedPublicArticle(string $title, string $visibility = 'open'): array
    {
        ['journal' => $journal, 'issue' => $issue] = $this->seedJournalWithIssue();

        $article = Article::create([
            'id' => (string) Str::uuid(),
            'journal_id' => $journal->id,
            'issue_id' => $issue->id,
            'slug' => Str::slug($title),
            'title' => $title,
            'abstract' => 'An abstract.',
            'visibility' => $visibility,
            'price_amount' => 2500,
            'currency' => 'NGN',
            'status' => 'published',
            'published_at' => now(),
        ]);

        return compact('journal', 'article');
    }

    /**
     * @return array{journal: Journal, issue: Issue}
     */
    private function seedJournalWithIssue(): array
    {
        $journal = Journal::query()->create([
            'slug' => 'demo-journal',
            'title' => 'Demo Journal',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
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

        return compact('journal', 'issue');
    }
}
