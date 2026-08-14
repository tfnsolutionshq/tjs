<?php

namespace App\Services\Storage;

use App\Models\Article;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\Volume;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArticleStorage
{
    public function articleDirectory(Article $article): string
    {
        $article->loadMissing(['journal', 'issue.volume']);
        $journalKey = $article->journal?->slug ?: (string) $article->journal_id;
        $volumeNumber = $article->issue?->volume?->volume_number ?? 'unassigned';
        $issueNumber = $article->issue?->issue_number ?? 'unassigned';

        return "journals/{$journalKey}/volumes/{$volumeNumber}/issues/{$issueNumber}/articles/{$article->id}";
    }

    public function storeGalley(Article $article, UploadedFile $file): string
    {
        $dir = $this->articleDirectory($article);
        $ext = strtolower($file->getClientOriginalExtension() ?: 'pdf');
        $filename = 'manuscript.'.$ext;
        $path = $file->storeAs($dir, $filename, 'local');

        // Keep previous as revision snapshot if replacing
        if ($article->document_path && $article->document_path !== $path && Storage::disk('local')->exists($article->document_path)) {
            $revDir = $dir.'/revisions';
            $stamp = now()->format('YmdHis');
            Storage::disk('local')->copy(
                $article->document_path,
                $revDir.'/r-'.$stamp.'-'.basename($article->document_path)
            );
        }

        $article->document_path = $path;
        $article->save();

        return $path;
    }

    public function storeSubmissionDocument(Journal $journal, string $submissionId, UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: 'pdf');
        $safe = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'manuscript';

        return $file->storeAs(
            "journals/{$journal->slug}/submissions/{$submissionId}",
            $safe.'.'.$ext,
            'local'
        );
    }

    public function storeVolumeCover(Volume $volume, UploadedFile $file): string
    {
        $journal = $volume->journal;
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');

        return $file->storeAs(
            "journals/{$journal->slug}/volumes/{$volume->volume_number}",
            'cover.'.$ext,
            'public'
        );
    }

    public function storeIssueCover(Issue $issue, UploadedFile $file): string
    {
        $issue->loadMissing('volume.journal');
        $journal = $issue->volume->journal;
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');

        return $file->storeAs(
            "journals/{$journal->slug}/volumes/{$issue->volume->volume_number}/issues/{$issue->issue_number}",
            'cover.'.$ext,
            'public'
        );
    }

    public function absolutePath(?string $relative): ?string
    {
        if (! $relative || ! Storage::disk('local')->exists($relative)) {
            return null;
        }

        return Storage::disk('local')->path($relative);
    }
}
