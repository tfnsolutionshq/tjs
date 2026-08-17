<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Issue extends Model
{
    protected $fillable = [
        'volume_id', 'issue_number', 'title', 'period_start', 'period_end',
        'cover_path', 'status',
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
            if (Storage::disk('public')->exists($this->cover_path)) {
                return Storage::disk('public')->url($this->cover_path);
            }

            $journal = $this->volume?->journal;
            if ($journal) {
                return route('journals.issues.cover', [$journal, $this]);
            }
        }

        return $this->volume?->coverUrl();
    }
}
