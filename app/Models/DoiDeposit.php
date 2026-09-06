<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoiDeposit extends Model
{
    protected $fillable = [
        'journal_id', 'article_id', 'doi', 'mode', 'status', 'credits_spent',
        'provider_payload', 'error_message', 'deposited_by', 'deposited_at',
    ];

    protected function casts(): array
    {
        return [
            'provider_payload' => 'array',
            'deposited_at' => 'datetime',
            'credits_spent' => 'integer',
        ];
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function depositor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deposited_by');
    }
}
