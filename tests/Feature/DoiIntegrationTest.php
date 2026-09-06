<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Journal;
use App\Models\User;
use App\Notifications\DoiCreditsLowNotification;
use App\Services\Doi\DoiCreditService;
use App\Services\Doi\DoiDepositService;
use App\Support\JournalActivation;
use App\Support\JournalTeamRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DoiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'tjs.doi.enabled' => true,
            'tjs.doi.usd_to_ngn' => 2000,
            'tjs.doi.credit_price_usd' => 1,
            'tjs.doi.threshold_absolute' => 20,
            'tjs.doi.threshold_percent' => 10,
            'tjs.doi.platform_prefix' => '10.0000/tjs',
            'tjs.doi.crossref.username' => 'test_platform',
            'tjs.doi.crossref.password' => 'secret',
            'tjs.doi.crossref.deposit_url' => 'https://doi.crossref.org/servlet/deposit',
            'tjs.doi.crossref.depositor_name' => 'TJS',
            'tjs.doi.crossref.depositor_email' => 'doi@example.com',
            'tjs.doi.crossref.registrant' => 'TJS',
        ]);
    }

    public function test_admin_can_top_up_doi_credits(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $journal = Journal::query()->create([
            'slug' => 'doi-journal',
            'title' => 'DOI Journal',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.doi.topup', $journal), [
                'credits' => 50,
                'note' => 'Crossref prepaid',
            ])
            ->assertRedirect(route('admin.doi.index'));

        $journal->refresh();
        $this->assertSame(50, (int) $journal->doi_credits_balance);
        $this->assertSame(50, (int) $journal->doi_credits_lifetime);
    }

    public function test_platform_deposit_consumes_credit_and_sets_doi(): void
    {
        $manager = User::factory()->create();
        $journal = Journal::query()->create([
            'slug' => 'deposit-journal',
            'title' => 'Deposit Journal',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
            'doi_mode' => 'platform',
            'doi_credits_balance' => 5,
            'doi_credits_lifetime' => 5,
        ]);
        $journal->assignTeamMember($manager, JournalTeamRoles::ADMIN);

        $article = Article::query()->create([
            'journal_id' => $journal->id,
            'slug' => 'paper-one',
            'title' => 'Paper One',
            'visibility' => 'open',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $deposit = app(DoiDepositService::class)->depositArticle($article, $manager);

        $this->assertSame('success', $deposit->status);
        $this->assertSame(1, (int) $deposit->credits_spent);
        $this->assertNotNull($article->fresh()->doi);
        $this->assertSame('deposited', $article->fresh()->doi_deposit_status);
        $this->assertSame(4, (int) $journal->fresh()->doi_credits_balance);
    }

    public function test_exhausted_credits_block_platform_deposit_and_notify(): void
    {
        Notification::fake();

        $manager = User::factory()->create();
        $journal = Journal::query()->create([
            'slug' => 'empty-doi',
            'title' => 'Empty DOI',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
            'doi_mode' => 'platform',
            'doi_credits_balance' => 1,
            'doi_credits_lifetime' => 100,
        ]);
        $journal->assignTeamMember($manager, JournalTeamRoles::ADMIN);

        $article = Article::query()->create([
            'journal_id' => $journal->id,
            'slug' => 'last-credit',
            'title' => 'Last Credit',
            'visibility' => 'open',
            'status' => 'published',
            'published_at' => now(),
        ]);

        app(DoiDepositService::class)->depositArticle($article, $manager);
        $this->assertSame(0, (int) $journal->fresh()->doi_credits_balance);
        Notification::assertSentTo($manager, DoiCreditsLowNotification::class);

        $article2 = Article::query()->create([
            'journal_id' => $journal->id,
            'slug' => 'blocked',
            'title' => 'Blocked',
            'visibility' => 'open',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->expectException(\RuntimeException::class);
        app(DoiDepositService::class)->depositArticle($article2, $manager);
    }

    public function test_low_balance_threshold_notifies_at_20_or_10_percent(): void
    {
        Notification::fake();

        $manager = User::factory()->create();
        $journal = Journal::query()->create([
            'slug' => 'low-doi',
            'title' => 'Low DOI',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
            'doi_mode' => 'platform',
            'doi_credits_balance' => 25,
            'doi_credits_lifetime' => 200,
        ]);
        $journal->assignTeamMember($manager, JournalTeamRoles::ADMIN);

        // 10% of 200 = 20; dropping to 20 should notify
        app(DoiCreditService::class)->consume($journal, 5);

        Notification::assertSentTo($manager, DoiCreditsLowNotification::class);
        $this->assertSame(20, (int) $journal->fresh()->doi_credits_balance);
    }

    public function test_journal_can_configure_own_crossref_mode(): void
    {
        $manager = User::factory()->create();
        $journal = Journal::query()->create([
            'slug' => 'own-doi',
            'title' => 'Own DOI',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
        ]);
        $journal->assignTeamMember($manager, JournalTeamRoles::ADMIN);

        $this->actingAs($manager)
            ->put(route('journal.manage.doi.settings', $journal), [
                'doi_mode' => 'own',
                'doi_prefix' => '10.9999/own',
                'crossref_username' => 'test_journal',
                'crossref_password' => 'role-pass',
                'doi_auto_deposit' => '1',
            ])
            ->assertRedirect(route('journal.manage.doi.index', $journal));

        $journal->refresh();
        $this->assertSame('own', $journal->doi_mode);
        $this->assertSame('10.9999/own', $journal->doi_prefix);
        $this->assertTrue($journal->doi_auto_deposit);
    }

    public function test_exhausted_banner_message_for_managers(): void
    {
        $journal = Journal::query()->create([
            'slug' => 'banner-doi',
            'title' => 'Banner DOI',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
            'doi_mode' => 'platform',
            'doi_credits_balance' => 0,
            'doi_credits_lifetime' => 10,
        ]);

        $banner = app(DoiCreditService::class)->statusBanner($journal);
        $this->assertSame('exhausted', $banner['level']);
        $this->assertStringContainsString('paused', $banner['message']);
    }
}
