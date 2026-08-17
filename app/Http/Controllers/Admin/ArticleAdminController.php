<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\Category;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\Volume;
use App\Services\Articles\ArticleDocumentExtractor;
use App\Services\Storage\ArticleStorage;
use App\Support\Licenses;
use App\Support\Nationalities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class ArticleAdminController extends Controller
{
    public function __construct(
        private ArticleStorage $storage,
        protected ArticleDocumentExtractor $extractor,
    ) {
    }

    public function index(Request $request): View
    {
        $query = Article::query()
            ->with(['journal', 'issue.volume', 'authors', 'categories'])
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

        if ($request->filled('journal_id')) {
            $query->where('journal_id', $request->integer('journal_id'));
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

        $journals = Journal::query()->orderBy('title')->get(['id', 'title', 'slug']);

        $stats = [
            'total' => Article::query()->count(),
            'published' => Article::query()->where('status', 'published')->count(),
            'draft' => Article::query()->where('status', 'draft')->count(),
            'open' => Article::query()->where('visibility', 'open')->count(),
            'members_only' => Article::query()->where('visibility', 'members_only')->count(),
            'paid' => Article::query()->where('visibility', 'paid')->count(),
            'closed' => Article::query()->where('visibility', 'closed')->count(),
        ];

        return view('admin.articles.index', compact('articles', 'journals', 'stats', 'perPage'));
    }

    public function create(): View
    {
        $journals = Journal::query()->orderBy('title')->get();
        $catalog = $this->placementCatalog();
        $categories = Category::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'slug', 'journal_id']);
        $ocrAvailable = $this->extractor->tesseractAvailable();

        return view('admin.articles.create', compact('journals', 'catalog', 'categories', 'ocrAvailable'));
    }

    public function extract(Request $request): JsonResponse
    {
        $request->validate([
            'document' => ['required', 'file', 'extensions:pdf,doc,docx,png,jpg,jpeg,webp,tif,tiff', 'max:51200'],
        ]);

        try {
            $result = $this->extractor->extract($request->file('document'));
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Could not extract details from this document.'], 500);
        }

        return response()->json($result);
    }

    public function quickVolume(Request $request): JsonResponse
    {
        $data = $request->validate([
            'journal_id' => ['required', 'exists:journals,id'],
            'volume_number' => ['required', 'integer', 'min:1'],
            'year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'title' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $exists = Volume::query()
            ->where('journal_id', $data['journal_id'])
            ->where('volume_number', $data['volume_number'])
            ->where('year', $data['year'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'That volume number already exists for this journal and year.'], 422);
        }

        $volume = Volume::query()->create([
            'journal_id' => $data['journal_id'],
            'volume_number' => $data['volume_number'],
            'year' => $data['year'],
            'title' => $data['title'] ?? null,
            'status' => $data['status'],
        ]);

        return response()->json([
            'volume' => [
                'id' => $volume->id,
                'journal_id' => (int) $volume->journal_id,
                'volume_number' => (int) $volume->volume_number,
                'year' => (int) $volume->year,
                'title' => $volume->title,
                'status' => $volume->status,
                'label' => 'Vol. '.$volume->volume_number.' ('.$volume->year.')',
            ],
        ], 201);
    }

    public function quickIssue(Request $request): JsonResponse
    {
        $data = $request->validate([
            'volume_id' => ['required', 'exists:volumes,id'],
            'issue_number' => ['required', 'integer', 'min:1'],
            'title' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $volume = Volume::query()->with('journal')->findOrFail($data['volume_id']);

        $exists = Issue::query()
            ->where('volume_id', $volume->id)
            ->where('issue_number', $data['issue_number'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'That issue number already exists in this volume.'], 422);
        }

        $issue = $volume->issues()->create([
            'issue_number' => $data['issue_number'],
            'title' => $data['title'] ?? null,
            'status' => $data['status'],
        ]);

        return response()->json([
            'issue' => [
                'id' => $issue->id,
                'volume_id' => (int) $issue->volume_id,
                'journal_id' => (int) $volume->journal_id,
                'issue_number' => (int) $issue->issue_number,
                'title' => $issue->title,
                'status' => $issue->status,
                'label' => $issue->label(),
            ],
        ], 201);
    }

    public function quickCategory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'journal_id' => ['required', 'exists:journals,id'],
            'name' => ['required', 'string', 'max:120'],
        ]);

        $name = trim($data['name']);
        $slug = Str::slug($name);
        if ($slug === '') {
            return response()->json(['message' => 'Enter a valid category name.'], 422);
        }

        $existing = Category::query()
            ->forJournal((int) $data['journal_id'])
            ->where(fn ($q) => $q->where('slug', $slug)->orWhere('name', $name))
            ->first();

        if ($existing) {
            return response()->json([
                'category' => [
                    'id' => $existing->id,
                    'name' => $existing->name,
                    'slug' => $existing->slug,
                    'journal_id' => $existing->journal_id,
                ],
                'created' => false,
            ]);
        }

        $maxSort = (int) Category::query()->forJournal((int) $data['journal_id'])->max('sort_order');
        $category = Category::query()->create([
            'journal_id' => (int) $data['journal_id'],
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
            'sort_order' => $maxSort + 1,
        ]);

        return response()->json([
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'journal_id' => $category->journal_id,
            ],
            'created' => true,
        ], 201);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $previousPath = null;

        $article = DB::transaction(function () use ($request, $data, &$previousPath) {
            $slug = $data['slug'] ?: Str::slug($data['title']);
            $slug = $this->uniqueSlug((int) $data['journal_id'], $slug);

            $article = Article::query()->create([
                'journal_id' => $data['journal_id'],
                'issue_id' => $data['issue_id'] ?? null,
                'author_user_id' => $data['author_user_id'] ?? null,
                'slug' => $slug,
                'title' => $data['title'],
                'abstract' => $data['abstract'] ?? null,
                'category' => null,
                'keywords' => $data['keywords'] ?? null,
                'doi' => $data['doi'] ?? null,
                'license' => $data['license'] ?? null,
                'page_range' => $data['page_range'] ?? null,
                'visibility' => $data['visibility'],
                'price_amount' => $data['price_amount'] ?? null,
                'currency' => $data['currency'] ?? 'NGN',
                'status' => $data['status'],
                'published_at' => $data['status'] === 'published' ? now() : null,
                'mins_read' => $data['mins_read'] ?? null,
                'references' => $data['references'] ?? null,
            ]);

            $this->syncAuthors($article, $data['authors'] ?? []);
            $this->syncCategories($article, $data['category_ids'] ?? []);

            if ($request->hasFile('galley')) {
                $previousPath = $article->document_path;
                $this->storage->storeGalley($article, $request->file('galley'));
            }

            return $article;
        });

        if ($request->hasFile('galley')) {
            $this->logGalleyReplace($request, $article, $previousPath);
        }

        return $this->articlesRedirect($request, $article, 'Article created.', toEdit: false);
    }

    public function edit(Article $article): View
    {
        $article->load(['authors', 'journal', 'issue.volume', 'categories']);
        $journals = Journal::query()->orderBy('title')->get();
        $catalog = $this->placementCatalog();
        $categories = Category::query()
            ->active()
            ->forJournal((int) $article->journal_id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'journal_id']);

        $authorsPayload = $this->authorsForForm($article);

        $ocrAvailable = $this->extractor->tesseractAvailable();
        $selectedCategoryIds = $article->categories->pluck('id')->map(fn ($id) => (string) $id)->all();

        return view('admin.articles.edit', compact(
            'article',
            'journals',
            'catalog',
            'categories',
            'authorsPayload',
            'ocrAvailable',
            'selectedCategoryIds'
        ));
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $data = $this->validated($request, $article);
        $previousPath = $article->document_path;
        $galleyReplaced = false;

        DB::transaction(function () use ($request, $article, $data, &$galleyReplaced) {
            $slug = $data['slug'] ?: Str::slug($data['title']);
            $slug = $this->uniqueSlug((int) $data['journal_id'], $slug, $article->id);

            $article->fill([
                'journal_id' => $data['journal_id'],
                'issue_id' => $data['issue_id'] ?? null,
                'author_user_id' => $data['author_user_id'] ?? null,
                'slug' => $slug,
                'title' => $data['title'],
                'abstract' => $data['abstract'] ?? null,
                'keywords' => $data['keywords'] ?? null,
                'doi' => $data['doi'] ?? null,
                'license' => $data['license'] ?? null,
                'page_range' => $data['page_range'] ?? null,
                'visibility' => $data['visibility'],
                'price_amount' => $data['price_amount'] ?? null,
                'currency' => $data['currency'] ?? 'NGN',
                'status' => $data['status'],
                'mins_read' => $data['mins_read'] ?? null,
                'references' => $data['references'] ?? null,
            ]);

            if ($data['status'] === 'published' && ! $article->published_at) {
                $article->published_at = now();
            }

            if ($data['status'] !== 'published') {
                $article->published_at = null;
            }

            $article->save();

            $this->syncAuthors($article, $data['authors'] ?? []);
            $this->syncCategories($article, $data['category_ids'] ?? []);

            if ($request->hasFile('galley')) {
                $galleyReplaced = true;
                $this->storage->storeGalley($article, $request->file('galley'));
            }
        });

        if ($galleyReplaced) {
            $this->logGalleyReplace($request, $article->fresh(), $previousPath);
        }

        return $this->articlesRedirect($request, $article, 'Article updated.', toEdit: true);
    }

    /**
     * @return array{volumes: list<array<string, mixed>>, issues: list<array<string, mixed>>}
     */
    protected function placementCatalog(): array
    {
        $volumes = Volume::query()
            ->with('journal:id,title,slug')
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

        $issues = Issue::query()
            ->with('volume.journal')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Issue $issue) => [
                'id' => $issue->id,
                'volume_id' => (int) $issue->volume_id,
                'journal_id' => (int) ($issue->volume?->journal_id ?? 0),
                'issue_number' => (int) $issue->issue_number,
                'title' => $issue->title,
                'status' => $issue->status,
                'label' => $issue->label(),
            ])
            ->values()
            ->all();

        return compact('volumes', 'issues');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Article $article = null): array
    {
        $data = $request->validate([
            'journal_id' => ['required', 'exists:journals,id'],
            'issue_id' => ['nullable', 'exists:issues,id'],
            'author_user_id' => ['nullable', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('articles', 'slug')
                    ->where(fn ($q) => $q->where('journal_id', $request->integer('journal_id')))
                    ->ignore($article?->id),
            ],
            'abstract' => ['nullable', 'string'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => [
                'integer',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('journal_id', $request->integer('journal_id'))),
            ],
            'keywords' => ['nullable', 'string', 'max:500'],
            'doi' => ['nullable', 'string', 'max:255', \App\Support\Doi::uniqueRule($article?->id)],
            'license' => ['nullable', 'string', Licenses::rule()],
            'page_range' => ['nullable', 'string', 'max:64'],
            'visibility' => ['required', 'in:open,members_only,paid,closed'],
            'price_amount' => [$request->input('visibility') === 'paid' ? 'required' : 'nullable', 'integer', 'min:0'],
            'currency' => [$request->input('visibility') === 'paid' ? 'required' : 'nullable', 'string', 'max:8'],
            'status' => ['required', 'in:draft,published'],
            'mins_read' => ['nullable', 'integer', 'min:1'],
            'authors' => ['nullable', 'array'],
            'authors.*.surname' => ['nullable', 'string', 'max:255'],
            'authors.*.given_names' => ['nullable', 'string', 'max:255'],
            'authors.*.middle_name' => ['nullable', 'string', 'max:255'],
            'authors.*.email' => ['nullable', 'email', 'max:255'],
            'authors.*.affiliation' => ['nullable', 'string', 'max:500'],
            'authors.*.nationality' => ['nullable', 'string', 'max:255', Rule::in(Nationalities::names())],
            'authors.*.orcid' => ['nullable', 'string', 'max:64'],
            'authors.*.role' => ['nullable', 'string', 'in:author,co-author,lead-author,editor,contributor,translator'],
            'authors.*.is_corresponding' => ['nullable', 'boolean'],
            'galley' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:51200'],
            'references' => ['nullable', 'array'],
        ]);

        $data['doi'] = \App\Support\Doi::normalize($data['doi'] ?? null);

        return $data;
    }

    private function uniqueSlug(int $journalId, string $slug, ?string $ignoreId = null): string
    {
        $base = $slug !== '' ? $slug : 'article';
        $candidate = $base;
        $i = 2;

        while (
            Article::query()
                ->where('journal_id', $journalId)
                ->where('slug', $candidate)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        return $candidate;
    }

    private function syncCategories(Article $article, array $categoryIds): void
    {
        $ids = collect($categoryIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $article->categories()->sync($ids);

        $labels = $article->categories()->orderBy('sort_order')->orderBy('name')->pluck('name')->implode(', ');
        $article->forceFill(['category' => $labels !== '' ? $labels : null])->save();
    }

    /**
     * @param  list<array<string, mixed>>  $authors
     * @return list<array<string, mixed>>
     */
    protected function authorsForForm(Article $article): array
    {
        return $article->authors->values()->map(function (ArticleAuthor $a) {
            $surname = trim((string) ($a->surname ?? ''));
            $given = trim((string) ($a->given_names ?? ''));
            $middle = trim((string) ($a->middle_name ?? ''));

            if ($surname === '' && $given === '' && trim((string) $a->name) !== '') {
                [$surname, $given] = $this->splitDisplayName((string) $a->name);
            }

            $fields = [];
            if ($surname !== '') {
                $fields[] = ['key' => 'surname', 'value' => $surname];
            }
            if ($given !== '') {
                $fields[] = ['key' => 'given_names', 'value' => $given];
            }
            if ($middle !== '') {
                $fields[] = ['key' => 'middle_name', 'value' => $middle];
            }
            foreach ([
                'email' => $a->email,
                'affiliation' => $a->affiliation,
                'nationality' => Nationalities::normalize($a->nationality),
                'orcid' => $a->orcid,
                'role' => $this->normalizeAuthorRole($a->role),
            ] as $key => $value) {
                $value = trim((string) ($value ?? ''));
                if ($value !== '') {
                    $fields[] = ['key' => $key, 'value' => $value];
                }
            }

            return [
                'fields' => $fields,
                'is_corresponding' => (bool) $a->is_corresponding,
            ];
        })->all();
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitDisplayName(string $name): array
    {
        $name = trim($name);
        if (str_contains($name, ',')) {
            [$surname, $rest] = array_pad(array_map('trim', explode(',', $name, 2)), 2, '');

            return [$surname, $rest];
        }

        $parts = preg_split('/\s+/', $name) ?: [];
        if (count($parts) >= 2) {
            $surname = array_pop($parts);

            return [$surname, implode(' ', $parts)];
        }

        return ['', $name];
    }

    private function normalizeAuthorRole(?string $role): ?string
    {
        $role = strtolower(trim((string) $role));
        if ($role === '') {
            return null;
        }

        $allowed = ['author', 'co-author', 'lead-author', 'editor', 'contributor', 'translator'];
        $aliases = [
            'coauthor' => 'co-author',
            'co author' => 'co-author',
            'lead author' => 'lead-author',
            'leadauthor' => 'lead-author',
            'authur' => 'author',
            'auther' => 'author',
        ];

        $role = $aliases[$role] ?? $role;

        return in_array($role, $allowed, true) ? $role : null;
    }

    /**
     * @param  list<array<string, mixed>>|string  $authors
     */
    private function syncAuthors(Article $article, array|string $authors): void
    {
        $article->authors()->delete();

        // Legacy pipe-delimited fallback (e.g. old forms / extractor paste).
        if (is_string($authors)) {
            $lines = preg_split('/\r\n|\r|\n/', $authors) ?: [];
            $authors = [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $parts = array_map('trim', explode('|', $line));
                [$surname, $given] = $this->splitDisplayName($parts[0] ?? '');
                $authors[] = [
                    'surname' => $surname,
                    'given_names' => $given,
                    'email' => $parts[1] ?? '',
                    'affiliation' => $parts[2] ?? '',
                    'orcid' => $parts[3] ?? '',
                    'is_corresponding' => count($authors) === 0,
                ];
            }
        }

        $order = 0;
        $hasCorresponding = collect($authors)->contains(fn ($a) => filter_var($a['is_corresponding'] ?? false, FILTER_VALIDATE_BOOLEAN));

        foreach ($authors as $row) {
            if (! is_array($row)) {
                continue;
            }

            $surname = trim((string) ($row['surname'] ?? ''));
            $given = trim((string) ($row['given_names'] ?? ''));
            $middle = trim((string) ($row['middle_name'] ?? ''));
            $name = ArticleAuthor::composeName($surname, $given, $middle);

            if ($name === '') {
                continue;
            }

            $email = trim((string) ($row['email'] ?? ''));
            $affiliation = trim((string) ($row['affiliation'] ?? ''));
            $nationality = Nationalities::normalize($row['nationality'] ?? '');
            $orcid = trim((string) ($row['orcid'] ?? ''));
            $role = trim((string) ($row['role'] ?? ''));
            $isCorresponding = filter_var($row['is_corresponding'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if (! $hasCorresponding && $order === 0) {
                $isCorresponding = true;
            }

            $article->authors()->create([
                'name' => $name,
                'surname' => $surname !== '' ? $surname : null,
                'given_names' => $given !== '' ? $given : null,
                'middle_name' => $middle !== '' ? $middle : null,
                'email' => $email !== '' ? $email : null,
                'affiliation' => $affiliation !== '' ? $affiliation : null,
                'nationality' => $nationality !== '' ? $nationality : null,
                'orcid' => $orcid !== '' ? $orcid : null,
                'role' => $role !== '' ? $role : 'author',
                'is_corresponding' => $isCorresponding,
                'sort_order' => $order,
            ]);

            $order++;
        }
    }

    private function articlesRedirect(Request $request, Article $article, string $status, bool $toEdit = false): RedirectResponse
    {
        $manageJournal = $request->attributes->get('manage_journal');
        if ($manageJournal instanceof Journal) {
            if ($toEdit) {
                return redirect()
                    ->route('journal.manage.articles.edit', [$manageJournal, $article])
                    ->with('status', $status);
            }

            return redirect()
                ->route('journal.manage.articles.index', $manageJournal)
                ->with('status', $status);
        }

        if ($toEdit) {
            return redirect()
                ->route('admin.articles.edit', $article)
                ->with('status', $status);
        }

        return redirect()
            ->route('admin.articles.index')
            ->with('status', $status);
    }

    private function logGalleyReplace(Request $request, Article $article, ?string $previousPath): void
    {
        AccessLog::query()->create([
            'article_id' => $article->id,
            'user_id' => $request->user()?->id,
            'action' => 'galley_replace',
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'metadata' => [
                'previous_path' => $previousPath,
                'new_path' => $article->document_path,
            ],
        ]);
    }
}
