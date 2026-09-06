<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Journal;
use App\Models\MembershipPlan;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Notifications\PaymentReceiptNotification;
use App\Services\Payments\PaymentFulfillmentService;
use App\Services\Payments\PaymentReceiptService;
use App\Support\JournalActivation;
use App\Support\JournalTeamRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PaymentReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_payer_and_journal_manager_can_download_receipt(): void
    {
        $payer = User::factory()->create();
        $manager = User::factory()->create();
        $stranger = User::factory()->create();

        $journal = Journal::query()->create([
            'slug' => 'receipt-journal',
            'title' => 'Receipt Journal',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
        ]);
        $journal->assignTeamMember($manager, JournalTeamRoles::ADMIN);

        $tx = PaymentTransaction::query()->create([
            'user_id' => $payer->id,
            'reference' => 'RCP-REF-001',
            'payable_type' => Journal::class,
            'payable_id' => (string) $journal->id,
            'amount' => 50000,
            'currency' => 'NGN',
            'status' => 'success',
            'provider' => 'paystack',
            'paid_at' => now(),
        ]);

        $this->actingAs($payer)
            ->get(route('payments.receipt', $tx))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($manager)
            ->get(route('payments.receipt', $tx))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($stranger)
            ->get(route('payments.receipt', $tx))
            ->assertForbidden();
    }

    public function test_pending_payment_has_no_receipt(): void
    {
        $user = User::factory()->create();
        $tx = PaymentTransaction::query()->create([
            'user_id' => $user->id,
            'reference' => 'RCP-PENDING',
            'payable_type' => Journal::class,
            'payable_id' => '1',
            'amount' => 1000,
            'currency' => 'NGN',
            'status' => 'pending',
            'provider' => 'paystack',
        ]);

        $this->actingAs($user)
            ->get(route('payments.receipt', $tx))
            ->assertNotFound();
    }

    public function test_member_payments_page_lists_own_transactions(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        PaymentTransaction::query()->create([
            'user_id' => $user->id,
            'reference' => 'MINE-001',
            'payable_type' => MembershipPlan::class,
            'payable_id' => '9',
            'amount' => 15000,
            'currency' => 'NGN',
            'status' => 'success',
            'provider' => 'paystack',
            'paid_at' => now(),
        ]);

        PaymentTransaction::query()->create([
            'user_id' => $other->id,
            'reference' => 'THEIRS-001',
            'payable_type' => MembershipPlan::class,
            'payable_id' => '9',
            'amount' => 15000,
            'currency' => 'NGN',
            'status' => 'success',
            'provider' => 'paystack',
            'paid_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee('MINE-001', false)
            ->assertDontSee('THEIRS-001', false);
    }

    public function test_successful_fulfillment_emails_receipt_pdf(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $journal = Journal::query()->create([
            'slug' => 'pay-receipt-journal',
            'title' => 'Pay Receipt Journal',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_UNPAID,
        ]);
        $journal->assignTeamMember($user, JournalTeamRoles::ADMIN);

        $tx = PaymentTransaction::query()->create([
            'user_id' => $user->id,
            'reference' => 'FULFILL-RCP-1',
            'payable_type' => Journal::class,
            'payable_id' => (string) $journal->id,
            'amount' => 50000,
            'currency' => 'NGN',
            'status' => 'pending',
            'provider' => 'paystack',
        ]);

        app(PaymentFulfillmentService::class)->fulfillByReference('FULFILL-RCP-1', [
            'status' => 'success',
            'amount' => 50000 * 100,
        ]);

        Notification::assertSentTo($user, PaymentReceiptNotification::class, function (PaymentReceiptNotification $notification) use ($user, $tx) {
            $mail = $notification->toMail($user);
            $this->assertNotEmpty($mail->rawAttachments);

            return $notification->transaction->is($tx->fresh())
                && ($mail->rawAttachments[0]['options']['mime'] ?? null) === 'application/pdf';
        });

        $this->assertSame(JournalActivation::STATUS_ACTIVE, $journal->fresh()->activation_status);
    }

    public function test_billing_partial_filter_returns_json_html(): void
    {
        $admin = User::factory()->create();
        $journal = Journal::query()->create([
            'slug' => 'async-billing',
            'title' => 'Async Billing',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
        ]);
        $journal->assignTeamMember($admin, JournalTeamRoles::ADMIN);

        $article = Article::query()->create([
            'journal_id' => $journal->id,
            'slug' => 'async-article',
            'title' => 'Async Article',
            'visibility' => 'paid',
            'price_amount' => 2000,
            'currency' => 'NGN',
            'status' => 'published',
            'published_at' => now(),
        ]);

        PaymentTransaction::query()->create([
            'user_id' => $admin->id,
            'reference' => 'ASYNC-ACT',
            'payable_type' => Journal::class,
            'payable_id' => (string) $journal->id,
            'amount' => 50000,
            'currency' => 'NGN',
            'status' => 'success',
            'provider' => 'paystack',
            'paid_at' => now(),
        ]);

        PaymentTransaction::query()->create([
            'user_id' => $admin->id,
            'reference' => 'ASYNC-ART',
            'payable_type' => Article::class,
            'payable_id' => (string) $article->id,
            'amount' => 2000,
            'currency' => 'NGN',
            'status' => 'success',
            'provider' => 'paystack',
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)
            ->getJson(route('journal.manage.billing.index', [
                'journal' => $journal,
                'type' => 'activation',
                'partial' => 1,
            ]))
            ->assertOk()
            ->assertJsonStructure(['html', 'count', 'export_url'])
            ->assertJsonPath('count', 1)
            ->assertSee('ASYNC-ACT', false)
            ->assertDontSee('ASYNC-ART', false);
    }

    public function test_receipt_pdf_contains_reference(): void
    {
        $user = User::factory()->create(['name' => 'Payer Name', 'email' => 'payer@example.com']);
        $tx = PaymentTransaction::query()->create([
            'user_id' => $user->id,
            'reference' => 'PDF-REF-XYZ',
            'payable_type' => Journal::class,
            'payable_id' => '42',
            'amount' => 12000,
            'currency' => 'NGN',
            'status' => 'success',
            'provider' => 'paystack',
            'paid_at' => now(),
        ]);

        $binary = app(PaymentReceiptService::class)->pdfBinary($tx);

        $this->assertStringStartsWith('%PDF', $binary);
        $this->assertStringContainsString('PDF-REF-XYZ', $binary);
    }
}
