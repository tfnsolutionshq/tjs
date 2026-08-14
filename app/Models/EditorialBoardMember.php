<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EditorialBoardMember extends Model
{
    protected $fillable = [
        'journal_id', 'name', 'role_title', 'affiliation', 'email', 'orcid', 'sort_order',
    ];

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }
}
