<?php

namespace App\Services\Payments;

use App\Models\Article;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\PaymentTransaction;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentFulfillmentService
{
    public function __construct(private PaystackService $paystack)
    {
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

        return $this->startTransaction(
            $user,
            Article::class,
            $article->id,
            (int) $article->price_amount,
            $article->currency ?: config('tjs.currency'),
            ['purpose' => 'article_purchase', 'article_id' => $article->id],
            route('payments.callback')
        );
    }

    public function startMembershipPurchase(User $user, MembershipPlan $plan): array
    {
        if (! $plan->is_active) {
            throw new RuntimeException('This membership plan is inactive.');
        }

        if (app(\App\Services\Membership\MembershipCoverageService::class)->planIsCovered($user, $plan)) {
            throw new RuntimeException('You already have active membership that covers this plan.');
        }

        return $this->startTransaction(
            $user,
            MembershipPlan::class,
            (string) $plan->id,
            (int) $plan->price_amount,
            $plan->currency ?: config('tjs.currency'),
            ['purpose' => 'membership', 'plan_id' => $plan->id],
            route('payments.callback')
        );
    }

    public function fulfillByReference(string $reference, ?array $providerPayload = null): PaymentTransaction
    {
        return DB::transaction(function () use ($reference, $providerPayload) {
            /** @var PaymentTransaction $tx */
            $tx = PaymentTransaction::query()->where('reference', $reference)->lockForUpdate()->firstOrFail();

            if ($tx->status === 'success') {
                return $tx; // idempotent
            }

            $verified = $providerPayload ?: $this->paystack->verify($reference);
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
        string $callbackUrl
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
        ]);

        $data = $this->paystack->initialize(
            $user->email,
            $amount,
            $reference,
            $metadata,
            $callbackUrl
        );

        $tx->provider_payload = ['initialize' => $data];
        $tx->save();

        return [
            'transaction' => $tx,
            'authorization_url' => $data['authorization_url'] ?? null,
            'access_code' => $data['access_code'] ?? null,
            'reference' => $reference,
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
        }
    }
}
