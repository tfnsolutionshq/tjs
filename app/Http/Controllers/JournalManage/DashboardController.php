<?php

namespace App\Http\Controllers\JournalManage;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Journal;
use App\Models\MembershipPlan;
use App\Models\Submission;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Journal $journal): View
    {
        $stats = [
            'articles' => Article::query()->where('journal_id', $journal->id)->count(),
            'published' => Article::query()->where('journal_id', $journal->id)->where('status', 'published')->count(),
            'submissions' => Submission::query()->where('journal_id', $journal->id)->count(),
            'pending' => Submission::query()
                ->where('journal_id', $journal->id)
                ->whereIn('status', ['submitted', 'under_review', 'revision_requested', 'resubmitted'])
                ->count(),
            'plans' => MembershipPlan::query()
                ->where('scope', 'journal')
                ->where('journal_id', $journal->id)
                ->count(),
            'active_plans' => MembershipPlan::query()
                ->where('scope', 'journal')
                ->where('journal_id', $journal->id)
                ->where('is_active', true)
                ->count(),
        ];

        $recentArticles = Article::query()
            ->where('journal_id', $journal->id)
            ->with(['authors'])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        $recentSubmissions = Submission::query()
            ->where('journal_id', $journal->id)
            ->with(['author'])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        return view('journal-manage.dashboard', compact(
            'journal',
            'stats',
            'recentArticles',
            'recentSubmissions',
        ));
    }
}
