<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Volume extends Model
{
    protected $fillable = [
        'journal_id', 'volume_number', 'year', 'title', 'introduction',
        'issn', 'cover_path', 'status',
    ];

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function coverUrl(): ?string
    {
        if (! $this->cover_path) {
            return null;
        }

        if (Storage::disk('public')->exists($this->cover_path)) {
            return Storage::disk('public')->url($this->cover_path);
        }

        // Legacy private-disk covers remain reachable for crawlers via controller.
        if ($this->journal) {
            return route('journals.volumes.cover', [$this->journal, $this]);
        }

        return null;
    }
}
