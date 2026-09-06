<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Volume extends Model
{
    protected $fillable = [
        'journal_id', 'volume_number', 'year', 'title', 'introduction',
        'issn', 'cover_path', 'cover_disk', 'status',
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

        $url = app(\App\Services\Storage\HybridDisk::class)
            ->url($this->cover_path, \App\Services\Storage\HybridDisk::KIND_MEDIA, $this->cover_disk);

        if ($url) {
            return $url;
        }

        if ($this->journal) {
            return route('journals.volumes.cover', [$this->journal, $this]);
        }

        return null;
    }
}
