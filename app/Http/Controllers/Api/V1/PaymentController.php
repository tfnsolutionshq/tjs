<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PaymentTransactionResource;
use App\Models\Article;
use App\Models\Journal;
use App\Models\MembershipPlan;
use App\Models\PaymentTransaction;
use App\Services\Payments\JournalPaymentUnavailableException;
use App\Services\Payments\PaymentFulfillmentService;
use App\Services\Payments\PaymentReceiptService;
use App\Support\Api\PaymentInitializePayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentFulfillmentService $fulfillment,
        private PaymentReceiptService $receipts,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $transactions = PaymentTransaction::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        return PaymentTransactionResource::collection($transactions)->response();
    }

    public function show(Request $request, PaymentTransaction $paymentTransaction): JsonResponse
    {
        abort_unless((int) $paymentTransaction->user_id === (int) $request->user()->id, 403);

        return response()->json([
            'data' => new PaymentTransactionResource($paymentTransaction),
        ]);
    }

    public function downloadReceipt(Request $request, PaymentTransaction $paymentTransaction): StreamedResponse
    {
        abort_unless($this->receipts->userCanDownload($request->user(), $paymentTransaction), 403);
        abort_unless($paymentTransaction->status === 'success', 404, 'Receipt is only available after a successful payment.');

        return $this->receipts->downloadResponse($paymentTransaction);
    }

    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:120'],
        ]);

        $transaction = PaymentTransaction::query()
            ->where('reference', $data['reference'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        try {
            $transaction = $this->fulfillment->fulfillByReference($data['reference']);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage() ?: 'Payment could not be confirmed.',
            ], 422);
        }

        return response()->json([
            'data' => [
                'message' => $transaction->status === 'success'
                    ? 'Payment confirmed.'
                    : 'Payment status updated.',
                'transaction' => new PaymentTransactionResource($transaction),
            ],
        ]);
    }

    public function purchaseArticle(Request $request, Journal $journal, Article $article): JsonResponse
    {
        abort_unless((int) $article->journal_id === (int) $journal->id, 404);

        return $this->initializePayment(
            fn () => $this->fulfillment->startArticlePurchase($request->user(), $article),
            'article_purchase',
        );
    }

    public function purchaseMembership(Request $request, MembershipPlan $plan): JsonResponse
    {
        return $this->initializePayment(
            fn () => $this->fulfillment->startMembershipPurchase($request->user(), $plan),
            'membership',
        );
    }

    public function activateJournal(Request $request, Journal $journal): JsonResponse
    {
        abort_unless($request->user()->canManageJournal($journal), 403);

        return $this->initializePayment(
            fn () => $this->fulfillment->startJournalActivationPurchase($request->user(), $journal),
            'journal_activation',
        );
    }

    private function initializePayment(callable $starter, string $purpose): JsonResponse
    {
        try {
            $result = $starter();
        } catch (JournalPaymentUnavailableException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage() ?: 'Unable to start payment. Check Paystack configuration.',
            ], 422);
        }

        if (empty($result['authorization_url'])) {
            return response()->json([
                'message' => 'Unable to start payment with Paystack. Confirm PAYSTACK_SECRET_KEY is set.',
            ], 422);
        }

        return response()->json([
            'data' => array_merge(
                PaymentInitializePayload::fromPaystackResult($result),
                [
                    'purpose' => $purpose,
                    'transaction_id' => $result['transaction']->id ?? null,
                ]
            ),
        ]);
    }
}
