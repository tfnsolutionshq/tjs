<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PaystackService
{
    public function initialize(
        string $email,
        int $amount,
        string $reference,
        array $metadata = [],
        ?string $callbackUrl = null,
        ?PaymentGatewayContext $gateway = null,
    ): array {
        $gateway ??= $this->defaultGateway();
        $secret = $gateway->secretKey;
        if ($secret === '') {
            throw new RuntimeException('Paystack secret key is not configured.');
        }

        $payload = [
            'email' => $email,
            'amount' => $amount * 100, // Paystack expects kobo
            'reference' => $reference,
            'currency' => config('paystack.currency', 'NGN'),
            'metadata' => $metadata,
        ];
        if ($callbackUrl) {
            $payload['callback_url'] = $callbackUrl;
        }

        if ($gateway->mode === PaymentGatewayContext::MODE_SPLIT && $gateway->splitCode) {
            $payload['split_code'] = $gateway->splitCode;
        }

        try {
            $response = Http::withToken($secret)
                ->acceptJson()
                ->post(rtrim(config('paystack.base_url'), '/').'/transaction/initialize', $payload);
        } catch (ConnectionException $e) {
            throw $this->initializationFailure(
                $gateway,
                'Paystack connection failed: '.$e->getMessage()
            );
        }

        if (! $response->successful() || ! ($response->json('status'))) {
            throw $this->initializationFailure(
                $gateway,
                'Paystack initialize failed: '.($response->json('message') ?: 'Unable to initialize Paystack transaction.')
            );
        }

        return $response->json('data');
    }

    private function initializationFailure(PaymentGatewayContext $gateway, string $internalReason): RuntimeException
    {
        if ($gateway->isJournalIncome()) {
            return new JournalPaymentUnavailableException($internalReason);
        }

        return new RuntimeException('Unable to start payment right now. Please try again later.');
    }

    public function verify(string $reference, ?PaymentGatewayContext $gateway = null): array
    {
        $gateway ??= $this->defaultGateway();
        $secret = $gateway->secretKey;
        if ($secret === '') {
            throw new RuntimeException('Paystack secret key is not configured.');
        }

        $response = Http::withToken($secret)
            ->acceptJson()
            ->get(rtrim(config('paystack.base_url'), '/').'/transaction/verify/'.rawurlencode($reference));

        if (! $response->successful()) {
            throw new RuntimeException('Unable to verify Paystack transaction.');
        }

        return $response->json('data') ?? [];
    }

    public function isValidWebhookSignature(string $rawBody, ?string $signature, ?string $secret = null): bool
    {
        $secret ??= (string) (config('paystack.webhook_secret') ?: config('paystack.secret_key'));
        if ($secret === '' || ! $signature) {
            return false;
        }

        $computed = hash_hmac('sha512', $rawBody, $secret);

        return hash_equals($computed, $signature);
    }

    /**
     * @param  list<string>  $secrets
     */
    public function isValidWebhookSignatureAny(string $rawBody, ?string $signature, array $secrets): bool
    {
        foreach ($secrets as $secret) {
            if ($this->isValidWebhookSignature($rawBody, $signature, $secret)) {
                return true;
            }
        }

        return false;
    }

    public function makeReference(string $prefix = 'tjs'): string
    {
        return strtoupper($prefix).'_'.Str::lower(Str::random(18));
    }

    private function defaultGateway(): PaymentGatewayContext
    {
        return app(JournalPaymentGatewayResolver::class)->platform();
    }
}
