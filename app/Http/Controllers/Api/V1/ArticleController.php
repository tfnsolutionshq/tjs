<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ArticleDetailResource;
use App\Http\Resources\Api\V1\ArticleSummaryResource;
use App\Models\Article;
use App\Models\Journal;
use App\Services\Access\ArticleAccessResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $articles = Article::query()
            ->publicCatalog()
            ->with(['journal', 'authors', 'issue.volume'])
            ->orderByDesc('published_at')
            ->paginate($request->integer('per_page', 12))
            ->withQueryString();

        return ArticleSummaryResource::collection($articles)->response();
    }

    public function show(Request $request, Journal $journal, Article $article, ArticleAccessResolver $access): JsonResponse
    {
        abort_unless((int) $article->journal_id === (int) $journal->id, 404);
        abort_unless($journal->isListed(), 404);
        abort_unless($access->canViewMetadata($request->user(), $article), 404);

        $article->load(['authors', 'issue.volume', 'journal', 'categories']);

        return response()->json([
            'data' => new ArticleDetailResource($article),
        ]);
    }
}
