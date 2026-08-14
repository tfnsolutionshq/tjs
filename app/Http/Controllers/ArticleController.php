<?php

namespace App\Http\Controllers;

use App\Models\AccessLog;
use App\Models\Article;
use App\Models\Journal;
use App\Services\Access\ArticleAccessResolver;
use App\Services\Seo\ApaCitation;
use App\Services\Seo\PdfMetadataStamper;
use App\Services\Seo\ScholarlyMeta;
use App\Services\Storage\ArticleStorage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ArticleController extends Controller
{
    public function __construct(
        private ArticleAccessResolver $access,
        private ScholarlyMeta $scholarlyMeta,
        private ApaCitation $apaCitation,
        private ArticleStorage $storage,
        private PdfMetadataStamper $pdfStamper,
    ) {
    }

    public function show(Request $request, Journal $journal, Article $article)
    {
        abort_unless((int) $article->journal_id === (int) $journal->id, 404);
        abort_unless($this->access->canViewMetadata($request->user(), $article), 404);

        $article->load(['authors', 'issue.volume', 'journal', 'categories']);
        $meta = $this->scholarlyMeta->forArticle($article);
        $apa = $this->apaCitation->build($article);
        $user = $request->user();
        $canAccess = $this->access->canAccessFullText($user, $article);
        $denial = $canAccess ? null : $this->access->denialReason($user, $article);
        $accessViaPurchase = $user ? $this->access->hasActivePurchase($user, $article) : false;

        return view('public.articles.show', compact(
            'journal',
            'article',
            'meta',
            'apa',
            'canAccess',
            'denial',
            'accessViaPurchase',
        ));
    }

    public function pdfViewer(Request $request, Journal $journal, Article $article)
    {
        abort_unless((int) $article->journal_id === (int) $journal->id, 404);
        abort_unless($this->access->canAccessFullText($request->user(), $article), 403, $this->access->denialReason($request->user(), $article));
        abort_unless($article->document_path && Storage::disk('local')->exists($article->document_path), 404, 'Document not found.');

        $article->load(['authors', 'issue.volume', 'journal', 'categories']);
        $meta = $this->scholarlyMeta->forArticle($article);
        $pdfStreamUrl = route('journals.articles.pdf', [$journal, $article->slug]);

        return view('public.articles.pdf-viewer', compact('journal', 'article', 'meta', 'pdfStreamUrl'));
    }

    public function pdf(Request $request, Journal $journal, string $articleSlug): BinaryFileResponse|Response
    {
        $slug = str_ends_with(strtolower($articleSlug), '.pdf')
            ? substr($articleSlug, 0, -4)
            : $articleSlug;

        $article = Article::query()
            ->where('journal_id', $journal->id)
            ->where('slug', $slug)
            ->firstOrFail();

        abort_unless($this->access->canAccessFullText($request->user(), $article), 403, $this->access->denialReason($request->user(), $article));

        // Update-before-view: refresh path resolution + access stamp
        $article->refresh();
        $path = $article->document_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404, 'Document not found.');

        $article->forceFill(['last_accessed_at' => now()])->save();

        AccessLog::create([
            'article_id' => $article->id,
            'user_id' => $request->user()?->id,
            'action' => 'pdf_view',
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'metadata' => ['path' => $path],
        ]);

        $absolute = Storage::disk('local')->path($path);
        if ($isPdf = str_ends_with(strtolower($path), '.pdf')) {
            $article->loadMissing(['authors', 'journal', 'issue.volume']);
            $absolute = $this->pdfStamper->stampedPath($article, $absolute);
        }

        $filename = ($article->slug ?: $article->id).'.pdf';
        $gated = $article->visibility !== 'open';

        return response()->file($absolute, [
            'Content-Type' => $isPdf ? 'application/pdf' : 'application/octet-stream',
            'Content-Disposition' => ($isPdf ? 'inline' : 'attachment').'; filename="'.$filename.'"',
            'Cache-Control' => $gated ? 'private, no-store' : 'public, max-age=3600',
            'X-Robots-Tag' => $article->visibility === 'open' ? 'all' : 'noindex',
        ]);
    }
}
