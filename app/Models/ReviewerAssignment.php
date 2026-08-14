<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewerAssignment extends Model
{
    protected $fillable = [
        'submission_id', 'reviewer_id', 'status', 'priority', 'due_at',
    ];

    protected function casts(): array
    {
        return ['due_at' => 'datetime'];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
