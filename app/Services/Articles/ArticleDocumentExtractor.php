<?php

namespace App\Services\Articles;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser as PdfParser;
use Throwable;

class ArticleDocumentExtractor
{
    /**
     * @return array{
     *     method: string,
     *     raw_text_preview: string,
     *     preview: array<string, mixed>,
     *     fields: array<string, string|null>,
     *     warnings: list<string>
     * }
     */
    public function extract(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');
        $warnings = [];

        [$text, $method] = match ($extension) {
            'pdf' => $this->extractFromPdf($file->getRealPath(), $warnings),
            'docx' => $this->extractFromDocx($file->getRealPath(), $warnings),
            'doc' => $this->extractFromLegacyDoc($warnings),
            'png', 'jpg', 'jpeg', 'webp', 'tif', 'tiff' => $this->extractFromImage($file->getRealPath(), $warnings),
            default => throw new \InvalidArgumentException('Unsupported file type. Use PDF, DOCX, or an image.'),
        };

        $normalized = $this->normalizeText($text);
        $normalized = $this->repairExtractedLines($normalized);
        $normalized = $this->deinterleaveFrontMatter($normalized);

        if (mb_strlen($normalized) < 40) {
            $warnings[] = 'Very little text was found. If this is a scanned document, install Tesseract OCR on the server for image recognition.';
        }

        $fields = $this->parseFields($normalized);
        $preview = $this->buildPreview($normalized, $fields);

        return [
            'method' => $method,
            'raw_text_preview' => $preview['plain'],
            'preview' => $preview,
            'fields' => $fields,
            'warnings' => $warnings,
        ];
    }

    /**
     * Parse already-extracted plain text (useful for tests and reuse).
     *
     * @return array{fields: array<string, string|null>, preview: array<string, mixed>}
     */
    public function parseText(string $text): array
    {
        $normalized = $this->repairExtractedLines($this->normalizeText($text));
        $normalized = $this->deinterleaveFrontMatter($normalized);
        $fields = $this->parseFields($normalized);

        return [
            'fields' => $fields,
            'preview' => $this->buildPreview($normalized, $fields),
        ];
    }

    public function tesseractAvailable(): bool
    {
        return $this->tesseractBinary() !== null;
    }

    /**
     * @param  list<string>  $warnings
     * @return array{0: string, 1: string}
     */
    private function extractFromPdf(string $path, array &$warnings): array
    {
        $text = '';

        try {
            $parser = new PdfParser;
            $pdf = $parser->parseFile($path);
            $text = $this->pdfTextInReadingOrder($pdf) ?: (string) $pdf->getText();
        } catch (Throwable $e) {
            $warnings[] = 'PDF text layer could not be read: '.$e->getMessage();
        }

        $normalized = $this->normalizeText($text);
        if (mb_strlen($normalized) >= 80) {
            return [$normalized, 'pdf_text'];
        }

        $ocr = $this->ocrWithTesseract($path, $warnings);
        if ($ocr !== '') {
            return [$ocr, 'pdf_ocr'];
        }

        if ($normalized !== '') {
            $warnings[] = 'Only a sparse PDF text layer was found. Results may be incomplete.';

            return [$normalized, 'pdf_text'];
        }

        $warnings[] = 'No extractable text in this PDF. Export a text-based PDF, or install Tesseract for scanned pages.';

        return ['', 'pdf_empty'];
    }

