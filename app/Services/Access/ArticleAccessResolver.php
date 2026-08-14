<?php

namespace App\Services\Access;

use App\Models\Article;
use App\Models\Membership;
use App\Models\Purchase;
use App\Models\User;

class ArticleAccessResolver
{
    public function canViewMetadata(?User $user, Article $article): bool
    {
        if ($article->visibility === 'closed') {
            return $this->isStaff($user, $article);
        }

        if ($article->status !== 'published' || ! $article->published_at) {
            return $this->isStaff($user, $article) || $this->isAuthor($user, $article);
        }

        return true;
    }

    public function canAccessFullText(?User $user, Article $article): bool
    {
        if ($this->isStaff($user, $article) || $this->isAuthor($user, $article)) {
            return true;
        }

        if ($article->status !== 'published' || ! $article->published_at) {
            return false;
        }

        return match ($article->visibility) {
            'open' => true,
            'closed' => false,
            'members_only' => $user !== null && $this->hasActiveMembership($user, $article),
            'paid' => $user !== null && $this->hasActivePurchase($user, $article),
            default => false,
        };
    }

    public function denialReason(?User $user, Article $article): string
    {
        if ($article->visibility === 'closed') {
            return 'This article is closed and not available to the public.';
        }

        if ($article->visibility === 'members_only') {
            return $user
                ? 'An active membership is required to access the full text.'
                : 'Please log in and become a member to access the full text.';
        }

        if ($article->visibility === 'paid') {
            return $user
                ? 'Purchase this article to access the full text.'
                : 'Please log in and purchase this article to access the full text.';
        }

        return 'You do not have access to this document.';
    }

    public function isStaff(?User $user, Article $article): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (! $article->journal_id) {
            return false;
        }

        return $user->journals()
            ->where('journals.id', $article->journal_id)
            ->wherePivotIn('role', \App\Support\JournalTeamRoles::all())
            ->exists();
    }

    public function isAuthor(?User $user, Article $article): bool
    {
        return $user && $article->author_user_id && (int) $user->id === (int) $article->author_user_id;
    }

    public function hasActiveMembership(User $user, Article $article): bool
    {
        return Membership::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->where(function ($q) use ($article) {
                $q->where('scope', 'platform')
                    ->orWhere(function ($q2) use ($article) {
                        $q2->where('scope', 'journal')->where('journal_id', $article->journal_id);
                    });
            })
            ->exists();
    }

    public function hasActivePurchase(User $user, Article $article): bool
    {
        return Purchase::query()
            ->where('user_id', $user->id)
            ->where('article_id', $article->id)
            ->whereNull('revoked_at')
            ->exists();
    }
}
