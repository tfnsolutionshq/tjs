<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\Journal;
use App\Models\Volume;
use App\Services\Storage\ArticleStorage;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CatalogCoverController extends Controller
{
    public function __construct(
        private ArticleStorage $storage,
    ) {
    }

    public function volume(Journal $journal, Volume $volume): BinaryFileResponse
    {
        abort_unless((int) $volume->journal_id === (int) $journal->id, 404);
        abort_unless($volume->cover_path, 404);

        return $this->respond($volume->cover_path, 'volume-'.$volume->volume_number.'-cover');
    }

    public function issue(Journal $journal, Issue $issue): BinaryFileResponse
    {
        $issue->loadMissing('volume');
        abort_unless($issue->volume && (int) $issue->volume->journal_id === (int) $journal->id, 404);
        abort_unless($issue->cover_path, 404);

        return $this->respond($issue->cover_path, 'issue-'.$issue->issue_number.'-cover');
    }

    private function respond(string $path, string $basename): BinaryFileResponse
    {
        if (Storage::disk('public')->exists($path)) {
            $absolute = Storage::disk('public')->path($path);
        } else {
            $absolute = $this->storage->absolutePath($path);
        }

        abort_unless($absolute && is_file($absolute), 404);

        $mime = mime_content_type($absolute) ?: 'image/jpeg';
        $ext = pathinfo($absolute, PATHINFO_EXTENSION) ?: 'jpg';

        return response()->file($absolute, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$basename.'.'.$ext.'"',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
