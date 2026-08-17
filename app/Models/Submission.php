<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Support\ReviewType;

class Submission extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'journal_id', 'issue_id', 'announcement_id', 'author_id', 'title', 'abstract', 'category', 'keywords',
        'document_path', 'status', 'review_type', 'reviewer_id', 'review_comment',
        'rejection_reason', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(JournalAnnouncement::class, 'announcement_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ReviewerAssignment::class);
    }

    public function timelines(): HasMany
    {
        return $this->hasMany(SubmissionTimeline::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(EditorialComment::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(SubmissionRevision::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'id', 'submission_id');
    }

    public function effectiveReviewType(): string
    {
        return ReviewType::forSubmission($this);
    }

    public function isOpenReview(): bool
    {
        return ReviewType::isOpen($this->effectiveReviewType());
    }
}
