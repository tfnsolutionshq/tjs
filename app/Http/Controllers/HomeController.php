<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Journal;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    private const JOURNALS_PER_PAGE = 6;

    private const ARTICLES_PER_PAGE = 6;

    public function __invoke(Request $request): View
    {
        $journals = Journal::query()
            ->listed()
            ->orderByDesc('is_featured')
            ->orderBy('title')
            ->paginate(self::JOURNALS_PER_PAGE, ['*'], 'journal_page')
            ->withQueryString()
            ->fragment('journals');

        $latest = Article::query()
            ->publicCatalog()
            ->with(['journal', 'authors', 'issue.volume'])
            ->orderByDesc('published_at')
            ->paginate(self::ARTICLES_PER_PAGE, ['*'], 'article_page')
            ->withQueryString()
            ->fragment('latest');

        return view('public.home', compact('journals', 'latest'));
    }
}
