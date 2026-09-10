<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MembershipPlanResource;
use App\Models\MembershipPlan;
use App\Services\Membership\MembershipCoverageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MembershipController extends Controller
{
    public function index(Request $request, MembershipCoverageService $coverage): JsonResponse
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
            ->with(['journal:id,title,slug,is_featured'])
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
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        $availablePlatformPlans = $platformPlans->filter(
            fn (MembershipPlan $plan) => ! $coverage->planIsCovered($user, $plan, $activeMemberships)
        )->values();

        $availableJournalPlans = $journalPlans->getCollection()->filter(
            fn (MembershipPlan $plan) => ! $coverage->planIsCovered($user, $plan, $activeMemberships)
        )->values();

        $activePayload = $activeMemberships->map(function ($membership) {
            $plan = $membership->plan;
            if ($plan && $plan->scope === 'journal') {
                $plan->setRelation('journal', $membership->journal);
            }

            return [
                'id' => $membership->id,
                'scope' => $membership->scope,
                'starts_at' => $membership->starts_at?->toIso8601String(),
                'ends_at' => $membership->ends_at?->toIso8601String(),
                'plan' => $plan ? (new MembershipPlanResource($plan))->resolve() : null,
                'journal' => $membership->journal ? [
                    'id' => $membership->journal->id,
                    'slug' => $membership->journal->slug,
                    'title' => $membership->journal->title,
                ] : null,
            ];
        })->values();

        return response()->json([
            'data' => [
                'active_memberships' => $activePayload,
                'platform_plans' => MembershipPlanResource::collection($availablePlatformPlans)->resolve(),
                'journal_plans' => MembershipPlanResource::collection($availableJournalPlans)->resolve(),
            ],
            'meta' => [
                'scope' => $scope,
                'journal_plans_pagination' => [
                    'current_page' => $journalPlans->currentPage(),
                    'last_page' => $journalPlans->lastPage(),
                    'per_page' => $journalPlans->perPage(),
                    'total' => $journalPlans->total(),
                ],
            ],
            'links' => [
                'journal_plans' => [
                    'first' => $journalPlans->url(1),
                    'last' => $journalPlans->url($journalPlans->lastPage()),
                    'prev' => $journalPlans->previousPageUrl(),
                    'next' => $journalPlans->nextPageUrl(),
                ],
            ],
        ]);
    }
}
