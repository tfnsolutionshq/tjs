<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PaystackService
{
    public function initialize(string $email, int $amount, string $reference, array $metadata = [], ?string $callbackUrl = null): array
    {
        $secret = config('paystack.secret_key');
        if (! $secret) {
            throw new RuntimeException('PAYSTACK_SECRET_KEY is not configured. Add it to your .env file.');
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

        $response = Http::withToken($secret)
            ->acceptJson()
            ->post(rtrim(config('paystack.base_url'), '/').'/transaction/initialize', $payload);

        if (! $response->successful() || ! ($response->json('status'))) {
            throw new RuntimeException($response->json('message') ?: 'Unable to initialize Paystack transaction.');
        }

        return $response->json('data');
    }

    public function verify(string $reference): array
    {
        $secret = config('paystack.secret_key');
        if (! $secret) {
            throw new RuntimeException('PAYSTACK_SECRET_KEY is not configured.');
        }

        $response = Http::withToken($secret)
            ->acceptJson()
            ->get(rtrim(config('paystack.base_url'), '/').'/transaction/verify/'.rawurlencode($reference));

        if (! $response->successful()) {
            throw new RuntimeException('Unable to verify Paystack transaction.');
        }

        return $response->json('data') ?? [];
    }

    public function isValidWebhookSignature(string $rawBody, ?string $signature): bool
    {
        $secret = config('paystack.webhook_secret') ?: config('paystack.secret_key');
        if (! $secret || ! $signature) {
            return false;
        }

        $computed = hash_hmac('sha512', $rawBody, $secret);

        return hash_equals($computed, $signature);
    }

    public function makeReference(string $prefix = 'tjs'): string
    {
        return strtoupper($prefix).'_'.Str::lower(Str::random(18));
    }
}
