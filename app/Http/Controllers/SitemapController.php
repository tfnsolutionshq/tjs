<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\JournalAnnouncement;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $journals = Journal::query()
            ->listed()
            ->orderBy('title')
            ->get(['id', 'slug', 'updated_at']);

        $xml = view('seo.sitemap-index', compact('journals'))->render();

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function site(): Response
    {
        $xml = view('seo.sitemap-site')->render();

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function journal(Journal $journal): Response
    {
        abort_unless($journal->isListed(), 404);

        $articles = Article::query()
            ->publicCatalog()
            ->where('journal_id', $journal->id)
            ->orderByDesc('published_at')
            ->get(['id', 'slug', 'journal_id', 'published_at', 'updated_at', 'visibility']);

        $issues = Issue::query()
            ->where('status', 'published')
            ->whereHas('volume', fn ($q) => $q->where('journal_id', $journal->id)->where('status', 'published'))
            ->with('volume')
            ->orderByDesc('updated_at')
            ->get();

        $announcements = JournalAnnouncement::query()
            ->where('journal_id', $journal->id)
            ->where('is_published', true)
            ->orderByDesc('updated_at')
            ->get(['id', 'updated_at']);

        $xml = view('seo.sitemap-journal', compact('journal', 'articles', 'issues', 'announcements'))->render();

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
