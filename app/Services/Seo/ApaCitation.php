<?php

namespace App\Services\Seo;

use App\Models\Article;

class ApaCitation
{
    public function build(Article $article): array
    {
        $article->loadMissing(['authors', 'journal', 'issue.volume']);
        $names = $article->authors->sortBy('sort_order')->pluck('name')->filter()->values()->all();
        if ($names === [] && $article->authorUser) {
            $names = [$article->authorUser->name];
        }

        $year = $article->published_at?->format('Y') ?: 'n.d.';
        $title = $this->sentenceCase($article->title);
        $journalTitle = $article->journal?->title ?: config('tjs.name');
        $volume = $article->issue?->volume?->volume_number;
        $issue = $article->issue?->issue_number;
        $pages = $this->pageDisplay($article->page_range);
        $doi = $this->doiUrl($article->doi);
        $url = $doi ?: $article->publicUrl();

        $authorPart = $this->formatAuthors($names) ?: 'Anonymous';
        $volumeIssue = $volume ? ($issue ? "{$volume}({$issue})" : (string) $volume) : '';

        $reference = "{$authorPart} ({$year}). {$title}. {$journalTitle}";
        if ($volumeIssue) {
            $reference .= ", {$volumeIssue}";
        }
        if ($pages) {
            $reference .= ", {$pages}";
        }
        $reference .= '.';
        if ($url) {
            $reference .= " {$url}";
        }

        $surnames = array_map(fn ($n) => $this->surname($n), $names);
        $surnames = array_values(array_filter($surnames));

        if (count($surnames) === 0) {
            $parenthetical = "(Anonymous, {$year})";
            $narrative = "Anonymous ({$year})";
        } elseif (count($surnames) === 1) {
            $parenthetical = "({$surnames[0]}, {$year})";
            $narrative = "{$surnames[0]} ({$year})";
        } elseif (count($surnames) === 2) {
            $parenthetical = "({$surnames[0]} & {$surnames[1]}, {$year})";
            $narrative = "{$surnames[0]} and {$surnames[1]} ({$year})";
        } else {
            $parenthetical = "({$surnames[0]} et al., {$year})";
            $narrative = "{$surnames[0]} et al. ({$year})";
        }

        return [
            'style' => 'APA 7th edition',
            'reference' => $reference,
            'parenthetical' => $parenthetical,
            'narrative' => $narrative,
            'parts' => [
                'authorPart' => $authorPart,
                'year' => $year,
                'title' => $title,
                'journalTitle' => $journalTitle,
                'volume' => $volume ? (string) $volume : '',
                'issue' => $issue ? (string) $issue : '',
                'pages' => $pages,
                'sourceUrl' => $url,
            ],
        ];
    }

    private function toApaName(string $raw): string
    {
        $name = trim(preg_replace('/\s+/', ' ', $raw));
        if ($name === '') {
            return '';
        }
        if (str_contains($name, ',')) {
            [$surname, $given] = array_map('trim', explode(',', $name, 2));
            $initials = collect(preg_split('/\s+/', $given))->filter()
                ->map(fn ($p) => strtoupper(mb_substr($p, 0, 1)).'.')
                ->implode(' ');

            return $initials ? "{$surname}, {$initials}" : $surname;
        }
        $parts = preg_split('/\s+/', $name);
        if (count($parts) === 1) {
            return $parts[0];
        }
        $surname = array_pop($parts);
        $initials = collect($parts)->map(fn ($p) => strtoupper(mb_substr($p, 0, 1)).'.')->implode(' ');

        return "{$surname}, {$initials}";
    }

    private function formatAuthors(array $names): string
    {
        $formatted = array_values(array_filter(array_map(fn ($n) => $this->toApaName($n), $names)));
        $count = count($formatted);
        if ($count === 0) {
            return '';
        }
        if ($count === 1) {
            return $formatted[0];
        }
        if ($count === 2) {
            return "{$formatted[0]}, & {$formatted[1]}";
        }
        if ($count <= 20) {
            return implode(', ', array_slice($formatted, 0, -1)).', & '.$formatted[$count - 1];
        }

        return implode(', ', array_slice($formatted, 0, 19)).', ... '.$formatted[$count - 1];
    }

    private function sentenceCase(string $title): string
    {
        $lower = mb_strtolower(trim($title));

        return preg_replace_callback('/(^|[.!?]\s+|:\s*)([a-z])/u', function ($m) {
            return $m[1].mb_strtoupper($m[2]);
        }, $lower) ?: $lower;
    }

    private function surname(string $name): string
    {
        $name = trim($name);
        if (str_contains($name, ',')) {
            return trim(explode(',', $name)[0]);
        }
        $parts = preg_split('/\s+/', $name);

        return $parts[count($parts) - 1] ?? $name;
    }

    private function pageDisplay(?string $range): string
    {
        if (! $range) {
            return '';
        }
        $cleaned = preg_replace('/^pp?\.\s*/i', '', trim($range));
        $parts = preg_split('/\s*[-–—]\s*/', $cleaned);
        if (count($parts) >= 2) {
            return $parts[0].'–'.$parts[1];
        }

        return $cleaned;
    }

    private function doiUrl(?string $doi): ?string
    {
        if (! $doi) {
            return null;
        }
        $id = preg_replace('#^https?://(dx\.)?doi\.org/#i', '', trim($doi));
        $id = preg_replace('/^doi:\s*/i', '', $id);

        return $id ? 'https://doi.org/'.$id : null;
    }
}
