<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\Submission;
use App\Models\User;
use App\Models\Volume;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $pendingStatuses = ['submitted', 'under_review', 'resubmitted', 'revision_requested'];

        $counts = [
            'journals' => Journal::query()->count(),
            'volumes' => Volume::query()->count(),
            'issues' => Issue::query()->count(),
            'articles' => Article::query()->count(),
            'published_articles' => Article::query()->where('status', 'published')->count(),
            'submissions' => Submission::query()->count(),
            'pending_submissions' => Submission::query()->whereIn('status', $pendingStatuses)->count(),
            'users' => User::query()->count(),
            'membership_plans' => MembershipPlan::query()->count(),
            'active_memberships' => Membership::query()
                ->where('status', 'active')
                ->where('ends_at', '>', now())
                ->count(),
        ];

        $recentSubmissions = Submission::query()
            ->with(['journal:id,title,slug', 'author:id,name,email'])
            ->latest()
            ->limit(6)
            ->get();

        $journals = Journal::query()
            ->withCount(['articles', 'volumes'])
            ->orderByDesc('is_featured')
            ->orderBy('title')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('counts', 'recentSubmissions', 'journals'));
    }
}
