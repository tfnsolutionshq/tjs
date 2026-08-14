<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Issue;
use App\Models\Journal;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $journals = Journal::query()->where('is_active', true)->get();

        $xml = view('seo.sitemap-index', compact('journals'))->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    public function journal(Journal $journal): Response
    {
        abort_unless($journal->is_active, 404);

        $articles = Article::query()
            ->publicCatalog()
            ->where('journal_id', $journal->id)
            ->orderByDesc('published_at')
            ->get(['id', 'slug', 'journal_id', 'published_at', 'updated_at']);

        $issues = Issue::query()
            ->where('status', 'published')
            ->whereHas('volume', fn ($q) => $q->where('journal_id', $journal->id)->where('status', 'published'))
            ->with('volume')
            ->orderByDesc('updated_at')
            ->get();

        $xml = view('seo.sitemap-journal', compact('journal', 'articles', 'issues'))->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
