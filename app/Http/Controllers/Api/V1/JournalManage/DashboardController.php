<?php

namespace App\Http\Controllers\Api\V1\JournalManage;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\JournalManageSubmissionResource;
use App\Models\Article;
use App\Models\Journal;
use App\Models\MembershipPlan;
use App\Models\Submission;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(Journal $journal): JsonResponse
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
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get(['id', 'slug', 'title', 'status', 'updated_at']);

        $recentSubmissions = Submission::query()
            ->where('journal_id', $journal->id)
            ->with(['author:id,name'])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        return response()->json([
            'data' => [
                'journal' => [
                    'id' => $journal->id,
                    'slug' => $journal->slug,
                    'title' => $journal->title,
                    'activation_locked' => $journal->activationLocked(),
                    'can_mutate' => $journal->userMayMutate(request()->user()),
                ],
                'stats' => $stats,
                'recent_articles' => $recentArticles->map(fn ($article) => [
                    'id' => $article->id,
                    'slug' => $article->slug,
                    'title' => $article->title,
                    'status' => $article->status,
                    'updated_at' => $article->updated_at?->toIso8601String(),
                ])->values(),
                'recent_submissions' => JournalManageSubmissionResource::collection($recentSubmissions)->resolve(),
            ],
        ]);
    }
}
