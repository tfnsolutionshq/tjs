<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\Journal;
use App\Models\Volume;
use App\Services\Storage\HybridDisk;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CatalogCoverController extends Controller
{
    public function __construct(
        private HybridDisk $disks,
    ) {
    }

    public function volume(Journal $journal, Volume $volume): BinaryFileResponse|StreamedResponse
    {
        abort_unless((int) $volume->journal_id === (int) $journal->id, 404);
        abort_unless($volume->cover_path, 404);

        return $this->respond($volume->cover_path, $volume->cover_disk, 'volume-'.$volume->volume_number.'-cover');
    }

    public function issue(Journal $journal, Issue $issue): BinaryFileResponse|StreamedResponse
    {
        $issue->loadMissing('volume');
        abort_unless($issue->volume && (int) $issue->volume->journal_id === (int) $journal->id, 404);
        abort_unless($issue->cover_path, 404);

        return $this->respond($issue->cover_path, $issue->cover_disk, 'issue-'.$issue->issue_number.'-cover');
    }

    private function respond(string $path, ?string $disk, string $basename): BinaryFileResponse|StreamedResponse
    {
        $located = $this->disks->locate($path, HybridDisk::KIND_MEDIA, $disk)
            ?? $this->disks->locate($path, HybridDisk::KIND_DOCUMENTS, $disk);

        abort_unless($located, 404);

        $name = $basename.'.'.(pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg');

        return $this->disks->filesystem($located)->response($path, $name, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