    /**
     * Prefer geometric reading order (top→bottom, left→right) when positions exist.
     */
    private function pdfTextInReadingOrder(object $pdf): string
    {
        try {
            $pages = $pdf->getPages();
        } catch (Throwable) {
            return '';
        }

        $chunks = [];

        foreach ($pages as $pageIndex => $page) {
            if ($pageIndex > 0) {
                break; // front-matter metadata lives on page 1
            }

            try {
                $data = $page->getDataTm();
            } catch (Throwable) {
                return '';
            }

            if (! is_array($data) || $data === []) {
                return '';
            }

            $items = [];
            foreach ($data as $row) {
                if (! is_array($row) || count($row) < 2) {
                    continue;
                }
                $tm = $row[0] ?? null;
                $str = trim((string) ($row[1] ?? ''));
                if ($str === '' || ! is_array($tm) || count($tm) < 6) {
                    continue;
                }
                $items[] = [
                    'x' => (float) $tm[4],
                    'y' => (float) $tm[5],
                    'text' => $str,
                ];
            }

            if ($items === []) {
                return '';
            }

            usort($items, function (array $a, array $b) {
                $yDiff = $b['y'] <=> $a['y'];
                if (abs($a['y'] - $b['y']) > 2) {
                    return $yDiff;
                }

                return $a['x'] <=> $b['x'];
            });

            $lineY = null;
            $lineParts = [];
            $lines = [];

            foreach ($items as $item) {
                if ($lineY === null || abs($item['y'] - $lineY) > 2.5) {
                    if ($lineParts !== []) {
                        $lines[] = trim(implode(' ', $lineParts));
                    }
                    $lineParts = [$item['text']];
                    $lineY = $item['y'];
                } else {
                    $lineParts[] = $item['text'];
                }
            }
            if ($lineParts !== []) {
                $lines[] = trim(implode(' ', $lineParts));
            }

            $chunks[] = implode("\n", array_filter($lines));
        }

        return trim(implode("\n\n", $chunks));
    }

    /**
     * @param  list<string>  $warnings
     * @return array{0: string, 1: string}
     */
    private function extractFromDocx(string $path, array &$warnings): array
    {
        if (! class_exists(\ZipArchive::class)) {
            $warnings[] = 'ZipArchive is required to read DOCX files.';

            return ['', 'docx_unavailable'];
        }

        $zip = new \ZipArchive;
        if ($zip->open($path) !== true) {
            $warnings[] = 'Could not open the DOCX archive.';

            return ['', 'docx_error'];
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false || $xml === '') {
            $warnings[] = 'DOCX did not contain word/document.xml.';

            return ['', 'docx_empty'];
        }

        $xml = preg_replace('/<\/w:p>/', "\n", $xml) ?? $xml;
        $xml = preg_replace('/<\/w:tr>/', "\n", $xml) ?? $xml;
        $text = strip_tags(str_replace(['<w:tab/>', '<w:br/>'], ["\t", "\n"], $xml));
        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return [$this->normalizeText($text), 'docx_text'];
    }

    /**
     * @param  list<string>  $warnings
     * @return array{0: string, 1: string}
     */
    private function extractFromLegacyDoc(array &$warnings): array
    {
        $warnings[] = 'Legacy .doc files are not supported for extraction. Convert to PDF or DOCX.';

        return ['', 'doc_unsupported'];
    }

    /**
     * @param  list<string>  $warnings
     * @return array{0: string, 1: string}
     */
    private function extractFromImage(string $path, array &$warnings): array
    {
        $ocr = $this->ocrWithTesseract($path, $warnings);
        if ($ocr !== '') {
            return [$ocr, 'image_ocr'];
        }

        $warnings[] = 'Image OCR needs Tesseract installed on the server (not detected).';

        return ['', 'image_ocr_unavailable'];
    }

    /**
     * @param  list<string>  $warnings
     */
    private function ocrWithTesseract(string $path, array &$warnings): string
    {
        $binary = $this->tesseractBinary();
        if ($binary === null) {
            return '';
        }

        $outBase = tempnam(sys_get_temp_dir(), 'tjs_ocr_');
        if ($outBase === false) {
            return '';
        }
        @unlink($outBase);

        $cmd = sprintf(
            '%s %s %s -l eng --psm 6 2>&1',
            escapeshellarg($binary),
            escapeshellarg($path),
            escapeshellarg($outBase)
        );

        exec($cmd, $output, $code);
        $txtPath = $outBase.'.txt';
        $text = is_file($txtPath) ? (string) file_get_contents($txtPath) : '';
        @unlink($txtPath);

        if ($code !== 0 || trim($text) === '') {
            $warnings[] = 'Tesseract OCR ran but returned little or no text.';
            if ($output !== []) {
                $warnings[] = Str::limit(implode(' ', $output), 180);
            }

            return '';
        }

        return $this->normalizeText($text);
    }

