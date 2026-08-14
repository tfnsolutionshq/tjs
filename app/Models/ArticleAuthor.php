<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleAuthor extends Model
{
    protected $fillable = [
        'article_id', 'name', 'surname', 'given_names', 'middle_name',
        'email', 'affiliation', 'nationality',
        'orcid', 'role', 'is_corresponding', 'sort_order',
    ];

    /**
     * Academic-style display name: "Surname, Given Middle".
     */
    public static function composeName(?string $surname, ?string $givenNames, ?string $middleName = null, ?string $fallback = null): string
    {
        $surname = trim((string) $surname);
        $given = trim(trim((string) $givenNames).' '.trim((string) $middleName));
        $given = preg_replace('/\s+/', ' ', $given) ?: '';

        if ($surname !== '' && $given !== '') {
            return $surname.', '.$given;
        }
        if ($surname !== '') {
            return $surname;
        }
        if ($given !== '') {
            return $given;
        }

        return trim((string) $fallback);
    }

    protected function casts(): array
    {
        return ['is_corresponding' => 'boolean'];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
