<?php

namespace Tests\Feature;

use App\Models\Journal;
use App\Models\JournalAnnouncement;
use App\Models\JournalFee;
use App\Models\Submission;
use App\Models\User;
use App\Services\Payments\PaymentFulfillmentService;
use App\Support\JournalActivation;
use App\Support\JournalFeePurpose;
use App\Support\JournalTeamRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class JournalFeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_submission_fee_payment_moves_submission_to_submitted(): void
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
                    'authorization_url' => 'https://paystack.test/pay/sub-fee',
                    'access_code' => 'ac_sub',
                    'reference' => 'SUB-FEE-REF',
                ],
            ], 200),
        ]);

        $journal = Journal::query()->create([
            'slug' => 'fee-journal',
            'title' => 'Fee Journal',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
            'paystack_split_code' => 'SPL_testfee',
        ]);

        $fee = JournalFee::query()->create([
            'journal_id' => $journal->id,
            'name' => 'Standard submission',
            'purpose' => JournalFeePurpose::SUBMISSION,
            'amount' => 5000,
            'currency' => 'NGN',
            'is_active' => true,
        ]);

        $author = User::factory()->create();

        $announcement = JournalAnnouncement::query()->create([
            'journal_id' => $journal->id,
            'title' => 'Open call',
            'body' => 'Submit now',
            'type' => 'call_for_submissions',
            'is_published' => true,
            'closes_at' => now()->addWeek(),
        ]);

        $submission = Submission::query()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'journal_id' => $journal->id,
            'announcement_id' => $announcement->id,
            'journal_fee_id' => $fee->id,
            'author_id' => $author->id,
            'title' => 'Paid submission',
            'status' => 'fee_pending',
            'document_path' => 'submissions/test.docx',
            'document_disk' => 'local',
            'review_type' => 'single_blind',
        ]);

        $result = app(PaymentFulfillmentService::class)->startSubmissionFeePayment($author, $submission);

        $this->assertSame('https://paystack.test/pay/sub-fee', $result['authorization_url']);

        $reference = $result['reference'];
        app(PaymentFulfillmentService::class)->fulfillByReference($reference, [
            'status' => 'success',
            'amount' => 500000,
        ]);

        $submission->refresh();
        $this->assertSame('submitted', $submission->status);
        $this->assertNotNull($submission->fee_payment_transaction_id);
    }

    public function test_journal_manager_can_create_fee(): void
    {
        $journal = Journal::query()->create([
            'slug' => 'mgr-fees',
            'title' => 'Mgr Fees',
            'is_active' => true,
            'activation_status' => JournalActivation::STATUS_ACTIVE,
        ]);

        $manager = User::factory()->create();
        $journal->users()->attach($manager->id, ['role' => JournalTeamRoles::ADMIN]);

        $this->actingAs($manager)
            ->post(route('journal.manage.fees.store', $journal), [
                'name' => 'Reader membership',
                'purpose' => JournalFeePurpose::MEMBERSHIP,
                'amount' => 12000,
                'currency' => 'NGN',
                'is_active' => '1',
            ])
            ->assertRedirect(route('journal.manage.fees.index', $journal));

        $this->assertDatabaseHas('journal_fees', [
            'journal_id' => $journal->id,
            'name' => 'Reader membership',
            'purpose' => JournalFeePurpose::MEMBERSHIP,
            'amount' => 12000,
        ]);
    }
}
