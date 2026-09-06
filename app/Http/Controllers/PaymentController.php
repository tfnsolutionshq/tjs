<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Journal;
use App\Models\MembershipPlan;
use App\Models\Submission;
use App\Services\Payments\JournalPaymentUnavailableException;
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
        } catch (JournalPaymentUnavailableException $e) {
            Log::warning('Article purchase init failed — journal payments unavailable', [
                'article_id' => $article->id,
                'journal_id' => $journal->id,
                'user_id' => $request->user()?->id,
                'reason' => $e->internalReason(),
            ]);

            return redirect()
                ->route('journals.articles.show', [$journal, $article])
                ->with('error', $e->getMessage());
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
        } catch (JournalPaymentUnavailableException $e) {
            Log::warning('Membership purchase init failed — journal payments unavailable', [
                'plan_id' => $plan->id,
                'journal_id' => $plan->journal_id,
                'user_id' => $request->user()?->id,
                'reason' => $e->internalReason(),
            ]);

            return redirect()
                ->route('memberships.index')
                ->with('error', $e->getMessage());
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

    public function buySubmissionFee(Request $request, Submission $submission)
    {
        try {
            $result = $this->fulfillment->startSubmissionFeePayment($request->user(), $submission);
        } catch (JournalPaymentUnavailableException $e) {
            Log::warning('Submission fee payment init failed — journal payments unavailable', [
                'submission_id' => $submission->id,
                'user_id' => $request->user()?->id,
                'reason' => $e->internalReason(),
            ]);

            return redirect()
                ->route('author.submissions.show', $submission)
                ->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::warning('Submission fee payment init failed', [
                'submission_id' => $submission->id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('author.submissions.show', $submission)
                ->with('error', $e->getMessage() ?: 'Unable to start payment. Check Paystack configuration.');
        }

        if (empty($result['authorization_url'])) {
            return redirect()
                ->route('author.submissions.show', $submission)
                ->with('error', 'Unable to start payment with Paystack. Confirm PAYSTACK_SECRET_KEY is set.');
        }

        return redirect()->away($result['authorization_url']);
    }

    public function checkoutPublicationFee(Request $request, Submission $submission)
    {
        abort_unless((int) $submission->author_id === (int) $request->user()->id, 403);

        return $this->buyPublicationFee($request, $submission);
    }

    public function buyPublicationFee(Request $request, Submission $submission)
    {
        try {
            $result = $this->fulfillment->startPublicationFeePayment($request->user(), $submission);
        } catch (JournalPaymentUnavailableException $e) {
            Log::warning('Publication fee payment init failed — journal payments unavailable', [
                'submission_id' => $submission->id,
                'user_id' => $request->user()?->id,
                'reason' => $e->internalReason(),
            ]);

            return redirect()
                ->route('author.submissions.show', $submission)
                ->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::warning('Publication fee payment init failed', [
                'submission_id' => $submission->id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('author.submissions.show', $submission)
                ->with('error', $e->getMessage() ?: 'Unable to start payment. Check Paystack configuration.');
        }

        if (empty($result['authorization_url'])) {
            return redirect()
                ->route('author.submissions.show', $submission)
                ->with('error', 'Unable to start payment with Paystack. Confirm PAYSTACK_SECRET_KEY is set.');
        }

        return redirect()->away($result['authorization_url']);
    }

    public function buyJournalActivation(Request $request, Journal $journal)
    {
        try {
            $result = $this->fulfillment->startJournalActivationPurchase($request->user(), $journal);
        } catch (\Throwable $e) {
            Log::warning('Journal activation purchase init failed', [
                'journal_id' => $journal->id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('journal.manage.activation.show', $journal)
                ->with('error', $e->getMessage() ?: 'Unable to start payment. Check Paystack configuration.');
        }

        if (empty($result['authorization_url'])) {
            return redirect()
                ->route('journal.manage.activation.show', $journal)
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
                        ->with('status', 'Payment successful. You now have access to the full text. A PDF receipt was emailed to you.');
                }
            }

            if ($tx->payable_type === Journal::class) {
                $journal = Journal::query()->find($tx->payable_id);
                if ($journal) {
                    return redirect()
                        ->route('journal.manage.activation.show', $journal)
                        ->with('status', 'Activation payment successful. Your journal is listed and management is unlocked. A PDF receipt was emailed to you.');
                }
            }

            if ($tx->payable_type === Submission::class) {
                $submission = Submission::query()->find($tx->payable_id);
                if ($submission) {
                    $wasPublicationFee = in_array($submission->status, [
                        \App\Support\SubmissionStatus::READY_FOR_PRODUCTION,
                        \App\Support\SubmissionStatus::IN_PRODUCTION,
                        \App\Support\SubmissionStatus::READY_TO_PUBLISH,
                        \App\Support\SubmissionStatus::PUBLISHED,
                    ], true) || $submission->publication_fee_payment_transaction_id === $tx->id;

                    return redirect()
                        ->route('author.submissions.show', $submission)
                        ->with('status', $wasPublicationFee
                            ? 'Publication fee paid. Your manuscript is ready for publication in the issue. A PDF receipt was emailed to you.'
                            : 'Payment successful. Your submission is now with the editors. A PDF receipt was emailed to you.');
                }
            }

            return redirect()
                ->route('memberships.index')
                ->with('status', 'Payment successful. Membership activated. A PDF receipt was emailed to you.');
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
        $payload = json_decode($raw, true);
        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];
        $reference = $data['reference'] ?? null;

        $tx = $reference
            ? \App\Models\PaymentTransaction::query()->with('gatewayJournal')->where('reference', $reference)->first()
            : null;

        $secrets = app(\App\Services\Payments\JournalPaymentGatewayResolver::class)
            ->webhookCandidateSecrets($tx);

        if (! $this->paystack->isValidWebhookSignatureAny($raw, $signature, $secrets)) {
            Log::warning('Invalid Paystack webhook signature', ['reference' => $reference]);

            return response('Invalid signature', 400);
        }

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
