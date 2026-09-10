<?php

namespace App\Services\Api;

use App\Models\Journal;
use App\Models\User;

final class UserCapabilities
{
    /**
     * @return array<string, mixed>
     */
    public function for(User $user): array
    {
        return [
            'platform_admin' => $user->canAccessPlatformAdmin(),
            'can_review' => $user->canAccessReviewQueue(),
            'can_produce' => $user->canAccessProductionQueue(),
            'managed_journals' => $this->journalSummaries($user->managedJournals()),
            'staff_journals' => $this->journalSummaries($user->staffJournals()),
            'production_journals' => $this->journalSummaries($user->productionJournals()),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Journal>  $journals
     * @return list<array{id: int, slug: string, title: string, role: string|null}>
     */
    private function journalSummaries($journals): array
    {
        return $journals->map(function (Journal $journal) {
            return [
                'id' => $journal->id,
                'slug' => $journal->slug,
                'title' => $journal->title,
                'role' => $journal->pivot->role ?? null,
            ];
        })->values()->all();
    }
}
