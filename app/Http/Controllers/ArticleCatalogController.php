<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArticleCatalogController extends Controller
{
    public function __invoke(Request $request): View
    {
        $articles = Article::query()
            ->publicCatalog()
            ->with(['journal', 'authors', 'issue.volume'])
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('public.articles.index', compact('articles'));
    }
}
