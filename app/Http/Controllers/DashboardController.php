<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\Submission;
use App\Services\Journal\ReviewerRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $home = $user->homeRouteName();

        if ($home !== 'dashboard') {
            return redirect()->route($home, $user->homeRouteParameters());
        }

        $submissionBase = Submission::query()->where('author_id', $user->id);

        $stats = [
            'submissions' => (clone $submissionBase)->count(),
            'in_review' => (clone $submissionBase)
                ->whereIn('status', ['submitted', 'under_review', 'resubmitted', 'revision_requested'])
                ->count(),
            'accepted' => (clone $submissionBase)->where('status', 'accepted')->count(),
            'memberships' => Membership::query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->where('starts_at', '<=', now())
                ->where('ends_at', '>=', now())
                ->count(),
        ];

        $recentSubmissions = Submission::query()
            ->with('journal:id,title,slug')
            ->where('author_id', $user->id)
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        $activeMemberships = Membership::query()
            ->with(['plan', 'journal:id,title,slug'])
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->orderByDesc('ends_at')
            ->get();

        $membershipPlans = MembershipPlan::query()
            ->where('is_active', true)
            ->with('journal:id,title,slug')
            ->orderBy('scope')
            ->orderBy('price_amount')
            ->get();

        $reviewerRequestJournals = app(ReviewerRequestService::class)->memberJournalContexts($user);

        return view('dashboard', compact(
            'stats',
            'recentSubmissions',
            'activeMemberships',
            'membershipPlans',
            'reviewerRequestJournals',
        ));
    }
}
