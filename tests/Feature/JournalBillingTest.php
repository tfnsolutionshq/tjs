<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Journal;
use App\Models\MembershipPlan;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Support\JournalActivation;
use App\Support\JournalTeamRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_page_lists_activation_and_income_transactions(): void
    {
        $admin = User::factory()->create(['name' => 'Journal Admin', 'email' => 'admin@example.com']);
        $buyer = User::factory()->create(['name' => 'Article Buyer', 'email' => 'buyer@example.com']);

        $journal = Journal::query()->create([
            'slug' => 'billing-journal',
            'title' => 'Billing Journal',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
            'activation_paid_at' => now()->subDay(),
            'activation_expires_at' => now()->addYear(),
        ]);
        $journal->assignTeamMember($admin, JournalTeamRoles::ADMIN);

        $article = Article::query()->create([
            'journal_id' => $journal->id,
            'slug' => 'sold-paper',
            'title' => 'Sold Paper',
            'visibility' => 'paid',
            'price_amount' => 2500,
            'currency' => 'NGN',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $plan = MembershipPlan::query()->create([
            'journal_id' => $journal->id,
            'name' => 'Annual reader',
            'scope' => 'journal',
            'price_amount' => 10000,
            'currency' => 'NGN',
            'duration_days' => 365,
            'is_active' => true,
        ]);

        PaymentTransaction::query()->create([
            'user_id' => $admin->id,
            'reference' => 'ACT-REF-001',
            'payable_type' => Journal::class,
            'payable_id' => (string) $journal->id,
            'amount' => 50000,
            'currency' => 'NGN',
            'status' => 'success',
            'provider' => 'paystack',
            'paid_at' => now()->subDay(),
        ]);

        PaymentTransaction::query()->create([
            'user_id' => $buyer->id,
            'reference' => 'ART-REF-001',
            'payable_type' => Article::class,
            'payable_id' => (string) $article->id,
            'amount' => 2500,
            'currency' => 'NGN',
            'status' => 'success',
            'provider' => 'paystack',
            'paid_at' => now(),
        ]);

        PaymentTransaction::query()->create([
            'user_id' => $buyer->id,
            'reference' => 'MEM-REF-001',
            'payable_type' => MembershipPlan::class,
            'payable_id' => (string) $plan->id,
            'amount' => 10000,
            'currency' => 'NGN',
            'status' => 'pending',
            'provider' => 'paystack',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('journal.manage.billing.index', $journal))
            ->assertOk()
            ->assertSee('Payments &amp; Income', false)
            ->assertSee('ACT-REF-001', false)
            ->assertSee('ART-REF-001', false)
            ->assertSee('MEM-REF-001', false)
            ->assertSee('Sold Paper', false)
            ->assertSee('Annual reader', false)
            ->assertSee('2,500', false) // income success only (article)
            ->assertSee('50,000', false); // activation spent

        $this->actingAs($admin)
            ->get(route('journal.manage.billing.index', [
                'journal' => $journal,
                'type' => 'activation',
            ]))
            ->assertOk()
            ->assertSee('ACT-REF-001', false)
            ->assertDontSee('ART-REF-001', false);

        $this->actingAs($admin)
            ->get(route('journal.manage.billing.index', [
                'journal' => $journal,
                'q' => 'buyer@example.com',
            ]))
            ->assertOk()
            ->assertSee('ART-REF-001', false)
            ->assertDontSee('ACT-REF-001', false);

        $export = $this->actingAs($admin)
            ->get(route('journal.manage.billing.export', [
                'journal' => $journal,
                'direction' => 'in',
            ]));

        $export->assertOk()->assertHeader('content-disposition');
        $csv = $export->streamedContent();
        $this->assertStringContainsString('ART-REF-001', $csv);
        $this->assertStringContainsString('MEM-REF-001', $csv);
        $this->assertStringNotContainsString('ACT-REF-001', $csv);
    }

    public function test_billing_remains_available_when_activation_is_locked(): void
    {
        $user = User::factory()->create();
        $journal = Journal::query()->create([
            'slug' => 'locked-billing',
            'title' => 'Locked Billing',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_UNPAID,
        ]);
        $journal->assignTeamMember($user, JournalTeamRoles::ADMIN);

        $this->actingAs($user)
            ->get(route('journal.manage.billing.index', $journal))
            ->assertOk()
            ->assertSee('Payments &amp; Income', false);

        $this->actingAs($user)
            ->get(route('journal.manage.billing.export', $journal))
            ->assertOk();
    }
}
