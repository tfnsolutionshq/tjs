<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
    protected $fillable = [
        'article_id', 'user_id', 'action', 'ip_address', 'user_agent', 'metadata',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }
}
