<?php

namespace App\Models;

use App\Support\JournalFeePurpose;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalFee extends Model
{
    protected $fillable = [
        'journal_id',
        'name',
        'purpose',
        'amount',
        'currency',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function membershipPlans(): HasMany
    {
        return $this->hasMany(MembershipPlan::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function purposeLabel(): string
    {
        return JournalFeePurpose::label($this->purpose);
    }

    public function formattedAmount(): string
    {
        return number_format((int) $this->amount).' '.strtoupper((string) $this->currency);
    }

    public function requiresPayment(): bool
    {
        return (int) $this->amount > 0;
    }

    /**
     * @param  Builder<JournalFee>  $query
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<JournalFee>  $query
     */
    public function scopeForPurpose(Builder $query, string $purpose): Builder
    {
        return $query->where('purpose', $purpose);
    }
}
