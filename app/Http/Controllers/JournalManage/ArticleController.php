<?php

namespace App\Http\Controllers\JournalManage;

use App\Http\Controllers\Admin\ArticleAdminController as PlatformArticleAdminController;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\Volume;
use App\Services\Articles\ArticleDocumentExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function __construct(
        private PlatformArticleAdminController $platform,
        private ArticleDocumentExtractor $extractor,
    ) {
    }

    public function index(Request $request, Journal $journal): View
    {
        $query = Article::query()
            ->with(['journal', 'issue.volume', 'authors', 'categories'])
            ->where('journal_id', $journal->id)
            ->orderByDesc('updated_at');

        if ($search = trim((string) $request->get('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('doi', 'like', "%{$search}%")
                    ->orWhere('keywords', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhereHas('categories', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status') && in_array($request->string('status')->toString(), ['draft', 'published'], true)) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('visibility') && in_array($request->string('visibility')->toString(), ['open', 'members_only', 'paid', 'closed'], true)) {
            $query->where('visibility', $request->string('visibility'));
        }

        $perPage = (int) $request->integer('per_page', 12);
        if (! in_array($perPage, [12, 24, 48], true)) {
            $perPage = 12;
        }

        $articles = $query->paginate($perPage)->withQueryString();
        $journals = collect([$journal]);

        $base = Article::query()->where('journal_id', $journal->id);
        $stats = [
            'total' => (clone $base)->count(),
            'published' => (clone $base)->where('status', 'published')->count(),
            'draft' => (clone $base)->where('status', 'draft')->count(),
            'open' => (clone $base)->where('visibility', 'open')->count(),
            'members_only' => (clone $base)->where('visibility', 'members_only')->count(),
            'paid' => (clone $base)->where('visibility', 'paid')->count(),
            'closed' => (clone $base)->where('visibility', 'closed')->count(),
        ];

        $manageJournal = $journal;

        return view('admin.articles.index', compact(
            'articles',
            'journals',
            'stats',
            'perPage',
            'manageJournal',
            'journal',
        ));
    }

    public function create(Journal $journal): View
    {
        $journals = collect([$journal]);
        $catalog = $this->placementCatalogForJournal($journal);
        $categories = Category::query()
            ->active()
            ->forJournal($journal)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'journal_id']);
        $ocrAvailable = $this->extractor->tesseractAvailable();
        $manageJournal = $journal;

        return view('admin.articles.create', compact(
            'journals',
            'catalog',
            'categories',
            'ocrAvailable',
            'manageJournal',
            'journal',
        ));
    }

    public function store(Request $request, Journal $journal): RedirectResponse
    {
        $request->merge(['journal_id' => $journal->id]);
        $request->attributes->set('manage_journal', $journal);

        return $this->platform->store($request);
    }

    public function edit(Journal $journal, Article $article): View
    {
        abort_unless((int) $article->journal_id === (int) $journal->id, 404);

        $view = $this->platform->edit($article);
        $data = $view->getData();
        $data['journals'] = collect([$journal]);
        $data['catalog'] = $this->placementCatalogForJournal($journal);
        $data['manageJournal'] = $journal;
        $data['journal'] = $journal;

        return view('admin.articles.edit', $data);
    }

    public function update(Request $request, Journal $journal, Article $article): RedirectResponse
    {
        abort_unless((int) $article->journal_id === (int) $journal->id, 404);
        $request->merge(['journal_id' => $journal->id]);
        $request->attributes->set('manage_journal', $journal);

        return $this->platform->update($request, $article);
    }

    public function extract(Request $request, Journal $journal): JsonResponse
    {
        return $this->platform->extract($request);
    }

    public function quickVolume(Request $request, Journal $journal): JsonResponse
    {
        $request->merge(['journal_id' => $journal->id]);

        return $this->platform->quickVolume($request);
    }

    public function quickIssue(Request $request, Journal $journal): JsonResponse
    {
        return $this->platform->quickIssue($request);
    }

    public function quickCategory(Request $request, Journal $journal): JsonResponse
    {
        $request->merge(['journal_id' => $journal->id]);

        return $this->platform->quickCategory($request);
    }

    /**
     * @return array{volumes: list<array<string, mixed>>, issues: list<array<string, mixed>>}
     */
    private function placementCatalogForJournal(Journal $journal): array
    {
        $volumes = Volume::query()
            ->where('journal_id', $journal->id)
            ->orderByDesc('year')
            ->orderByDesc('volume_number')
            ->get()
            ->map(fn (Volume $volume) => [
                'id' => $volume->id,
                'journal_id' => (int) $volume->journal_id,
                'volume_number' => (int) $volume->volume_number,
                'year' => (int) $volume->year,
                'title' => $volume->title,
                'status' => $volume->status,
                'label' => 'Vol. '.$volume->volume_number.' ('.$volume->year.')',
            ])
            ->values()
            ->all();

        $volumeIds = array_map(fn ($v) => (int) $v['id'], $volumes);

        $issues = Issue::query()
            ->whereIn('volume_id', $volumeIds ?: [0])
            ->orderBy('issue_number')
            ->get()
            ->map(fn (Issue $issue) => [
                'id' => $issue->id,
                'volume_id' => (int) $issue->volume_id,
                'journal_id' => (int) $journal->id,
                'issue_number' => (int) $issue->issue_number,
                'title' => $issue->title,
                'status' => $issue->status,
                'label' => 'Issue '.$issue->issue_number.($issue->title ? ' — '.$issue->title : ''),
            ])
            ->values()
            ->all();

        return ['volumes' => $volumes, 'issues' => $issues];
    }
}
