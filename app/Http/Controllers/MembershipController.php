<?php

namespace App\Http\Controllers;

use App\Models\MembershipPlan;
use App\Services\Journal\ReviewerRequestService;
use App\Services\Membership\MembershipCoverageService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MembershipController extends Controller
{
    public function index(Request $request, MembershipCoverageService $coverage): View
    {
        $user = $request->user();
        $activeMemberships = $coverage->activeMemberships($user);

        $scope = $request->get('scope', 'featured') === 'all' ? 'all' : 'featured';
        $search = trim((string) $request->get('q', ''));

        $platformPlans = MembershipPlan::query()
            ->where('is_active', true)
            ->where('scope', 'platform')
            ->when(
                ! config('tjs.membership.platform_enabled', true),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->orderBy('price_amount')
            ->get();

        $journalPlansQuery = MembershipPlan::query()
            ->where('membership_plans.is_active', true)
            ->where('membership_plans.scope', 'journal')
            ->with(['journal:id,title,slug,is_featured,logo_path,logo_disk,initials'])
            ->when($scope === 'featured', function ($query) {
                $query->whereHas('journal', fn ($journal) => $journal->where('is_featured', true));
            })
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhereHas('journal', fn ($journal) => $journal->where('title', 'like', $like));
                });
            })
            ->when(! $coverage->hasPlatformAccess($user, $activeMemberships), function ($query) use ($user, $coverage, $activeMemberships) {
                $coveredJournalIds = $coverage->coveredJournalIds($user, $activeMemberships);
                if ($coveredJournalIds !== []) {
                    $query->whereNotIn('membership_plans.journal_id', $coveredJournalIds);
                }
            }, fn ($query) => $query->whereRaw('1 = 0'))
            ->join('journals', 'membership_plans.journal_id', '=', 'journals.id')
            ->orderByDesc('journals.is_featured')
            ->orderBy('journals.title')
            ->orderBy('membership_plans.price_amount')
            ->select('membership_plans.*');

        $journalPlans = (clone $journalPlansQuery)
            ->paginate(10)
            ->withQueryString();

        $otherJournalPlansCount = MembershipPlan::query()
            ->where('is_active', true)
            ->where('scope', 'journal')
            ->whereHas('journal', fn ($journal) => $journal->where('is_featured', false))
            ->when(! $coverage->hasPlatformAccess($user, $activeMemberships), function ($query) use ($user, $coverage, $activeMemberships) {
                $coveredJournalIds = $coverage->coveredJournalIds($user, $activeMemberships);
                if ($coveredJournalIds !== []) {
                    $query->whereNotIn('journal_id', $coveredJournalIds);
                }
            }, fn ($query) => $query->whereRaw('1 = 0'))
            ->count();

        $allPlans = $platformPlans->concat($journalPlans->getCollection());

        $availablePlatformPlans = $platformPlans->filter(
            fn (MembershipPlan $plan) => ! $coverage->planIsCovered($user, $plan, $activeMemberships)
        )->values();

        $availableJournalPlans = $journalPlans->getCollection();

        $availableCount = $availablePlatformPlans->count() + (clone $journalPlansQuery)->count();

        $coveredPlans = $allPlans->filter(
            fn (MembershipPlan $plan) => $coverage->planIsCovered($user, $plan, $activeMemberships)
        )->map(fn (MembershipPlan $plan) => [
            'plan' => $plan,
            'membership' => $coverage->coveringMembership($user, $plan, $activeMemberships),
        ])->values();

        $reviewerRequestJournals = app(ReviewerRequestService::class)->memberJournalContexts($user);

        return view('memberships.index', compact(
            'activeMemberships',
            'availablePlatformPlans',
            'availableJournalPlans',
            'journalPlans',
            'availableCount',
            'coveredPlans',
            'reviewerRequestJournals',
            'scope',
            'search',
            'otherJournalPlansCount',
        ));
    }
}
