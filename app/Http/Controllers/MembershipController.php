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

        $plans = MembershipPlan::query()
            ->where('is_active', true)
            ->with('journal:id,title,slug')
            ->orderBy('scope')
            ->orderBy('price_amount')
            ->get();

        $availablePlans = $plans->filter(
            fn (MembershipPlan $plan) => ! $coverage->planIsCovered($user, $plan, $activeMemberships)
        )->values();

        $coveredPlans = $plans->filter(
            fn (MembershipPlan $plan) => $coverage->planIsCovered($user, $plan, $activeMemberships)
        )->map(fn (MembershipPlan $plan) => [
            'plan' => $plan,
            'membership' => $coverage->coveringMembership($user, $plan, $activeMemberships),
        ])->values();

        $reviewerRequestJournals = app(ReviewerRequestService::class)->memberJournalContexts($user);

        return view('memberships.index', compact(
            'activeMemberships',
            'availablePlans',
            'coveredPlans',
            'reviewerRequestJournals',
        ));
    }
}
