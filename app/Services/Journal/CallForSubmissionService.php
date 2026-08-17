<?php

namespace App\Services\Journal;

use App\Models\Issue;
use App\Models\Journal;
use App\Models\JournalAnnouncement;
use App\Support\AnnouncementType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CallForSubmissionService
{
    public function openCallsQuery(): Builder
    {
        return JournalAnnouncement::query()
            ->where('type', AnnouncementType::CALL_FOR_SUBMISSIONS)
            ->where('is_published', true)
            ->whereNotNull('issue_id')
            ->where(function (Builder $query) {
                $query->whereNull('opens_at')->orWhere('opens_at', '<=', now());
            })
            ->where(function (Builder $query) {
                $query->whereNull('closes_at')->orWhere('closes_at', '>', now());
            })
            ->whereHas('journal', fn (Builder $journal) => $journal->where('is_active', true));
    }

    /**
     * @return Collection<int, JournalAnnouncement>
     */
    public function openCalls(?int $journalId = null): Collection
    {
        return $this->openCallsQuery()
            ->when($journalId, fn (Builder $query) => $query->where('journal_id', $journalId))
            ->with(['journal:id,title,slug,review_type', 'issue.volume'])
            ->orderBy('closes_at')
            ->orderByDesc('opens_at')
            ->get();
    }

    /**
     * Published calls that open in the future.
     *
     * @return Collection<int, JournalAnnouncement>
     */
    public function upcomingCalls(int $limit = 5): Collection
    {
        return JournalAnnouncement::query()
            ->where('type', AnnouncementType::CALL_FOR_SUBMISSIONS)
            ->where('is_published', true)
            ->whereNotNull('issue_id')
            ->whereNotNull('opens_at')
            ->where('opens_at', '>', now())
            ->whereHas('journal', fn (Builder $journal) => $journal->where('is_active', true))
            ->with(['journal:id,title,slug', 'issue.volume'])
            ->orderBy('opens_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Recently closed published calls.
     *
     * @return Collection<int, JournalAnnouncement>
     */
    public function recentlyClosedCalls(int $limit = 5): Collection
    {
        return JournalAnnouncement::query()
            ->where('type', AnnouncementType::CALL_FOR_SUBMISSIONS)
            ->where('is_published', true)
            ->whereNotNull('issue_id')
            ->whereNotNull('closes_at')
            ->where('closes_at', '<=', now())
            ->whereHas('journal', fn (Builder $journal) => $journal->where('is_active', true))
            ->with(['journal:id,title,slug', 'issue.volume'])
            ->orderByDesc('closes_at')
            ->limit($limit)
            ->get();
    }

    public function acceptsSubmissions(JournalAnnouncement $announcement): bool
    {
        if (! $announcement->is_published) {
            return false;
        }

        if (! AnnouncementType::isCallForSubmissions($announcement->type)) {
            return false;
        }

        if (! $announcement->issue_id) {
            return false;
        }

        $announcement->loadMissing('journal');

        if (! $announcement->journal?->is_active) {
            return false;
        }

        $now = now();

        if ($announcement->opens_at && $now->lt($announcement->opens_at)) {
            return false;
        }

        if ($announcement->closes_at && $now->gte($announcement->closes_at)) {
            return false;
        }

        return true;
    }

    public function statusLabel(JournalAnnouncement $announcement): string
    {
        if (! $announcement->is_published) {
            return 'Draft';
        }

        if (! AnnouncementType::isCallForSubmissions($announcement->type)) {
            return 'Published';
        }

        $now = now();

        if ($announcement->opens_at && $now->lt($announcement->opens_at)) {
            return 'Scheduled';
        }

        if ($announcement->closes_at && $now->gte($announcement->closes_at)) {
            return 'Closed';
        }

        return 'Open';
    }

    public function assertIssueBelongsToJournal(Journal $journal, ?Issue $issue): void
    {
        abort_unless(
            $issue && (int) $issue->volume?->journal_id === (int) $journal->id,
            422,
            'The selected issue does not belong to this journal.'
        );
    }

    public function findOpenCall(int $announcementId): JournalAnnouncement
    {
        $announcement = JournalAnnouncement::query()
            ->with(['journal', 'issue.volume'])
            ->findOrFail($announcementId);

        abort_unless($this->acceptsSubmissions($announcement), 422, 'This call for submissions is closed.');

        return $announcement;
    }
}