    private function tesseractBinary(): ?string
    {
        static $cached = false;
        static $binary = null;

        if ($cached) {
            return $binary;
        }
        $cached = true;

        $candidates = array_filter([
            env('TESSERACT_PATH'),
            'tesseract',
            'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
            'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe',
            '/usr/bin/tesseract',
            '/usr/local/bin/tesseract',
        ]);

        foreach ($candidates as $candidate) {
            if ($candidate === 'tesseract') {
                exec('tesseract --version 2>&1', $out, $code);
                if ($code === 0) {
                    $binary = 'tesseract';

                    return $binary;
                }

                continue;
            }

            if (is_file($candidate)) {
                $binary = $candidate;

                return $binary;
            }
        }

        return null;
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[\x{00A0}\x{2007}\x{202F}]/u', ' ', $text) ?? $text;
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * Rejoin PDF line-break artifacts (author superscripts, dangling commas, wrapped titles).
     */
    private function repairExtractedLines(string $text): string
    {
        $rawLines = preg_split('/\n+/', $text) ?: [];
        $lines = [];

        foreach ($rawLines as $raw) {
            $line = trim($raw);
            if ($line === '') {
                if ($lines !== [] && end($lines) !== '') {
                    $lines[] = '';
                }

                continue;
            }

            // Normalize unicode superscripts to digits for author markers
            $line = strtr($line, [
                '¹' => '1', '²' => '2', '³' => '3', '⁴' => '4', '⁵' => '5',
                '⁶' => '6', '⁷' => '7', '⁸' => '8', '⁹' => '9', '⁰' => '0',
            ]);

            if ($lines === []) {
                $lines[] = $line;

                continue;
            }

            $prev = $lines[count($lines) - 1];

            if (preg_match('/^\d{1,2}$/', $line) && preg_match('/[A-Za-z]$/', $prev)) {
                $lines[count($lines) - 1] = $prev.$line;

                continue;
            }

            if (preg_match('/^[,;]\s*/', $line) && $prev !== '') {
                $lines[count($lines) - 1] = rtrim($prev, ',; ').', '.ltrim($line, ',; ');

                continue;
            }

            // Title wrap: "... Methods and" + "Challenges"
            if (
                $prev !== ''
                && ! $this->isMetaLine($prev)
                && ! $this->isMetaLine($line)
                && ! $this->looksLikeAuthorLine($line)
                && (
                    preg_match('/\b(and|or|of|the|a|an|for|to|in|on|with|:|-)$/i', $prev)
                    || (preg_match('/[a-z,]$/', $prev) && preg_match('/^[a-z]/', $line))
                )
                && mb_strlen($prev) < 160
                && mb_strlen($line) < 120
            ) {
                $lines[count($lines) - 1] = rtrim($prev).' '.ltrim($line);

                continue;
            }

            $lines[] = $line;
        }

        $joined = implode("\n", $lines);
        $joined = preg_replace("/\n{3,}/", "\n\n", $joined) ?? $joined;

        return trim($joined);
    }

    /**
     * Pull sidebar labels out of abstract body when two-column PDF text is interleaved.
     */
    private function deinterleaveFrontMatter(string $text): string
    {
        // "Article Info ABSTRACT" / "ABSTRACT Article Info"
        $text = preg_replace('/\bArticle\s+Info\s+ABSTRACT\b/i', "Article Info\nABSTRACT", $text) ?? $text;
        $text = preg_replace('/\bABSTRACT\s+Article\s+Info\b/i', "Article Info\nABSTRACT", $text) ?? $text;

        // History lines glued into abstract sentences
        $text = preg_replace(
            '/\bArticle\s+history:\s*Received\s+(\d{4}-\d{2}-\d{2})\s+Revised\s+(\d{4}-\d{2}-\d{2})\s+Accepted\s+(\d{4}-\d{2}-\d{2})/i',
            "Article history:\nReceived $1\nRevised $2\nAccepted $3",
            $text
        ) ?? $text;

        return $text;
    }

    private function isMetaLine(string $line): bool
    {
        return (bool) preg_match(
            '/^(abstract|keywords?|key\s*words|index\s*terms|introduction|contents|doi|vol\.|volume|issue|received|revised|accepted|article\s+info|article\s+history|issn|journal\s+homepage|references)\b/i',
            $line
        );
    }

    private function looksLikeAuthorLine(string $line): bool
    {
        $line = $this->cleanInline($line);
        if ($line === '' || mb_strlen($line) < 8 || mb_strlen($line) > 220) {
            return false;
        }
        if ($this->isMetaLine($line) || preg_match('/\b(university|college|institute|journal|doi\.org|https?:)/i', $line)) {
            return false;
        }

        // "Name1, Name2, Name3" or with superscripts already normalized to digits
        $stripped = preg_replace('/(?<=[A-Za-z])\d{1,2}/', '', $line) ?? $line;
        $parts = preg_split('/\s*,\s*|\s+and\s+|\s+&\s+/i', $stripped) ?: [];
        $nameHits = 0;
        foreach ($parts as $part) {
            $part = trim($part, " \t.");
            if (preg_match('/^[A-Z][A-Za-z\'\-\.]+(?:\s+[A-Z][A-Za-z\'\-\.]+){1,5}$/', $part)) {
                $nameHits++;
            }
        }

        return $nameHits >= 2 || ($nameHits === 1 && str_contains($line, ','));
    }

    private function looksLikeTitleLine(string $line): bool
    {
        $line = $this->cleanInline($line);
        if ($line === '' || $this->isMetaLine($line) || $this->looksLikeAuthorLine($line)) {
            return false;
        }
        if (preg_match('/\b(journal of|university|college|institute|issn|doi\.org|homepage|vol\.?\s*\d+)/i', $line)) {
            return false;
        }
        if (preg_match('/^https?:/i', $line) || preg_match('/^\d+$/', $line)) {
            return false;
        }
        $len = mb_strlen($line);

        return $len >= 20 && $len <= 240;
    }

    /**
     * @param  array<string, string|null>  $fields
     * @return array<string, mixed>
     */
    private function buildPreview(string $text, array $fields): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\n+/', $text) ?: []), fn ($l) => $l !== ''));

        $journal = null;
        $citation = null;
        $issn = null;
        $homepage = null;
        $affiliation = null;
        $history = [];

        foreach ($lines as $line) {
            if ($journal === null && preg_match('/\bjournal\b/i', $line) && ! preg_match('/homepage|issn|doi/i', $line) && mb_strlen($line) > 20) {
                $journal = $this->cleanInline($line);
            }
            if ($citation === null && preg_match('/\bvol\.?\s*\d+/i', $line)) {
                $citation = preg_replace('/\s*,\s*https?:\/\/\S+/i', '', $line);
                $citation = $this->cleanInline((string) $citation);
            }
            if ($issn === null && preg_match('/\bISSN\s*([\d\-]+)/i', $line, $m)) {
                $issn = trim($m[1]);
            }
            if ($homepage === null && preg_match('/https?:\/\/\S+/i', $line, $m) && preg_match('/homepage|journal/i', $line)) {
                $homepage = rtrim($m[0], '.,);');
            }
            if (
                $affiliation === null
                && preg_match('/\b(university|college|institute|department|faculty|school)\b/i', $line)
                && ! preg_match('/\bjournal\b/i', $line)
            ) {
                $affiliation = preg_replace('/^[\d,\s]+/', '', $line) ?? $line;
                $affiliation = $this->cleanInline($affiliation);
            }
            if (preg_match('/\breceived\s+(\d{4}-\d{2}-\d{2})/i', $line, $m)) {
                $history['received'] = $m[1];
            }
            if (preg_match('/\brevised\s+(\d{4}-\d{2}-\d{2})/i', $line, $m)) {
                $history['revised'] = $m[1];
            }
            if (preg_match('/\baccepted\s+(\d{4}-\d{2}-\d{2})/i', $line, $m)) {
                $history['accepted'] = $m[1];
            }
        }

        $doi = $fields['doi'] ?? null;
        $authors = [];
        if (! empty($fields['authors_text'])) {
            $authors = array_values(array_filter(array_map('trim', preg_split('/\n+/', $fields['authors_text']) ?: [])));
        }

        $abstract = $fields['abstract'] ? Str::limit($fields['abstract'], 420, '…') : null;

        $plainParts = array_filter([
            $journal,
            $citation,
            $doi ? 'DOI '.$doi : null,
            $fields['title'] ?? null,
            $authors !== [] ? implode('; ', $authors) : null,
            $affiliation,
            $abstract ? 'Abstract — '.$abstract : null,
        ]);

        return [
            'plain' => implode("\n\n", $plainParts),
            'journal' => $journal,
            'citation' => $citation,
            'doi' => $doi,
            'doi_url' => $doi ? 'https://doi.org/'.$doi : null,
            'homepage' => $homepage,
            'issn' => $issn,
            'title' => $fields['title'] ?? null,
            'authors' => $authors,
            'affiliation' => $affiliation,
            'history' => $history,
            'abstract' => $abstract,
            'keywords' => $fields['keywords'] ?? null,
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function parseFields(string $text): array
    {
        if ($text === '') {
            return [
                'title' => null,
                'abstract' => null,
                'keywords' => null,
                'doi' => null,
                'authors_text' => null,
                'page_range' => null,
                'license' => null,
            ];
        }

        $structure = $this->parseFrontMatter($text);

        $doi = $structure['doi'];
        if ($doi === null) {
            if (preg_match('/(?:doi\.org\/|doi:\s*|DOI\s+)(10\.\d{4,9}\/[-._;()\/:A-Z0-9]+)/i', $text, $m)) {
                $doi = rtrim($m[1], '.,;)');
            } elseif (preg_match('/\b(10\.\d{4,9}\/[-._;()\/:A-Z0-9]+)\b/i', $text, $m)) {
                $doi = rtrim($m[1], '.,;)');
            }
        }

        $license = null;
        if (preg_match('/\b(CC[\s-]?BY(?:[\s-](?:NC|ND|SA))*(?:\s*\d(?:\.\d)?)?|Creative Commons(?: Attribution)?[^\n.]{0,40})/i', $text, $m)) {
            $license = $this->cleanInline($m[1]);
        }

        $pageRange = $structure['page_range'];
        if ($pageRange === null && preg_match('/\bpp?\.?\s*(\d{1,4}\s*[-–—]\s*\d{1,4})\b/i', $text, $m)) {
            $pageRange = preg_replace('/\s+/', '', str_replace(['–', '—'], '-', $m[1]));
        }

        return [
            'title' => $structure['title'],
            'abstract' => $structure['abstract'],
            'keywords' => $structure['keywords'],
            'doi' => $doi,
            'authors_text' => $structure['authors_text'],
            'page_range' => $pageRange,
            'license' => $license,
        ];
    }

    /**
     * Structure-aware front-matter parse for scholarly first pages.
     *
     * @return array{
     *     title: ?string,
     *     authors_text: ?string,
     *     abstract: ?string,
     *     keywords: ?string,
     *     doi: ?string,
     *     page_range: ?string
     * }
     */
    private function parseFrontMatter(string $text): array
    {
        $lines = array_values(array_filter(array_map(
            fn ($l) => $this->cleanInline($l),
            preg_split('/\n+/', $text) ?: []
        ), fn ($l) => $l !== ''));

        $authorIdx = null;
        foreach ($lines as $i => $line) {
            if ($this->looksLikeAuthorLine($line)) {
                $authorIdx = $i;
                break;
            }
        }

        $title = null;
        if ($authorIdx !== null) {
            $titleParts = [];
            for ($i = $authorIdx - 1; $i >= 0; $i--) {
                $line = $lines[$i];
                if ($this->isHeaderNoiseLine($line)) {
                    break;
                }
                if (! $this->looksLikeTitleLine($line) && $titleParts !== []) {
                    break;
                }
                if ($this->looksLikeTitleLine($line)) {
                    array_unshift($titleParts, $line);
                } elseif ($titleParts === []) {
                    continue;
                } else {
                    break;
                }
            }
            if ($titleParts !== []) {
                $title = $this->cleanInline(implode(' ', $titleParts));
            }
        }

        if ($title === null) {
            $title = $this->guessTitleFallback($lines);
        }

        // Drop accidental leading citation/vol lines from title
        if ($title && preg_match('/^Vol\.?\s*\d+/i', $title)) {
            $title = $this->guessTitleFallback($lines);
        }

        $authorsText = null;
        if ($authorIdx !== null) {
            $authorsText = $this->authorsFromLine($lines[$authorIdx]);
        }

        $keywords = $this->extractKeywords($text, $lines);
        $abstract = $this->extractAbstract($text);

        $doi = null;
        if (preg_match('/(?:doi\.org\/|doi:\s*)(10\.\d{4,9}\/[-._;()\/:A-Z0-9]+)/i', $text, $m)) {
            $doi = rtrim($m[1], '.,;)');
        }

        $pageRange = null;
        if (preg_match('/\bpp?\.?\s*(\d{1,4}\s*[-–—]\s*\d{1,4})\b/i', $text, $m)) {
            $pageRange = preg_replace('/\s+/', '', str_replace(['–', '—'], '-', $m[1]));
        }

        return [
            'title' => $title,
            'authors_text' => $authorsText,
            'abstract' => $abstract,
            'keywords' => $keywords,
            'doi' => $doi,
            'page_range' => $pageRange,
        ];
    }

    private function isHeaderNoiseLine(string $line): bool
    {
        if ($this->isMetaLine($line)) {
            return true;
        }

        return (bool) preg_match(
            '/\b(journal of|issn|doi\.org|journal\s+homepage|vol\.?\s*\d+|pp\.?\s*\d+)/i',
            $line
        );
    }

    /**
     * @param  list<string>  $lines
     */
    private function guessTitleFallback(array $lines): ?string
    {
        $best = null;
        $bestScore = -999;

        foreach ($lines as $line) {
            if (! $this->looksLikeTitleLine($line)) {
                continue;
            }
            $len = mb_strlen($line);
            $score = 100 - abs(90 - $len);
            if (str_contains($line, ':')) {
                $score += 20;
            }
            if (preg_match('/\b(review|analysis|study|foundations|methods|challenges|towards|using)\b/i', $line)) {
                $score += 12;
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $line;
            }
        }

        return $best;
    }

    private function authorsFromLine(string $line): ?string
    {
        $block = preg_replace('/(?<=[A-Za-z])\d{1,2}/', '', $line) ?? $line;
        $block = $this->cleanInline($block);
        $parts = preg_split('/\s*,\s*|\s+and\s+|\s+&\s+/i', $block) ?: [];
        $names = [];

        foreach ($parts as $part) {
            $part = trim($part, " \t.");
            $part = preg_replace('/\d+$/', '', $part) ?? $part;
            $part = trim($part);
            if ($part === '' || mb_strlen($part) < 4 || mb_strlen($part) > 80) {
                continue;
            }
            if (! preg_match('/^[A-Za-z][A-Za-z\'\-\.]+(?:\s+[A-Za-z][A-Za-z\'\-\.]+){0,5}$/', $part)) {
                continue;
            }
            // Reject leftover title fragments glued onto the author line
            if (preg_match('/\b(challenges|methods|foundations|review|analysis|science|data)\b/i', $part) && ! str_contains($part, ' ')) {
                continue;
            }
            $names[] = $part;
            if (count($names) >= 10) {
                break;
            }
        }

        return $names === [] ? null : implode("\n", $names);
    }

    /**
     * @param  list<string>  $lines
     */
    private function extractKeywords(string $text, array $lines): ?string
    {
        // Prefer a dedicated Keywords line with comma-separated phrases
        foreach ($lines as $i => $line) {
            if (! preg_match('/^keywords?\s*[:\-–]?\s*(.*)$/i', $line, $m)) {
                continue;
            }
            $rest = trim($m[1]);
            if ($rest === '' && isset($lines[$i + 1]) && ! $this->isMetaLine($lines[$i + 1])) {
                $rest = $lines[$i + 1];
            }
            $rest = $this->cleanKeywordBlob($rest);
            if ($rest !== null) {
                return $rest;
            }
        }

        if (preg_match('/\bkeywords?\s*[:\-–]\s*(.+?)(?=\b(?:abstract|introduction|1\.?\s+introduction|references|article\s+history)\b|$)/is', $text, $m)) {
            return $this->cleanKeywordBlob($m[1]);
        }

        return null;
    }

    private function cleanKeywordBlob(string $raw): ?string
    {
        $raw = $this->cleanInline($raw);
        $raw = preg_replace('/\b(article\s+history|received|revised|accepted|abstract|introduction).*$/i', '', $raw) ?? $raw;
        $raw = trim($raw, " \t:-–");

        if ($raw === '' || mb_strlen($raw) < 3) {
            return null;
        }

        // Reject blobs that look like abstract prose
        if (str_word_count($raw) > 40 || preg_match('/\b(has emerged|this paper|in this study)\b/i', $raw)) {
            // Keep only leading comma-separated title-case phrases if present
            if (preg_match('/^((?:[A-Z][^,]{2,60},\s*){1,8}[A-Z][^,]{2,60})/', $raw, $m)) {
                $raw = $m[1];
            } else {
                return null;
            }
        }

        return Str::limit($this->cleanInline($raw), 500, '');
    }

    private function extractAbstract(string $text): ?string
    {
        $chunk = $text;
        if (preg_match('/\babstract\b\s*[:\-–]?\s*(.+)$/is', $text, $m)) {
            $chunk = $m[1];
        }

        // Drop sidebar / history blocks that two-column PDFs interleave before the prose
        $chunk = preg_replace('/\bArticle\s+Info\b/i', "\n", $chunk) ?? $chunk;
        $chunk = preg_replace(
            '/\bArticle\s+history\s*:?\s*(?:Received\s+\d{4}-\d{2}-\d{2}\s*)?(?:Revised\s+\d{4}-\d{2}-\d{2}\s*)?(?:Accepted\s+\d{4}-\d{2}-\d{2}\s*)?/i',
            "\n",
            $chunk
        ) ?? $chunk;
        $chunk = preg_replace('/\bReceived\s+\d{4}-\d{2}-\d{2}\b/i', "\n", $chunk) ?? $chunk;
        $chunk = preg_replace('/\bRevised\s+\d{4}-\d{2}-\d{2}\b/i', "\n", $chunk) ?? $chunk;
        $chunk = preg_replace('/\bAccepted\s+\d{4}-\d{2}-\d{2}\b/i', "\n", $chunk) ?? $chunk;

        // Keywords are often a short comma list before the abstract prose in interleaved extracts
        $chunk = preg_replace(
            '/\bkeywords?\s*[:\-–]?\s*((?:[^.\n]{0,80},\s*){0,10}[^.\n]{0,80})(?=\s+(?:[A-Z][a-z]+(?:\s+[A-Z][a-z]+){0,3}\s+has\b|This\s+(?:paper|study|article|review)\b|We\s+|In\s+this\b)|\s*$)/is',
            "\n",
            $chunk
        ) ?? $chunk;

        if (preg_match('/^(.*?)(?=\n\s*(?:introduction|1\.?\s+introduction|references|i\.\s+introduction)\b)/is', $chunk, $cut)) {
            $chunk = $cut[1];
        }

        $chunk = $this->cleanInline($chunk);

        // Prefer a clear scholarly prose opener
        if (preg_match('/\b((?:Big Data(?:\s+Science)?|This\s+(?:paper|study|article|review)|We\s+(?:present|propose|investigate|examine)|In\s+this\s+(?:paper|study|article|review))\b.+)$/is', $chunk, $prose)) {
            $chunk = $this->cleanInline($prose[1]);
        }

        // Cut trailing keywords if they leaked after prose
        $chunk = preg_replace('/\bKeywords?\s*[:\-–].*$/i', '', $chunk) ?? $chunk;
        $chunk = $this->cleanInline($chunk);

        if ($chunk === '' || mb_strlen($chunk) < 25) {
            return null;
        }

        return Str::limit($chunk, 5000, '');
    }

    private function cleanInline(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }
}
