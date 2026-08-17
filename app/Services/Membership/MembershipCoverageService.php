<?php

namespace App\Services\Membership;

use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class MembershipCoverageService
{
    /**
     * @return Collection<int, Membership>
     */
    public function activeMemberships(User $user): Collection
    {
        return Membership::query()
            ->with(['plan', 'journal:id,title,slug'])
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->orderByDesc('ends_at')
            ->get();
    }

    public function planIsCovered(User $user, MembershipPlan $plan, ?Collection $active = null): bool
    {
        return $this->coveringMembership($user, $plan, $active) !== null;
    }

    public function coveringMembership(User $user, MembershipPlan $plan, ?Collection $active = null): ?Membership
    {
        $active ??= $this->activeMemberships($user);

        foreach ($active as $membership) {
            if ((int) $membership->membership_plan_id === (int) $plan->id) {
                return $membership;
            }

            if ($membership->scope === 'platform') {
                return $membership;
            }

            if ($plan->scope === 'journal'
                && $membership->scope === 'journal'
                && (int) $membership->journal_id === (int) $plan->journal_id) {
                return $membership;
            }
        }

        return null;
    }

    public function hasPlatformAccess(User $user, ?Collection $active = null): bool
    {
        $active ??= $this->activeMemberships($user);

        return $active->contains(fn (Membership $membership) => $membership->scope === 'platform');
    }

    /**
     * @return list<int>
     */
    public function coveredJournalIds(User $user, ?Collection $active = null): array
    {
        $active ??= $this->activeMemberships($user);

        if ($this->hasPlatformAccess($user, $active)) {
            return [];
        }

        return $active
            ->where('scope', 'journal')
            ->pluck('journal_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
