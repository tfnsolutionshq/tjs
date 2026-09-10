<?php

namespace Tests\Feature\Api\V1;

use App\Models\Article;
use App\Models\Journal;
use App\Models\MembershipPlan;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\Payments\PaymentFulfillmentService;
use App\Support\JournalActivation;
use App\Support\JournalTeamRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'paystack.secret_key' => 'sk_platform',
            'paystack.public_key' => 'pk_platform',
            'paystack.base_url' => 'https://api.paystack.co',
            'paystack.currency' => 'NGN',
        ]);

        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://paystack.test/auth',
                    'access_code' => 'ACCESS123',
                    'reference' => 'IGNORED',
                ],
            ], 200),
        ]);
    }

    public function test_member_can_initialize_article_purchase(): void
    {
        $buyer = User::factory()->create(['email_verified_at' => now()]);
        $journal = Journal::query()->create([
            'slug' => 'paid-journal',
            'title' => 'Paid Journal',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
            'paystack_split_code' => 'SPL_article',
        ]);
        $article = Article::query()->create([
            'journal_id' => $journal->id,
            'slug' => 'paid-paper',
            'title' => 'Paid Paper',
            'visibility' => 'paid',
            'price_amount' => 1500,
            'currency' => 'NGN',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Sanctum::actingAs($buyer);

        $response = $this->postJson('/api/v1/me/payments/articles/'.$journal->slug.'/'.$article->slug.'/purchase')
            ->assertOk()
            ->assertJsonPath('data.authorization_url', 'https://paystack.test/auth')
            ->assertJsonPath('data.purpose', 'article_purchase')
            ->assertJsonPath('data.amount', 1500);

        $this->assertStringStartsWith('TJS_', $response->json('data.reference'));
    }

    public function test_member_can_initialize_membership_purchase(): void
    {
        $buyer = User::factory()->create(['email_verified_at' => now()]);
        $journal = Journal::query()->create([
            'slug' => 'member-journal',
            'title' => 'Member Journal',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
            'paystack_split_code' => 'SPL_member',
        ]);
        $plan = MembershipPlan::query()->create([
            'journal_id' => $journal->id,
            'name' => 'Reader plan',
            'scope' => 'journal',
            'price_amount' => 8000,
            'currency' => 'NGN',
            'duration_days' => 365,
            'is_active' => true,
        ]);

        Sanctum::actingAs($buyer);

        $this->postJson('/api/v1/me/payments/memberships/'.$plan->id.'/purchase')
            ->assertOk()
            ->assertJsonPath('data.purpose', 'membership')
            ->assertJsonPath('data.amount', 8000);
    }

    public function test_manager_can_initialize_journal_activation_purchase(): void
    {
        config(['tjs.journal_activation.enabled' => true, 'tjs.journal_activation.price' => 25000, 'tjs.currency' => 'NGN']);

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $journal = Journal::query()->create([
            'slug' => 'activate-journal',
            'title' => 'Activate Journal',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_UNPAID,
        ]);
        $journal->assignTeamMember($manager, JournalTeamRoles::ADMIN);

        Sanctum::actingAs($manager);

        $this->postJson('/api/v1/me/payments/journals/'.$journal->slug.'/activate')
            ->assertOk()
            ->assertJsonPath('data.purpose', 'journal_activation')
            ->assertJsonPath('data.amount', 25000);
    }

    public function test_member_can_list_and_verify_own_payment(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://paystack.test/auth',
                    'access_code' => 'ACCESS123',
                    'reference' => 'IGNORED',
                ],
            ], 200),
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'amount' => 150000,
                    'reference' => 'VERIFY_REF',
                ],
            ], 200),
        ]);

        $buyer = User::factory()->create(['email_verified_at' => now()]);
        $journal = Journal::query()->create([
            'slug' => 'verify-journal',
            'title' => 'Verify Journal',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
            'paystack_split_code' => 'SPL_verify',
        ]);
        $article = Article::query()->create([
            'journal_id' => $journal->id,
            'slug' => 'verify-paper',
            'title' => 'Verify Paper',
            'visibility' => 'paid',
            'price_amount' => 1500,
            'currency' => 'NGN',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Sanctum::actingAs($buyer);

        $init = app(PaymentFulfillmentService::class)->startArticlePurchase($buyer, $article);
        $reference = $init['reference'];

        $this->getJson('/api/v1/me/payments')
            ->assertOk()
            ->assertJsonPath('data.0.reference', $reference);

        $this->postJson('/api/v1/me/payments/verify', ['reference' => $reference])
            ->assertOk()
            ->assertJsonPath('data.transaction.status', 'success');

        $this->assertDatabaseHas('purchases', [
            'user_id' => $buyer->id,
            'article_id' => $article->id,
        ]);
    }

    public function test_member_cannot_view_another_users_payment(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $other = User::factory()->create(['email_verified_at' => now()]);

        $transaction = PaymentTransaction::query()->create([
            'user_id' => $owner->id,
            'reference' => 'TJS_other_ref',
            'payable_type' => Article::class,
            'payable_id' => '1',
            'amount' => 1000,
            'currency' => 'NGN',
            'status' => 'pending',
            'provider' => 'paystack',
        ]);

        Sanctum::actingAs($other);

        $this->getJson('/api/v1/me/payments/'.$transaction->id)->assertForbidden();
    }
}
