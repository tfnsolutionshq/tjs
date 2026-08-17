<?php

namespace App\Models;

use App\Support\AnnouncementType;
use App\Services\Journal\CallForSubmissionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalAnnouncement extends Model
{
    protected $fillable = [
        'journal_id',
        'issue_id',
        'type',
        'title',
        'summary',
        'body',
        'opens_at',
        'closes_at',
        'is_published',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'announcement_id');
    }

    public function isCallForSubmissions(): bool
    {
        return AnnouncementType::isCallForSubmissions($this->type);
    }

    public function acceptsSubmissions(): bool
    {
        return app(CallForSubmissionService::class)->acceptsSubmissions($this);
    }

    public function submissionStatusLabel(): string
    {
        return app(CallForSubmissionService::class)->statusLabel($this);
    }

    public function issueLabel(): ?string
    {
        return $this->issue?->label();
    }
}
