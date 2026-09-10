<?php

namespace App\Support\Api;

final class PaymentInitializePayload
{
    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    public static function fromPaystackResult(array $result): array
    {
        $transaction = $result['transaction'] ?? null;

        return [
            'reference' => $result['reference'] ?? null,
            'authorization_url' => $result['authorization_url'] ?? null,
            'access_code' => $result['access_code'] ?? null,
            'amount' => isset($result['amount'])
                ? (int) $result['amount']
                : ($transaction ? (int) $transaction->amount : null),
            'currency' => $result['currency'] ?? ($transaction ? strtoupper((string) $transaction->currency) : null),
            'gateway_mode' => $result['gateway_mode'] ?? ($transaction->gateway_mode ?? null),
        ];
    }
}
