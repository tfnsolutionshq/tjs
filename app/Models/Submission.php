<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Support\ReviewType;
use App\Support\SubmissionStatus;

class Submission extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'journal_id', 'issue_id', 'announcement_id', 'journal_fee_id', 'fee_payment_transaction_id',
        'publication_journal_fee_id', 'publication_fee_payment_transaction_id',
        'author_id', 'title', 'abstract', 'category', 'keywords',
        'document_path', 'document_disk', 'status', 'review_type', 'reviewer_id', 'review_comment',
        'rejection_reason', 'reviewed_at',
        'production_assigned_to', 'production_started_at', 'production_completed_at',
        'production_completed_by', 'production_checklist',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'production_started_at' => 'datetime',
            'production_completed_at' => 'datetime',
            'production_checklist' => 'array',
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

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(JournalAnnouncement::class, 'announcement_id');
    }

    public function journalFee(): BelongsTo
    {
        return $this->belongsTo(JournalFee::class);
    }

    public function publicationJournalFee(): BelongsTo
    {
        return $this->belongsTo(JournalFee::class, 'publication_journal_fee_id');
    }

    public function feePaymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'fee_payment_transaction_id');
    }

    public function publicationFeePaymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'publication_fee_payment_transaction_id');
    }

    public function requiresFeePayment(): bool
    {
        return $this->status === 'fee_pending';
    }

    public function requiresPublicationFeePayment(): bool
    {
        return $this->status === 'publication_fee_pending';
    }

    /**
     * Editorial workflow (review, production, publish) is blocked until submission fee is paid.
     */
    public function blocksEditorialProgress(): bool
    {
        return $this->status === SubmissionStatus::FEE_PENDING;
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

    public function productionFiles(): HasMany
    {
        return $this->hasMany(SubmissionProductionFile::class);
    }

    public function currentProductionFile(): ?SubmissionProductionFile
    {
        if ($this->relationLoaded('productionFiles')) {
            return $this->productionFiles->firstWhere('is_current', true);
        }

        return $this->productionFiles()->where('is_current', true)->latest('id')->first();
    }

    public function productionAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'production_assigned_to');
    }

    public function productionCompleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'production_completed_by');
    }

    public function hasProductionDocument(): bool
    {
        $file = $this->currentProductionFile();

        return $file !== null && filled($file->document_path);
    }

    public function isReadyToPublish(): bool
    {
        return $this->status === SubmissionStatus::READY_TO_PUBLISH && $this->hasProductionDocument();
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
