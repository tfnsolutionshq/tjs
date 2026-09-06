<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Journal;
use App\Services\Access\ArticleAccessResolver;
use App\Services\Seo\ApaCitation;
use App\Services\Seo\ScholarlyMeta;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $journals = Journal::query()
            ->listed()
            ->orderByDesc('is_featured')
            ->orderBy('title')
            ->get();

        $latest = Article::query()
            ->publicCatalog()
            ->with(['journal', 'authors', 'issue.volume'])
            ->orderByDesc('published_at')
            ->limit(8)
            ->get();

        return view('public.home', compact('journals', 'latest'));
    }
}
