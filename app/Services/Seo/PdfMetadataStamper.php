<?php

namespace App\Services\Seo;

use App\Models\Article;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use setasign\Fpdi\Tcpdf\Fpdi;
use Throwable;

class PdfMetadataStamper
{
    public function __construct(
        private ScholarlyMeta $scholarlyMeta,
    ) {
    }

    /**
     * Return an absolute path to a PDF stamped with scholarly Info/XMP metadata.
     * Falls back to the original file if stamping fails (e.g. encrypted PDFs).
     */
    public function stampedPath(Article $article, string $sourceAbsolutePath): string
    {
        if (! is_file($sourceAbsolutePath) || ! str_ends_with(strtolower($sourceAbsolutePath), '.pdf')) {
            return $sourceAbsolutePath;
        }

        $meta = $this->scholarlyMeta->forArticle($article);
        $fingerprint = md5(implode('|', [
            $sourceAbsolutePath,
            (string) filemtime($sourceAbsolutePath),
            (string) filesize($sourceAbsolutePath),
            $article->updated_at?->timestamp,
            $article->title,
            $article->doi,
            $article->abstract,
            $article->keywords,
            implode(',', $meta['citation_authors'] ?? []),
        ]));

        $relative = "pdf-meta/{$article->id}/{$fingerprint}.pdf";
        if (Storage::disk('local')->exists($relative)) {
            return Storage::disk('local')->path($relative);
        }

        try {
            $binary = $this->buildStampedPdf($article, $meta, $sourceAbsolutePath);
            Storage::disk('local')->put($relative, $binary);

            return Storage::disk('local')->path($relative);
        } catch (Throwable $e) {
            report($e);

            return $sourceAbsolutePath;
        }
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function buildStampedPdf(Article $article, array $meta, string $sourceAbsolutePath): string
    {
        $pdf = new Fpdi;
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false);

        $authors = implode(', ', $meta['citation_authors'] ?? []);
        $keywords = implode(', ', $meta['citation_keywords'] ?? []);
        $subjectParts = array_filter([
            $meta['citation_journal_title'] ?? null,
            isset($meta['citation_volume']) ? 'Vol. '.$meta['citation_volume'] : null,
            isset($meta['citation_issue']) ? 'No. '.$meta['citation_issue'] : null,
            ! empty($meta['doi']) ? 'doi:'.$meta['doi'] : null,
        ]);
        $subject = implode(' · ', $subjectParts);
        if ($subject === '' && ! empty($meta['description'])) {
            $subject = (string) $meta['description'];
        }

        $pdf->SetCreator(config('tjs.full_name', 'TJS'));
        $pdf->SetAuthor($authors !== '' ? $authors : (config('tjs.publisher') ?? 'Unknown'));
        $pdf->SetTitle((string) $article->title);
        $pdf->SetSubject(Str::limit($subject, 250, ''));
        $pdf->SetKeywords($keywords !== '' ? $keywords : Str::limit(strip_tags((string) $article->abstract), 200, ''));

        // Richer XMP block for DOI / journal / license (visible in Adobe PDF properties)
        if (method_exists($pdf, 'setExtraXmpRdf')) {
            $pdf->setExtraXmpRdf($this->extraXmp($article, $meta, $authors));
        }

        $pageCount = $pdf->setSourceFile($sourceAbsolutePath);
        for ($page = 1; $page <= $pageCount; $page++) {
            $templateId = $pdf->importPage($page);
            $size = $pdf->getTemplateSize($templateId);
            $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
        }

        return $pdf->Output('', 'S');
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function extraXmp(Article $article, array $meta, string $authors): string
    {
        $esc = static fn (?string $v): string => htmlspecialchars((string) $v, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $doi = $meta['doi'] ?? null;
        $journal = $meta['citation_journal_title'] ?? null;
        $issn = $meta['citation_issn'] ?? null;
        $license = $meta['license'] ?? null;
        $publisher = $meta['citation_publisher'] ?? config('tjs.publisher');
        $url = $meta['canonical'] ?? $article->publicUrl();
        $pdfUrl = $meta['citation_pdf_url'] ?? $article->pdfUrl();

        $parts = [
            '<dc:title><rdf:Alt><rdf:li xml:lang="x-default">'.$esc($article->title).'</rdf:li></rdf:Alt></dc:title>',
            '<dc:creator><rdf:Seq><rdf:li>'.$esc($authors).'</rdf:li></rdf:Seq></dc:creator>',
            '<dc:description><rdf:Alt><rdf:li xml:lang="x-default">'.$esc(Str::limit(strip_tags((string) $article->abstract), 500, '')).'</rdf:li></rdf:Alt></dc:description>',
            '<dc:publisher><rdf:Bag><rdf:li>'.$esc($publisher).'</rdf:li></rdf:Bag></dc:publisher>',
            '<dc:source>'.$esc($journal).'</dc:source>',
            '<dc:identifier>'.$esc($doi ? 'https://doi.org/'.$doi : $url).'</dc:identifier>',
            '<dc:rights>'.$esc($license).'</dc:rights>',
            '<xmp:CreatorTool>'.$esc(config('tjs.full_name')).'</xmp:CreatorTool>',
            '<pdfx:doi>'.$esc($doi).'</pdfx:doi>',
            '<pdfx:issn>'.$esc($issn).'</pdfx:issn>',
            '<pdfx:journal>'.$esc($journal).'</pdfx:journal>',
            '<pdfx:articleurl>'.$esc($url).'</pdfx:articleurl>',
            '<pdfx:pdfurl>'.$esc($pdfUrl).'</pdfx:pdfurl>',
            '<pdfx:volume>'.$esc($meta['citation_volume'] ?? null).'</pdfx:volume>',
            '<pdfx:issue>'.$esc($meta['citation_issue'] ?? null).'</pdfx:issue>',
            '<pdfx:license>'.$esc($license).'</pdfx:license>',
        ];

        return implode("\n", $parts);
    }
}
