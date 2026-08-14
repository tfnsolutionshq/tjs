<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\User;
use App\Services\Access\ArticleAccessResolver;
use App\Services\Seo\ApaCitation;
use App\Services\Seo\ScholarlyMeta;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JournalController extends Controller
{
    public function index(): View
    {
        $journals = Journal::query()->where('is_active', true)->orderBy('title')->get();

        return view('public.journals.index', compact('journals'));
    }

    public function show(Journal $journal): View
    {
        abort_unless($journal->is_active, 404);

        $currentIssue = Issue::query()
            ->where('status', 'published')
            ->whereHas('volume', fn ($q) => $q->where('journal_id', $journal->id)->where('status', 'published'))
            ->with(['volume', 'articles' => fn ($q) => $q->publicCatalog()->with(['authors', 'categories'])])
            ->latest('updated_at')
            ->first();

        return view('public.journals.show', compact('journal', 'currentIssue'));
    }

    public function archive(Journal $journal): View
    {
        abort_unless($journal->is_active, 404);

        $volumes = $journal->volumes()
            ->where('status', 'published')
            ->with(['issues' => fn ($q) => $q->where('status', 'published')->orderBy('issue_number')])
            ->orderByDesc('year')
            ->orderByDesc('volume_number')
            ->get();

        return view('public.journals.archive', compact('journal', 'volumes'));
    }

    public function about(Journal $journal): View
    {
        abort_unless($journal->is_active, 404);

        return view('public.journals.about', compact('journal'));
    }

    public function editorialBoard(Journal $journal): View
    {
        abort_unless($journal->is_active, 404);
        $members = $journal->editorialBoard;

        return view('public.journals.editorial-board', compact('journal', 'members'));
    }

    public function reviewers(Journal $journal): View
    {
        abort_unless($journal->is_active, 404);
        $reviewers = User::query()
            ->where('is_public_reviewer', true)
            ->where(function ($q) use ($journal) {
                $q->where('role', 'reviewer')
                    ->orWhereHas('journals', fn ($j) => $j->where('journals.id', $journal->id)->wherePivot('role', 'reviewer'));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'position', 'affiliation', 'bio']);

        return view('public.journals.reviewers', compact('journal', 'reviewers'));
    }

    public function browse(Request $request, Journal $journal): View
    {
        abort_unless($journal->is_active, 404);

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
            ->with(['authors', 'categories', 'issue.volume'])
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

        $articles = $query->paginate(20)->withQueryString();
        $totalPublished = (clone $base)->count();

        return view('public.journals.browse', compact('journal', 'articles', 'years', 'totalPublished'));
    }

    public function issue(Journal $journal, Issue $issue): View
    {
        abort_unless($journal->is_active, 404);
        abort_unless($issue->volume && (int) $issue->volume->journal_id === (int) $journal->id, 404);
        abort_unless($issue->isPublished() && $issue->volume->isPublished(), 404);

        $issue->load(['volume.journal', 'articles' => fn ($q) => $q->publicCatalog()->with(['authors', 'categories'])]);

        return view('public.journals.issue', compact('journal', 'issue'));
    }
}
