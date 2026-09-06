<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\Membership;
use App\Models\Purchase;
use App\Models\User;
use App\Models\Volume;
use App\Services\Access\ArticleAccessResolver;
use App\Services\Payments\PaystackService;
use App\Services\Seo\ApaCitation;
use App\Services\Seo\ScholarlyMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class JournalAccessAndSeoTest extends TestCase
{
    use RefreshDatabase;

    private function seedArticle(string $visibility = 'open'): array
    {
        Storage::fake('local');

        $journal = Journal::create([
            'slug' => 'demo-journal',
            'title' => 'Demo Journal',
            'publisher' => 'TFN',
            'is_active' => true,
            'default_license' => 'CC BY 4.0',
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
            'slug' => 'sample-article',
            'title' => 'Sample Article Title',
            'abstract' => 'An abstract for testing scholarly metadata.',
            'visibility' => $visibility,
            'status' => 'published',
            'published_at' => now(),
            'page_range' => '10-20',
            'doi' => '10.1234/tjs.demo',
            'price_amount' => 5000,
            'currency' => 'NGN',
            'document_path' => 'journals/demo-journal/volumes/1/issues/1/articles/x/manuscript.pdf',
        ]);
        $article->authors()->create([
            'name' => 'Ada Lovelace',
            'sort_order' => 0,
            'is_corresponding' => true,
        ]);
        Storage::disk('local')->put($article->document_path, "%PDF-1.4\n%%EOF");

        return compact('journal', 'article');
    }

    public function test_open_article_html_contains_citation_meta(): void
    {
        ['journal' => $journal, 'article' => $article] = $this->seedArticle('open');

        $response = $this->get(route('journals.articles.show', [$journal, $article]));
        $response->assertOk();
        $response->assertSee('name="citation_title"', false);
        $response->assertSee('Sample Article Title');
        $response->assertSee('APA 7th');
    }

    public function test_open_pdf_is_publicly_streamable(): void
    {
        ['journal' => $journal, 'article' => $article] = $this->seedArticle('open');

        $response = $this->get(route('journals.articles.pdf', [$journal, $article->slug]));
        $response->assertOk();
    }

    public function test_paid_pdf_requires_purchase(): void
    {
        ['journal' => $journal, 'article' => $article] = $this->seedArticle('paid');
        $user = User::factory()->create(['role' => 'member']);

        $this->actingAs($user)
            ->get(route('journals.articles.pdf', [$journal, $article->slug]))
            ->assertForbidden();

        Purchase::create([
            'user_id' => $user->id,
            'article_id' => $article->id,
        ]);

        $this->actingAs($user)
            ->get(route('journals.articles.pdf', [$journal, $article->slug]))
            ->assertOk();
    }

    public function test_members_only_requires_active_membership(): void
    {
        ['journal' => $journal, 'article' => $article] = $this->seedArticle('members_only');
        $user = User::factory()->create(['role' => 'member']);
        $resolver = app(ArticleAccessResolver::class);

        $this->assertFalse($resolver->canAccessFullText($user, $article));

        Membership::create([
            'user_id' => $user->id,
            'scope' => 'platform',
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addYear(),
        ]);

        $this->assertTrue($resolver->canAccessFullText($user->fresh(), $article));
    }

    public function test_closed_article_hidden_from_guests(): void
    {
        ['journal' => $journal, 'article' => $article] = $this->seedArticle('closed');

        $this->get(route('journals.articles.show', [$journal, $article]))->assertNotFound();
    }

    public function test_apa_and_scholar_builders(): void
    {
        ['article' => $article] = $this->seedArticle('open');
        $apa = app(ApaCitation::class)->build($article);
        $meta = app(ScholarlyMeta::class)->forArticle($article);

        $this->assertStringContainsString('Lovelace', $apa['reference']);
        $this->assertSame('Sample Article Title', $meta['citation_title']);
        $this->assertNotEmpty($meta['citation_pdf_url']);
    }

    public function test_paystack_webhook_signature_validation(): void
    {
        config([
            'paystack.secret_key' => 'sk_test_secret',
            'paystack.webhook_secret' => 'sk_test_secret',
        ]);
        $service = app(PaystackService::class);
        $body = '{"event":"charge.success"}';
        $sig = hash_hmac('sha512', $body, 'sk_test_secret');

        $this->assertTrue($service->isValidWebhookSignature($body, $sig));
        $this->assertFalse($service->isValidWebhookSignature($body, 'bad'));
    }

    public function test_sitemap_renders(): void
    {
        $this->seedArticle('open');
        $this->get(route('sitemap'))->assertOk();
    }

    public function test_platform_sitemap_is_not_empty(): void
    {
        ['journal' => $journal] = $this->seedArticle('open');

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('sitemap.site'), false)
            ->assertSee(route('sitemap.journal', $journal), false);

        $this->get(route('sitemap.site'))
            ->assertOk()
            ->assertSee(route('home'), false)
            ->assertSee(route('journals.index'), false);
    }

    public function test_journal_sitemap_lists_catalog_and_article_urls(): void
    {
        ['journal' => $journal, 'article' => $article] = $this->seedArticle('open');

        $this->get(route('sitemap.journal', $journal))
            ->assertOk()
            ->assertSee(route('journals.show', $journal), false)
            ->assertSee(route('journals.about', $journal), false)
            ->assertSee(route('journals.browse', $journal), false)
            ->assertSee(route('journals.articles.show', [$journal, $article]), false)
            ->assertSee(route('journals.articles.pdf', [$journal, $article->slug]), false);
    }

    public function test_journal_pages_include_indexable_metadata(): void
    {
        ['journal' => $journal, 'article' => $article] = $this->seedArticle('open');

        $this->get(route('journals.about', $journal))
            ->assertOk()
            ->assertSee('name="citation_journal_title"', false)
            ->assertSee('name="robots"', false)
            ->assertSee('Periodical', false);

        $this->get(route('journals.articles.show', [$journal, $article]))
            ->assertOk()
            ->assertSee('name="citation_title"', false)
            ->assertSee('name="citation_abstract_html_url"', false);
    }

    public function test_platform_and_journal_branding_and_indexing_split(): void
    {
        Storage::fake('public');

        ['journal' => $journal] = $this->seedArticle('open');
        $journal->update([
            'logo_path' => 'journals/demo-journal/branding/logo.png',
            'logo_disk' => 'public',
        ]);
        Storage::disk('public')->put('journals/demo-journal/branding/logo.png', 'fake-logo');

        $platformIcon = asset(config('tjs.brand_icon'));
        $journalIcon = $journal->fresh()->logoUrl();
        $this->assertNotNull($journalIcon);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('rel="icon" href="'.$platformIcon.'"', false)
            ->assertSee('content="index,follow', false)
            ->assertSee('application-name" content="'.config('tjs.full_name').'"', false)
            ->assertDontSee('rel="icon" href="'.$journalIcon.'"', false);

        $user = User::factory()->create(['role' => 'member']);
        $this->actingAs($user)
            ->get(route('memberships.index'))
            ->assertOk()
            ->assertSee('rel="icon" href="'.$platformIcon.'"', false)
            ->assertSee('content="noindex,nofollow"', false)
            ->assertSee('application-name" content="'.config('tjs.full_name').'"', false);

        $this->get(route('journals.show', $journal))
            ->assertOk()
            ->assertSee('rel="icon" href="'.$journalIcon.'"', false)
            ->assertSee('content="index,follow', false)
            ->assertSee('application-name" content="Demo Journal"', false)
            ->assertDontSee('rel="icon" href="'.$platformIcon.'"', false);

        auth()->logout();

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('rel="icon" href="'.$platformIcon.'"', false)
            ->assertSee('content="noindex,nofollow"', false);

        $this->get(route('journals.login', $journal))
            ->assertOk()
            ->assertSee('rel="icon" href="'.$journalIcon.'"', false)
            ->assertSee('content="noindex,nofollow"', false)
            ->assertSee('application-name" content="Demo Journal"', false);
    }
}
