<?php

namespace App\Services\Storage;

use App\Models\Article;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\Volume;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ArticleStorage
{
    public function __construct(
        private HybridDisk $disks,
    ) {
    }

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

        if ($article->document_path && $this->disks->exists($article->document_path, HybridDisk::KIND_DOCUMENTS, $article->document_disk)) {
            $revDir = $dir.'/revisions';
            $stamp = now()->format('YmdHis');
            $this->disks->copy(
                $article->document_path,
                $revDir.'/r-'.$stamp.'-'.basename($article->document_path),
                HybridDisk::KIND_DOCUMENTS,
                $article->document_disk
            );
        }

        [$path, $disk] = $this->disks->storeAs($file, $dir, $filename, HybridDisk::KIND_DOCUMENTS);

        $article->document_path = $path;
        $article->document_disk = $disk;
        $article->save();

        return $path;
    }

    public function storeSubmissionDocument(Journal $journal, string $submissionId, UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: 'pdf');
        $safe = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'manuscript';

        return $this->disks->storeAs(
            $file,
            "journals/{$journal->slug}/submissions/{$submissionId}",
            $safe.'.'.$ext,
            HybridDisk::KIND_DOCUMENTS
        );
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    public function storeProductionDocument(Journal $journal, string $submissionId, int $version, UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: 'docx');
        $safe = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'production';

        return $this->disks->storeAs(
            $file,
            "journals/{$journal->slug}/submissions/{$submissionId}/production",
            'v'.$version.'-'.$safe.'.'.$ext,
            HybridDisk::KIND_DOCUMENTS
        );
    }

    public function storeVolumeCover(Volume $volume, UploadedFile $file): array
    {
        $journal = $volume->journal;
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');

        return $this->disks->storeAs(
            $file,
            "journals/{$journal->slug}/volumes/{$volume->volume_number}",
            'cover.'.$ext,
            HybridDisk::KIND_MEDIA
        );
    }

    public function storeIssueCover(Issue $issue, UploadedFile $file): array
    {
        $issue->loadMissing('volume.journal');
        $journal = $issue->volume->journal;
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');

        return $this->disks->storeAs(
            $file,
            "journals/{$journal->slug}/volumes/{$issue->volume->volume_number}/issues/{$issue->issue_number}",
            'cover.'.$ext,
            HybridDisk::KIND_MEDIA
        );
    }

    public function storeBrandAsset(Journal $journal, UploadedFile $file, string $name): array
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');

        return $this->disks->storeAs(
            $file,
            'journals/'.$journal->slug.'/branding',
            $name.'.'.$ext,
            HybridDisk::KIND_MEDIA
        );
    }

    public function storeAvatar(int|string $userId, UploadedFile $file): array
    {
        return $this->disks->store($file, 'avatars/'.$userId, HybridDisk::KIND_MEDIA);
    }

    public function documentExists(?string $path, ?string $disk = null): bool
    {
        return $path ? $this->disks->exists($path, HybridDisk::KIND_DOCUMENTS, $disk) : false;
    }

    public function absolutePath(?string $relative, ?string $disk = null): ?string
    {
        if (! $relative) {
            return null;
        }

        return $this->disks->localAbsolutePath($relative, HybridDisk::KIND_DOCUMENTS, $disk)
            ?? $this->disks->localAbsolutePath($relative, HybridDisk::KIND_MEDIA, $disk);
    }
}
