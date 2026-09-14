<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Journal\JournalPickerService;
use App\Http\Resources\Api\V1\ArticleSummaryResource;
use App\Http\Resources\Api\V1\JournalDetailResource;
use App\Http\Resources\Api\V1\JournalResource;
use App\Models\Article;
use App\Models\Issue;
use App\Models\Journal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $journals = Journal::query()
            ->listed()
            ->orderByDesc('is_featured')
            ->orderBy('title')
            ->paginate($request->integer('per_page', 12))
            ->withQueryString();

        return JournalResource::collection($journals)->response();
    }

    public function show(Journal $journal): JsonResponse
    {
        abort_unless($journal->isListed(), 404);

        $currentIssue = Issue::query()
            ->where('status', 'published')
            ->whereHas('volume', fn ($q) => $q->where('journal_id', $journal->id)->where('status', 'published'))
            ->with(['volume'])
            ->latest('updated_at')
            ->first();

        $journal->setRelation('currentIssue', $currentIssue);

        return response()->json([
            'data' => new JournalDetailResource($journal),
        ]);
    }

    public function browse(Request $request, Journal $journal): JsonResponse
    {
        abort_unless($journal->isListed(), 404);

        $base = Article::query()
            ->publicCatalog()
            ->where('journal_id', $journal->id)
            ->where('visibility', '!=', 'closed');

        $years = (clone $base)
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->pluck('published_at')
            ->map(fn ($date) => $date?->year)
            ->filter()
            ->unique()
            ->values();

        $query = (clone $base)
            ->with(['authors', 'categories', 'issue.volume', 'journal'])
            ->orderByDesc('published_at');

        if ($search = trim((string) $request->get('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('abstract', 'like', "%{$search}%")
                    ->orWhere('keywords', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhereHas('authors', fn ($aq) => $aq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($year = $request->integer('year')) {
            $query->whereYear('published_at', $year);
        }

        $articles = $query
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        return ArticleSummaryResource::collection($articles)
            ->additional([
                'meta' => [
                    'journal' => [
                        'slug' => $journal->slug,
                        'title' => $journal->title,
                    ],
                    'total_published' => (clone $base)->count(),
                    'filter_years' => $years,
                ],
            ])
            ->response();
    }

    public function picker(Request $request, JournalPickerService $picker): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
            'mode' => ['nullable', 'in:featured,other,all'],
        ]);

        $mode = $data['mode'] ?? 'all';

        return response()->json(
            $picker->searchForApi(
                $data['q'] ?? null,
                (int) ($data['page'] ?? 1),
                $mode === 'featured',
                $mode === 'other',
            )
        );
    }
}
