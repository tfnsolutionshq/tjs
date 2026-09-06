<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'user_id', 'reference', 'payable_type', 'payable_id', 'amount',
        'currency', 'status', 'provider', 'gateway_mode', 'gateway_journal_id',
        'provider_payload', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'provider_payload' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function gatewayJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'gateway_journal_id');
    }
}
