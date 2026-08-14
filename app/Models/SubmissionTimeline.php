<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionTimeline extends Model
{
    protected $fillable = ['submission_id', 'user_id', 'event', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
