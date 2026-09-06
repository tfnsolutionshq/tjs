<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Issue extends Model
{
    protected $fillable = [
        'volume_id', 'issue_number', 'title', 'period_start', 'period_end',
        'cover_path', 'cover_disk', 'status', 'journal_fee_id',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    public function volume(): BelongsTo
    {
        return $this->belongsTo(Volume::class);
    }

    public function journalFee(): BelongsTo
    {
        return $this->belongsTo(JournalFee::class);
    }

    public function requiresPublicationPayment(): bool
    {
        $fee = $this->relationLoaded('journalFee') ? $this->journalFee : $this->journalFee()->first();

        return $fee && $fee->is_active && (int) $fee->amount >= 1;
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(JournalAnnouncement::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function label(): string
    {
        $vol = $this->volume;

        return sprintf('Vol. %s No. %s (%s)', $vol?->volume_number, $this->issue_number, $vol?->year);
    }

    public function coverUrl(): ?string
    {
        if ($this->cover_path) {
            $url = app(\App\Services\Storage\HybridDisk::class)
                ->url($this->cover_path, \App\Services\Storage\HybridDisk::KIND_MEDIA, $this->cover_disk);

            if ($url) {
                return $url;
            }

            $journal = $this->volume?->journal;
            if ($journal) {
                return route('journals.issues.cover', [$journal, $this]);
            }
        }

        return $this->volume?->coverUrl();
    }
}
