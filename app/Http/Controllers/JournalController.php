<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\User;
use App\Models\JournalAnnouncement;
use App\Services\Journal\JournalFeeResolver;
use App\Services\Journal\ReviewerRequestService;
use App\Services\Journal\JournalEnrollmentService;
use App\Support\JournalAuth;
use App\Support\ListLayout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JournalController extends Controller
{
    public function __construct(
        private JournalFeeResolver $fees,
    ) {
    }

    public function index(Request $request): View
    {
        $layout = ListLayout::fromRequest($request);
        $journals = Journal::query()
            ->listed()
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();

        return view('public.journals.index', compact('journals', 'layout'));
    }

    public function show(Journal $journal): View
    {
        abort_unless($journal->isListed(), 404);

        $currentIssue = Issue::query()
            ->where('status', 'published')
            ->whereHas('volume', fn ($q) => $q->where('journal_id', $journal->id)->where('status', 'published'))
            ->with(['volume', 'articles' => fn ($q) => $q->publicCatalog()->with(['authors', 'categories'])])
            ->latest('updated_at')
            ->first();

        return view('public.journals.show', compact('journal', 'currentIssue'));
    }

    public function archive(Request $request, Journal $journal): View
    {
        abort_unless($journal->isListed(), 404);

        $layout = ListLayout::fromRequest($request);

        $volumeQuery = $journal->volumes()->where('status', 'published');
        $volumeCount = (clone $volumeQuery)->count();
        $issueCount = Issue::query()
            ->where('status', 'published')
            ->whereHas('volume', fn ($q) => $q->where('journal_id', $journal->id)->where('status', 'published'))
            ->count();

        $volumes = (clone $volumeQuery)
            ->with(['issues' => fn ($q) => $q->where('status', 'published')->orderBy('issue_number')])
            ->orderByDesc('year')
            ->orderByDesc('volume_number')
            ->paginate(6)
            ->withQueryString();

        return view('public.journals.archive', compact('journal', 'volumes', 'volumeCount', 'issueCount', 'layout'));
    }

    public function about(Journal $journal): View
    {
        abort_unless($journal->isListed(), 404);

        return view('public.journals.about', compact('journal'));
    }

    public function editorialBoard(Request $request, Journal $journal): View
    {
        abort_unless($journal->isListed(), 404);

        $layout = ListLayout::fromRequest($request);
        $members = $journal->editorialBoard()->paginate(12)->withQueryString();

        return view('public.journals.editorial-board', compact('journal', 'members', 'layout'));
    }

    public function reviewers(Request $request, Journal $journal): View
    {
        abort_unless($journal->isListed(), 404);

        $layout = ListLayout::fromRequest($request);

        $reviewers = User::query()
            ->where('is_public_reviewer', true)
            ->where(function ($q) use ($journal) {
                $q->where('role', 'reviewer')
                    ->orWhereHas('journals', fn ($j) => $j->where('journals.id', $journal->id)->wherePivot('role', 'reviewer'));
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $reviewerRequest = auth()->check()
            ? app(ReviewerRequestService::class)->contextFor(auth()->user(), $journal)
            : null;

        return view('public.journals.reviewers', compact('journal', 'reviewers', 'reviewerRequest', 'layout'));
    }

    public function browse(Request $request, Journal $journal): View
    {
        abort_unless($journal->isListed(), 404);

        $layout = ListLayout::fromRequest($request, ListLayout::LIST);

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

        return view('public.journals.browse', compact('journal', 'articles', 'years', 'totalPublished', 'layout'));
    }

    public function issue(Request $request, Journal $journal, Issue $issue): View
    {
        abort_unless($journal->isListed(), 404);
        abort_unless($issue->volume && (int) $issue->volume->journal_id === (int) $journal->id, 404);
        abort_unless($issue->isPublished() && $issue->volume->isPublished(), 404);

        $layout = ListLayout::fromRequest($request, ListLayout::LIST);

        $issue->load(['volume.journal']);

        $articles = Article::query()
            ->publicCatalog()
            ->where('issue_id', $issue->id)
            ->with(['authors', 'categories'])
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        $schemaArticles = Article::query()
            ->publicCatalog()
            ->where('issue_id', $issue->id)
            ->orderByDesc('published_at')
            ->get(['id', 'title', 'slug']);

        return view('public.journals.issue', compact('journal', 'issue', 'articles', 'schemaArticles', 'layout'));
    }

    public function announcements(Request $request, Journal $journal): View
    {
        abort_unless($journal->isListed(), 404);

        $layout = ListLayout::fromRequest($request);

        $announcements = $journal->announcements()
            ->where('is_published', true)
            ->with(['issue.volume'])
            ->orderByDesc('opens_at')
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        return view('public.journals.announcements', compact('journal', 'announcements', 'layout'));
    }

    public function announcement(Journal $journal, JournalAnnouncement $announcement): View
    {
        abort_unless($journal->isListed(), 404);
        abort_unless((int) $announcement->journal_id === (int) $journal->id && $announcement->is_published, 404);

        $announcement->load(['issue.volume', 'journal']);

        $submissionFees = collect();
        $publicationFees = collect();
        if ($announcement->isCallForSubmissions()) {
            $journalId = (int) $journal->id;
            $submissionFees = collect($this->fees->submissionFeesByJournalIds([$journalId])[$journalId] ?? []);
            $publicationFees = collect($this->fees->publicationFeesByJournalIds([$journalId])[$journalId] ?? []);
        }

        return view('public.journals.announcement', compact(
            'journal',
            'announcement',
            'submissionFees',
            'publicationFees',
        ));
    }

    public function join(Request $request, Journal $journal, JournalEnrollmentService $enrollment): RedirectResponse
    {
        JournalAuth::ensureActive($journal);

        $user = $request->user();
        $paidPlan = $enrollment->pendingPaidPlanForUser($user, (int) $journal->id);

        if ($paidPlan) {
            return redirect()
                ->route('memberships.checkout', $paidPlan)
                ->with('status', 'Complete your membership payment to access members-only content for this journal.');
        }

        $membership = $enrollment->ensureJournalAccess($user, $journal);

        return redirect()
            ->back()
            ->with('status', $membership
                ? 'You are now a member of '.$journal->title.'.'
                : 'You already have access to this journal.');
    }
}
