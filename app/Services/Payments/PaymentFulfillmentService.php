<?php

namespace App\Services\Payments;

use App\Models\Article;
use App\Models\Journal;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\PaymentTransaction;
use App\Models\Purchase;
use App\Models\Submission;
use App\Models\SubmissionTimeline;
use App\Models\User;
use App\Services\Journal\SubmissionProductionService;
use App\Support\JournalActivation;
use App\Support\SubmissionStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PaymentFulfillmentService
{
    public function __construct(
        private PaystackService $paystack,
        private PaymentReceiptService $receipts,
        private JournalPaymentGatewayResolver $gateways,
        private SubmissionProductionService $production,
    ) {
    }

    public function startArticlePurchase(User $user, Article $article): array
    {
        if ($article->visibility !== 'paid') {
            throw new RuntimeException('This article is not available for purchase.');
        }
        if (! $article->price_amount || $article->price_amount < 1) {
            throw new RuntimeException('Article price is not configured.');
        }

        $existing = Purchase::query()
            ->where('user_id', $user->id)
            ->where('article_id', $article->id)
            ->whereNull('revoked_at')
            ->exists();
        if ($existing) {
            throw new RuntimeException('You already own this article.');
        }

        $article->loadMissing('journal');
        $journal = $article->journal;
        if (! $journal) {
            throw new RuntimeException('Article journal is missing.');
        }

        $gateway = $this->gateways->forJournalIncome($journal);

        return $this->startTransaction(
            $user,
            Article::class,
            $article->id,
            (int) $article->price_amount,
            $article->currency ?: config('tjs.currency'),
            [
                'purpose' => 'article_purchase',
                'article_id' => $article->id,
                'journal_id' => $journal->id,
                'gateway_mode' => $gateway->mode,
            ],
            route('payments.callback'),
            $gateway,
            $journal->id,
        );
    }

    public function startMembershipPurchase(User $user, MembershipPlan $plan): array
    {
        if (! $plan->is_active) {
            throw new RuntimeException('This membership plan is inactive.');
        }

        if ($plan->scope === 'platform' && ! config('tjs.membership.platform_enabled', true)) {
            throw new RuntimeException('Platform membership purchases are currently disabled by the platform administrator.');
        }

        if (app(\App\Services\Membership\MembershipCoverageService::class)->planIsCovered($user, $plan)) {
            throw new RuntimeException('You already have active membership that covers this plan.');
        }

        if ($plan->scope === 'journal') {
            $journal = $plan->journal ?: Journal::query()->find($plan->journal_id);
            if (! $journal) {
                throw new RuntimeException('Membership plan journal is missing.');
            }
            $gateway = $this->gateways->forJournalIncome($journal);
            $journalId = $journal->id;
        } else {
            $gateway = $this->gateways->platform();
            $journalId = null;
        }

        return $this->startTransaction(
            $user,
            MembershipPlan::class,
            (string) $plan->id,
            (int) $plan->price_amount,
            $plan->currency ?: config('tjs.currency'),
            [
                'purpose' => 'membership',
                'plan_id' => $plan->id,
                'gateway_mode' => $gateway->mode,
            ],
            route('payments.callback'),
            $gateway,
            $journalId,
        );
    }

    public function startSubmissionFeePayment(User $user, Submission $submission): array
    {
        if ((int) $submission->author_id !== (int) $user->id) {
            throw new RuntimeException('You do not own this submission.');
        }

        if ($submission->status !== 'fee_pending') {
            throw new RuntimeException('This submission does not require a fee payment.');
        }

        $submission->loadMissing(['journal', 'journalFee']);
        $fee = $submission->journalFee;
        if (! $fee || (int) $fee->amount < 1) {
            throw new RuntimeException('Submission fee is not configured.');
        }

        $journal = $submission->journal;
        if (! $journal) {
            throw new RuntimeException('Submission journal is missing.');
        }

        $gateway = $this->gateways->forJournalIncome($journal);

        return $this->startTransaction(
            $user,
            Submission::class,
            $submission->id,
            (int) $fee->amount,
            $fee->currency ?: config('tjs.currency'),
            [
                'purpose' => 'submission_fee',
                'submission_id' => $submission->id,
                'journal_id' => $journal->id,
                'journal_fee_id' => $fee->id,
                'gateway_mode' => $gateway->mode,
            ],
            route('payments.callback'),
            $gateway,
            $journal->id,
        );
    }

    public function startPublicationFeePayment(User $user, Submission $submission): array
    {
        if ((int) $submission->author_id !== (int) $user->id) {
            throw new RuntimeException('You do not own this submission.');
        }

        if ($submission->status !== 'publication_fee_pending') {
            throw new RuntimeException('This submission does not require a publication fee payment.');
        }

        $submission->loadMissing(['journal', 'publicationJournalFee']);
        $fee = $submission->publicationJournalFee;
        if (! $fee || (int) $fee->amount < 1) {
            throw new RuntimeException('Publication fee is not configured.');
        }

        $journal = $submission->journal;
        if (! $journal) {
            throw new RuntimeException('Submission journal is missing.');
        }

        $gateway = $this->gateways->forJournalIncome($journal);

        return $this->startTransaction(
            $user,
            Submission::class,
            $submission->id,
            (int) $fee->amount,
            $fee->currency ?: config('tjs.currency'),
            [
                'purpose' => 'publication_fee',
                'submission_id' => $submission->id,
                'journal_id' => $journal->id,
                'journal_fee_id' => $fee->id,
                'gateway_mode' => $gateway->mode,
            ],
            route('payments.callback'),
            $gateway,
            $journal->id,
        );
    }

    public function startJournalActivationPurchase(User $user, Journal $journal): array
    {
        if (! $user->canManageJournal($journal)) {
            throw new RuntimeException('You do not manage this journal.');
        }

        if (! JournalActivation::enabled()) {
            throw new RuntimeException('Journal activation payments are currently disabled by the platform administrator.');
        }

        if ($journal->isActivationCurrent()) {
            throw new RuntimeException('This journal already has an active activation. Renew after it expires.');
        }

        $amount = JournalActivation::price();
        if ($amount < 1) {
            throw new RuntimeException('Journal activation price is not configured.');
        }

        $gateway = $this->gateways->platform();

        return $this->startTransaction(
            $user,
            Journal::class,
            (string) $journal->id,
            $amount,
            JournalActivation::currency(),
            [
                'purpose' => 'journal_activation',
                'journal_id' => $journal->id,
                'journal_slug' => $journal->slug,
                'gateway_mode' => $gateway->mode,
            ],
            route('payments.callback'),
            $gateway,
            $journal->id,
        );
    }

    public function fulfillByReference(string $reference, ?array $providerPayload = null): PaymentTransaction
    {
        return DB::transaction(function () use ($reference, $providerPayload) {
            /** @var PaymentTransaction $tx */
            $tx = PaymentTransaction::query()
                ->with('gatewayJournal')
                ->where('reference', $reference)
                ->lockForUpdate()
                ->firstOrFail();

            if ($tx->status === 'success') {
                return $tx; // idempotent
            }

            $gateway = $this->gateways->contextFromTransaction($tx);
            $verified = $providerPayload ?: $this->paystack->verify($reference, $gateway);
            $status = $verified['status'] ?? null;
            $amountKobo = (int) ($verified['amount'] ?? 0);
            $expectedKobo = (int) $tx->amount * 100;

            if ($status !== 'success' || $amountKobo !== $expectedKobo) {
                $tx->status = 'failed';
                $tx->provider_payload = $verified;
                $tx->save();
                throw new RuntimeException('Payment verification failed.');
            }

            $tx->status = 'success';
            $tx->paid_at = now();
            $tx->provider_payload = $verified;
            $tx->save();

            $this->grantEntitlement($tx);

            $txId = $tx->id;
            $receipts = $this->receipts;
            DB::afterCommit(function () use ($txId, $receipts) {
                try {
                    $paid = PaymentTransaction::query()->find($txId);
                    if ($paid) {
                        $receipts->notifyPayer($paid);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Payment receipt email failed', [
                        'transaction_id' => $txId,
                        'error' => $e->getMessage(),
                    ]);
                }
            });

            return $tx;
        });
    }

    private function startTransaction(
        User $user,
        string $payableType,
        string $payableId,
        int $amount,
        string $currency,
        array $metadata,
        string $callbackUrl,
        PaymentGatewayContext $gateway,
        ?int $gatewayJournalId = null,
    ): array {
        $reference = $this->paystack->makeReference();

        $tx = PaymentTransaction::create([
            'user_id' => $user->id,
            'reference' => $reference,
            'payable_type' => $payableType,
            'payable_id' => $payableId,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'provider' => 'paystack',
            'gateway_mode' => $gateway->mode,
            'gateway_journal_id' => $gatewayJournalId,
        ]);

        $data = $this->paystack->initialize(
            $user->email,
            $amount,
            $reference,
            $metadata,
            $callbackUrl,
            $gateway,
        );

        $tx->provider_payload = ['initialize' => $data, 'gateway_mode' => $gateway->mode];
        $tx->save();

        return [
            'transaction' => $tx,
            'authorization_url' => $data['authorization_url'] ?? null,
            'access_code' => $data['access_code'] ?? null,
            'reference' => $reference,
            'gateway_mode' => $gateway->mode,
        ];
    }

    private function grantEntitlement(PaymentTransaction $tx): void
    {
        if ($tx->payable_type === Article::class) {
            Purchase::query()->firstOrCreate(
                [
                    'user_id' => $tx->user_id,
                    'article_id' => $tx->payable_id,
                ],
                [
                    'payment_transaction_id' => $tx->id,
                    'revoked_at' => null,
                ]
            );

            return;
        }

        if ($tx->payable_type === MembershipPlan::class) {
            $plan = MembershipPlan::query()->findOrFail($tx->payable_id);
            Membership::create([
                'user_id' => $tx->user_id,
                'membership_plan_id' => $plan->id,
                'journal_id' => $plan->journal_id,
                'scope' => $plan->scope,
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => now()->addDays($plan->duration_days),
            ]);

            return;
        }

        if ($tx->payable_type === Journal::class) {
            $journal = Journal::query()->findOrFail($tx->payable_id);
            $journal->markActivationPaid();
        }

        if ($tx->payable_type === Submission::class) {
            $submission = Submission::query()->findOrFail($tx->payable_id);

            if ($submission->status === 'fee_pending') {
                $submission->update([
                    'status' => 'submitted',
                    'fee_payment_transaction_id' => $tx->id,
                ]);

                SubmissionTimeline::query()->create([
                    'submission_id' => $submission->id,
                    'user_id' => $tx->user_id,
                    'event' => 'submitted',
                    'metadata' => [
                        'fee_payment_transaction_id' => $tx->id,
                        'journal_fee_id' => $submission->journal_fee_id,
                    ],
                ]);
            } elseif ($submission->status === 'publication_fee_pending') {
                $submission->update([
                    'status' => SubmissionStatus::READY_FOR_PRODUCTION,
                    'publication_fee_payment_transaction_id' => $tx->id,
                ]);

                SubmissionTimeline::query()->create([
                    'submission_id' => $submission->id,
                    'user_id' => $tx->user_id,
                    'event' => 'publication_fee_paid',
                    'metadata' => [
                        'publication_fee_payment_transaction_id' => $tx->id,
                        'publication_journal_fee_id' => $submission->publication_journal_fee_id,
                    ],
                ]);

                $this->production->notifyProductionEditors($submission->fresh(['journal', 'author']));
            }
        }
    }
}
