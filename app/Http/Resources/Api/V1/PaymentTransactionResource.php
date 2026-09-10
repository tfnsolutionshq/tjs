<?php

namespace App\Http\Resources\Api\V1;

use App\Services\Payments\PaymentReceiptService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PaymentTransaction */
class PaymentTransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $info = app(PaymentReceiptService::class)->describe($this->resource);

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status,
            'amount' => (int) $this->amount,
            'currency' => strtoupper((string) $this->currency),
            'provider' => $this->provider,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'type' => $info['type'],
            'type_label' => $info['type_label'],
            'detail' => $info['detail'],
            'journal' => $info['journal'] ? [
                'id' => $info['journal']->id,
                'slug' => $info['journal']->slug,
                'title' => $info['journal']->title,
            ] : null,
            'can_download_receipt' => $this->status === 'success',
        ];
    }
}
