<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Journal;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Services\Payments\JournalPaymentGatewayResolver;
use App\Services\Payments\PaymentGatewayContext;
use App\Services\Payments\PaymentFulfillmentService;
use App\Support\JournalActivation;
use App\Support\JournalTeamRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use App\Services\Payments\JournalPaymentUnavailableException;
use Tests\TestCase;

class JournalPaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolver_prefers_personal_then_falls_back_to_split_when_disabled(): void
    {
        config(['paystack.secret_key' => 'sk_platform', 'paystack.public_key' => 'pk_platform']);

        $journal = Journal::query()->create([
            'slug' => 'gw-journal',
            'title' => 'GW Journal',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
            'payment_gateway_preference' => 'personal',
            'paystack_public_key' => 'pk_journal',
            'paystack_secret_key' => 'sk_journal',
            'paystack_split_code' => 'SPL_split',
            'personal_gateway_allowed' => true,
        ]);

        $resolver = app(JournalPaymentGatewayResolver::class);
        $this->assertSame(PaymentGatewayContext::MODE_PERSONAL, $resolver->forJournalIncome($journal)->mode);

        $journal->update(['personal_gateway_allowed' => false]);
        $journal->refresh();

        $split = $resolver->forJournalIncome($journal);
        $this->assertSame(PaymentGatewayContext::MODE_SPLIT, $split->mode);
        $this->assertSame('SPL_split', $split->splitCode);
    }

    public function test_resolver_blocks_when_personal_disabled_and_no_split(): void
    {
        config(['paystack.secret_key' => 'sk_platform', 'paystack.public_key' => 'pk_platform']);

        $journal = Journal::query()->create([
            'slug' => 'blocked-gw',
            'title' => 'Blocked GW',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
            'payment_gateway_preference' => 'personal',
            'paystack_public_key' => 'pk_journal',
            'paystack_secret_key' => 'sk_journal',
            'personal_gateway_allowed' => false,
        ]);

        $this->expectException(JournalPaymentUnavailableException::class);
        app(JournalPaymentGatewayResolver::class)->forJournalIncome($journal);
    }

    public function test_article_purchase_uses_paystack_split_code(): void
    {
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
                    'access_code' => 'access',
                    'reference' => 'ignored',
                ],
            ], 200),
        ]);

        $buyer = User::factory()->create(['email' => 'buyer@example.com']);
        $journal = Journal::query()->create([
            'slug' => 'split-sales',
            'title' => 'Split Sales',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
            'payment_gateway_preference' => 'split',
            'paystack_split_code' => 'SPL_abc',
            'personal_gateway_allowed' => true,
        ]);

        $article = Article::query()->create([
            'journal_id' => $journal->id,
            'slug' => 'paid-paper',
            'title' => 'Paid Paper',
            'visibility' => 'paid',
            'price_amount' => 1000,
            'currency' => 'NGN',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $result = app(PaymentFulfillmentService::class)->startArticlePurchase($buyer, $article);
        $this->assertSame('split', $result['gateway_mode']);
        $this->assertSame('split', $result['transaction']->gateway_mode);
        $this->assertSame($journal->id, $result['transaction']->gateway_journal_id);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://api.paystack.co/transaction/initialize'
                && ($data['split_code'] ?? null) === 'SPL_abc'
                && ! array_key_exists('subaccount', $data)
                && ! array_key_exists('transaction_charge', $data);
        });
    }

    public function test_invalid_split_code_shows_user_friendly_membership_error(): void
    {
        config([
            'paystack.secret_key' => 'sk_platform',
            'paystack.public_key' => 'pk_platform',
            'paystack.base_url' => 'https://api.paystack.co',
            'paystack.currency' => 'NGN',
        ]);

        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => false,
                'message' => 'Invalid Split code.',
            ], 400),
        ]);

        $buyer = User::factory()->create(['email' => 'buyer@example.com']);
        $journal = Journal::query()->create([
            'slug' => 'bad-split',
            'title' => 'Bad Split Journal',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
            'payment_gateway_preference' => 'split',
            'paystack_split_code' => 'SPL_invalid',
        ]);

        $plan = MembershipPlan::query()->create([
            'journal_id' => $journal->id,
            'name' => 'Reader plan',
            'scope' => 'journal',
            'price_amount' => 10000,
            'currency' => 'NGN',
            'duration_days' => 365,
            'is_active' => true,
        ]);

        $this->actingAs($buyer)
            ->post(route('payments.memberships.buy', $plan))
            ->assertRedirect(route('memberships.index'))
            ->assertSessionHas('error', JournalPaymentUnavailableException::USER_MESSAGE);
    }

    public function test_journal_admin_can_save_gateway_preference(): void
    {
        config(['paystack.secret_key' => 'sk_platform']);

        $admin = User::factory()->create();
        $journal = Journal::query()->create([
            'slug' => 'cfg-gw',
            'title' => 'Cfg GW',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
        ]);
        $journal->assignTeamMember($admin, JournalTeamRoles::ADMIN);

        $this->actingAs($admin)
            ->put(route('journal.manage.payments.gateway.update', $journal), [
                'payment_gateway_preference' => 'personal',
                'paystack_public_key' => 'pk_test_journal',
                'paystack_secret_key' => 'sk_test_journal',
            ])
            ->assertRedirect(route('journal.manage.payments.gateway', $journal));

        $journal->refresh();
        $this->assertSame('personal', $journal->payment_gateway_preference);
        $this->assertSame('pk_test_journal', $journal->paystack_public_key);
        $this->assertSame('sk_test_journal', $journal->paystack_secret_key);
    }

    public function test_platform_admin_can_disable_personal_gateway(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $journal = Journal::query()->create([
            'slug' => 'pa-toggle',
            'title' => 'PA Toggle',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
            'personal_gateway_allowed' => true,
            'allow_platform_admin_edits' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.journals.update', $journal), [
                'title' => $journal->title,
                'slug' => $journal->slug,
                'review_type' => 'closed',
                'default_license' => config('tjs.default_license'),
                'language' => 'en',
                'is_active' => '1',
                'is_featured' => '0',
                'personal_gateway_allowed' => '0',
            ])
            ->assertRedirect();

        $this->assertFalse($journal->fresh()->personal_gateway_allowed);
    }

    public function test_gateway_page_loads_when_stored_paystack_keys_cannot_be_decrypted(): void
    {
        $admin = User::factory()->create();
        $journal = Journal::query()->create([
            'slug' => 'corrupt-gw',
            'title' => 'Corrupt GW',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
            'payment_gateway_preference' => 'personal',
        ]);
        $journal->assignTeamMember($admin, JournalTeamRoles::ADMIN);

        \Illuminate\Support\Facades\DB::table('journals')->where('id', $journal->id)->update([
            'paystack_public_key' => 'eyJpdiI6ImJhZC1kYXRhIn0=',
            'paystack_secret_key' => 'eyJpdiI6ImJhZC1kYXRhIn0=',
        ]);

        $this->actingAs($admin)
            ->get(route('journal.manage.payments.gateway', $journal))
            ->assertOk()
            ->assertSee('Stored Paystack keys could not be read', false);

        $this->assertFalse(app(JournalPaymentGatewayResolver::class)->hasPersonalKeys($journal->fresh()));
    }

    public function test_journal_admin_can_replace_undecryptable_paystack_keys(): void
    {
        $admin = User::factory()->create();
        $journal = Journal::query()->create([
            'slug' => 'replace-gw',
            'title' => 'Replace GW',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
            'payment_gateway_preference' => 'unset',
        ]);
        $journal->assignTeamMember($admin, JournalTeamRoles::ADMIN);

        \Illuminate\Support\Facades\DB::table('journals')->where('id', $journal->id)->update([
            'paystack_public_key' => 'eyJpdiI6ImJhZC1kYXRhIn0=',
            'paystack_secret_key' => 'eyJpdiI6ImJhZC1kYXRhIn0=',
        ]);

        $this->actingAs($admin)
            ->put(route('journal.manage.payments.gateway.update', $journal->fresh()), [
                'payment_gateway_preference' => 'personal',
                'paystack_public_key' => 'pk_test_replaced',
                'paystack_secret_key' => 'sk_test_replaced',
            ])
            ->assertRedirect(route('journal.manage.payments.gateway', $journal));

        $journal->refresh();
        $this->assertSame('personal', $journal->payment_gateway_preference);
        $this->assertSame('pk_test_replaced', $journal->paystack_public_key);
        $this->assertSame('sk_test_replaced', $journal->paystack_secret_key);
    }
}
