<?php

namespace App\Support;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

final class Doi
{
    public static function normalize(?string $doi): ?string
    {
        $doi = trim((string) $doi);
        if ($doi === '') {
            return null;
        }

        $doi = preg_replace('#^https?://(dx\.)?doi\.org/#i', '', $doi) ?? $doi;
        $doi = preg_replace('#^doi:\s*#i', '', $doi) ?? $doi;

        return trim($doi) !== '' ? trim($doi) : null;
    }

    public static function uniqueRule(?string $ignoreArticleId = null): Unique
    {
        $rule = Rule::unique('articles', 'doi');

        if ($ignoreArticleId) {
            $rule->ignore($ignoreArticleId);
        }

        return $rule;
    }
}
