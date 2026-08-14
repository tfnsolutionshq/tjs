{{-- Expects $parts from ApaCitation::build()['parts'] --}}
@php
    $authorPart = e((string) ($parts['authorPart'] ?? ''));
    $year = e((string) ($parts['year'] ?? ''));
    $title = e((string) ($parts['title'] ?? ''));
    $journalTitle = e((string) ($parts['journalTitle'] ?? ''));
    $volume = (string) ($parts['volume'] ?? '');
    $issueNo = (string) ($parts['issue'] ?? '');
    $pages = (string) ($parts['pages'] ?? '');
    $sourceUrl = (string) ($parts['sourceUrl'] ?? '');

    echo "{$authorPart} ({$year}). {$title}. <em>{$journalTitle}</em>";

    if ($volume !== '') {
        echo ', <em>'.e($volume).'</em>';
        if ($issueNo !== '') {
            echo '('.e($issueNo).')';
        }
    }

    if ($pages !== '') {
        echo ', '.e($pages);
    }

    echo '.';

    if ($sourceUrl !== '') {
        $safeUrl = e($sourceUrl);
        echo ' <a class="j-link break-all" href="'.$safeUrl.'" target="_blank" rel="noopener">'.$safeUrl.'</a>';
    }
@endphp
