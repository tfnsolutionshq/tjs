<?php

namespace App\Services\Journal;

use App\Models\Journal;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Services\Membership\MembershipCoverageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class JournalEnrollmentService
{
    public function __construct(
        private MembershipCoverageService $coverage,
    ) {
    }

    public function rememberJournalForMembership(Request $request, Journal $journal): void
    {
        $request->session()->put('enroll.membership_journal_id', (int) $journal->id);
    }

    public function activePaidPlanForJournal(int $journalId): ?MembershipPlan
    {
        return MembershipPlan::query()
            ->where('journal_id', $journalId)
            ->where('scope', 'journal')
            ->where('is_active', true)
            ->where('price_amount', '>=', 1)
            ->orderBy('price_amount')
            ->first();
    }

    /**
     * @deprecated Use activePaidPlanForJournal() for payment prompts.
     */
    public function activePlanForJournal(int $journalId): ?MembershipPlan
    {
        return $this->activePaidPlanForJournal($journalId);
    }

    public function activeFreePlanForJournal(int $journalId): ?MembershipPlan
    {
        return MembershipPlan::query()
            ->where('journal_id', $journalId)
            ->where('scope', 'journal')
            ->where('is_active', true)
            ->where('price_amount', '<', 1)
            ->orderBy('price_amount')
            ->first();
    }

    public function requiresPaidMembership(Journal $journal): bool
    {
        return $this->activePaidPlanForJournal((int) $journal->id) !== null;
    }

    public function pendingPaidPlanForUser(User $user, int $journalId): ?MembershipPlan
    {
        $plan = $this->activePaidPlanForJournal($journalId);

        if (! $plan) {
            return null;
        }

        $active = $this->coverage->activeMemberships($user);

        return $this->coverage->planIsCovered($user, $plan, $active) ? null : $plan;
    }

    public function ensureJournalAccess(User $user, Journal $journal): ?Membership
    {
        $active = $this->coverage->activeMemberships($user);

        if ($this->coverage->hasPlatformAccess($user, $active)) {
            return null;
        }

        $existing = $active->first(
            fn (Membership $membership) => $membership->scope === 'journal'
                && (int) $membership->journal_id === (int) $journal->id
        );

        if ($existing) {
            return $existing;
        }

        if ($this->activePaidPlanForJournal((int) $journal->id)) {
            return null;
        }

        return $this->grantJournalMembership($user, $journal, $this->activeFreePlanForJournal((int) $journal->id));
    }

    public function grantJournalMembership(User $user, Journal $journal, ?MembershipPlan $plan = null): Membership
    {
        $durationDays = max(
            1,
            (int) ($plan?->duration_days ?: config('tjs.membership.enrollment_free_days', 365))
        );

        return Membership::query()->create([
            'user_id' => $user->id,
            'membership_plan_id' => $plan?->id,
            'journal_id' => $journal->id,
            'scope' => 'journal',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addDays($durationDays),
        ]);
    }

    public function afterVerificationRedirect(Request $request): RedirectResponse
    {
        $journalId = (int) $request->session()->pull('enroll.membership_journal_id', 0);
        $user = $request->user();

        if ($journalId && $user) {
            $plan = $this->pendingPaidPlanForUser($user, $journalId);

            if ($plan) {
                return redirect()
                    ->route('memberships.checkout', $plan)
                    ->with('status', 'Welcome! Complete your membership payment to access members-only content for this journal.');
            }

            if ($journal = Journal::query()->find($journalId)) {
                $membership = $this->ensureJournalAccess($user, $journal);
                $message = $membership
                    ? 'Welcome! You are now a member of '.$journal->title.'.'
                    : null;

                return redirect()
                    ->intended(route('journals.show', $journal).'?verified=1')
                    ->with('status', $message);
            }
        }

        return redirect()->intended(
            route($user?->homeRouteName() ?? 'dashboard', $user?->homeRouteParameters() ?? [], absolute: false).'?verified=1'
        );
    }
}
