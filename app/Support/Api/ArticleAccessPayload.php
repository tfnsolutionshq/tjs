<?php

namespace App\Support\Api;

use App\Models\Article;
use App\Models\User;
use App\Services\Access\ArticleAccessResolver;

final class ArticleAccessPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function for(?User $user, Article $article, ArticleAccessResolver $access): array
    {
        $canAccessFullText = $access->canAccessFullText($user, $article);

        $payload = [
            'can_view_metadata' => $access->canViewMetadata($user, $article),
            'can_view_full_text' => $canAccessFullText,
            'denial_reason' => $canAccessFullText ? null : $access->denialReason($user, $article),
            'membership_required' => $article->visibility === 'members_only' && ! $canAccessFullText,
            'purchase_required' => $article->visibility === 'paid' && ! $canAccessFullText,
        ];

        if ($article->visibility === 'paid') {
            $payload['price'] = [
                'amount' => $article->price_amount,
                'currency' => $article->currency,
            ];
        }

        return $payload;
    }
}
