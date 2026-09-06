<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoiCreditTopup extends Model
{
    protected $fillable = [
        'journal_id', 'credits', 'amount_usd', 'amount_ngn', 'note', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'credits' => 'integer',
            'amount_usd' => 'decimal:2',
            'amount_ngn' => 'integer',
        ];
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
