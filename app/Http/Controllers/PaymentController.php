<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Journal;
use App\Models\MembershipPlan;
use App\Services\Payments\PaymentFulfillmentService;
use App\Services\Payments\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentFulfillmentService $fulfillment,
        private PaystackService $paystack,
    ) {
    }

    public function buyArticle(Request $request, Journal $journal, Article $article)
    {
        abort_unless((int) $article->journal_id === (int) $journal->id, 404);

        try {
            $result = $this->fulfillment->startArticlePurchase($request->user(), $article);
        } catch (\Throwable $e) {
            Log::warning('Article purchase init failed', [
                'article_id' => $article->id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('journals.articles.show', [$journal, $article])
                ->with('error', $e->getMessage() ?: 'Unable to start payment. Check Paystack configuration.');
        }

        if (empty($result['authorization_url'])) {
            return redirect()
                ->route('journals.articles.show', [$journal, $article])
                ->with('error', 'Unable to start payment with Paystack. Confirm PAYSTACK_SECRET_KEY is set.');
        }

        return redirect()->away($result['authorization_url']);
    }

    public function buyMembership(Request $request, MembershipPlan $plan)
    {
        try {
            $result = $this->fulfillment->startMembershipPurchase($request->user(), $plan);
        } catch (\Throwable $e) {
            Log::warning('Membership purchase init failed', [
                'plan_id' => $plan->id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('memberships.index')
                ->with('error', $e->getMessage() ?: 'Unable to start payment. Check Paystack configuration.');
        }

        if (empty($result['authorization_url'])) {
            return redirect()
                ->route('memberships.index')
                ->with('error', 'Unable to start payment with Paystack. Confirm PAYSTACK_SECRET_KEY is set.');
        }

        return redirect()->away($result['authorization_url']);
    }

    public function callback(Request $request)
    {
        $reference = (string) $request->query('reference', '');
        if ($reference === '') {
            return redirect()->route('home')->with('error', 'Missing payment reference.');
        }

        try {
            $tx = $this->fulfillment->fulfillByReference($reference);

            if ($tx->payable_type === Article::class) {
                $article = Article::with('journal')->find($tx->payable_id);
                if ($article?->journal) {
                    return redirect()
                        ->route('journals.articles.show', [$article->journal, $article])
                        ->with('status', 'Payment successful. You now have access to the full text.');
                }
            }

            return redirect()
                ->route('memberships.index')
                ->with('status', 'Payment successful. Membership activated.');
        } catch (\Throwable $e) {
            Log::warning('Payment callback fulfillment failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('dashboard')
                ->with('error', 'Payment could not be confirmed. If you were charged, contact support with reference '.$reference.'.');
        }
    }

    public function webhook(Request $request)
    {
        $raw = $request->getContent();
        $signature = $request->header('x-paystack-signature');

        if (! $this->paystack->isValidWebhookSignature($raw, $signature)) {
            Log::warning('Invalid Paystack webhook signature');

            return response('Invalid signature', 400);
        }

        $payload = json_decode($raw, true);
        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];
        $reference = $data['reference'] ?? null;

        if ($event === 'charge.success' && $reference) {
            try {
                $this->fulfillment->fulfillByReference($reference, $data);
            } catch (\Throwable $e) {
                Log::error('Paystack webhook fulfillment error', [
                    'reference' => $reference,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return response('OK', 200);
    }
}
