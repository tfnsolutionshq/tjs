<?php

namespace App\Services\Journal;

use App\Models\Journal;
use App\Models\JournalReviewerRequest;
use App\Models\User;
use App\Support\JournalTeamRoles;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewerRequestService
{
    /**
     * @return array{0: bool, 1: string|null}
     */
    public function canRequest(User $user, Journal $journal): array
    {
        if ($user->isAdmin()) {
            return [false, 'Platform admins already have full access.'];
        }

        $teamRole = $user->journalTeamRole($journal);

        if ($teamRole === JournalTeamRoles::REVIEWER) {
            return [false, 'You are already a reviewer for this journal.'];
        }

        if ($teamRole !== null && in_array($teamRole, JournalTeamRoles::manageRoles(), true)) {
            return [false, 'You already have an editorial role on this journal.'];
        }

        if (! $user->isJournalMember($journal)) {
            return [false, 'You need an active journal membership or a manuscript submission for this journal first.'];
        }

        if ($this->pendingRequest($user, $journal)) {
            return [false, 'You already have a pending request for this journal.'];
        }

        return [true, null];
    }

    public function submit(User $user, Journal $journal, ?string $message = null): JournalReviewerRequest
    {
        [$allowed, $reason] = $this->canRequest($user, $journal);

        if (! $allowed) {
            throw ValidationException::withMessages([
                'reviewer_request' => $reason ?? 'You cannot request reviewer access for this journal.',
            ]);
        }

        return JournalReviewerRequest::query()->create([
            'journal_id' => $journal->id,
            'user_id' => $user->id,
            'status' => JournalReviewerRequest::STATUS_PENDING,
            'message' => $message !== null ? trim($message) : null,
        ]);
    }

    public function withdraw(User $user, Journal $journal): void
    {
        $request = $this->pendingRequest($user, $journal);

        if (! $request) {
            throw ValidationException::withMessages([
                'reviewer_request' => 'No pending request found for this journal.',
            ]);
        }

        $request->update([
            'status' => JournalReviewerRequest::STATUS_WITHDRAWN,
            'reviewed_at' => now(),
        ]);
    }

    public function approve(JournalReviewerRequest $request, User $reviewer, ?string $adminNote = null): void
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'reviewer_request' => 'This request is no longer pending.',
            ]);
        }

        DB::transaction(function () use ($request, $reviewer, $adminNote): void {
            $request->journal->assignTeamMember($request->user, JournalTeamRoles::REVIEWER);

            $request->update([
                'status' => JournalReviewerRequest::STATUS_APPROVED,
                'admin_note' => $adminNote !== null ? trim($adminNote) : null,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);
        });
    }

    public function reject(JournalReviewerRequest $request, User $reviewer, ?string $adminNote = null): void
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'reviewer_request' => 'This request is no longer pending.',
            ]);
        }

        $request->update([
            'status' => JournalReviewerRequest::STATUS_REJECTED,
            'admin_note' => $adminNote !== null ? trim($adminNote) : null,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);
    }

    public function pendingRequest(User $user, Journal $journal): ?JournalReviewerRequest
    {
        return JournalReviewerRequest::query()
            ->where('journal_id', $journal->id)
            ->where('user_id', $user->id)
            ->pending()
            ->latest('id')
            ->first();
    }

    /**
     * Latest request for a user on a journal, regardless of status.
     */
    public function latestRequest(User $user, Journal $journal): ?JournalReviewerRequest
    {
        return JournalReviewerRequest::query()
            ->where('journal_id', $journal->id)
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();
    }

    /**
     * @return array{canRequest: bool, reason: string|null, pending: JournalReviewerRequest|null, latest: JournalReviewerRequest|null}
     */
    public function contextFor(User $user, Journal $journal): array
    {
        [$canRequest, $reason] = $this->canRequest($user, $journal);

        return [
            'canRequest' => $canRequest,
            'reason' => $reason,
            'pending' => $this->pendingRequest($user, $journal),
            'latest' => $this->latestRequest($user, $journal),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{journal: Journal, canRequest: bool, reason: string|null, pending: JournalReviewerRequest|null, latest: JournalReviewerRequest|null}>
     */
    public function memberJournalContexts(User $user): \Illuminate\Support\Collection
    {
        $journalIds = $user->memberships()
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->whereNotNull('journal_id')
            ->pluck('journal_id');

        $journalIds = $journalIds->merge(
            $user->submissions()->distinct()->pluck('journal_id')
        )->unique()->filter();

        return Journal::query()
            ->whereIn('id', $journalIds)
            ->orderBy('title')
            ->get()
            ->map(function (Journal $journal) use ($user) {
                $context = $this->contextFor($user, $journal);
                $context['journal'] = $journal;

                return $context;
            })
            ->filter(function (array $row) use ($user) {
                if ($row['pending']) {
                    return true;
                }

                if ($row['canRequest']) {
                    return true;
                }

                $teamRole = $user->journalTeamRole($row['journal']);

                return $teamRole === \App\Support\JournalTeamRoles::REVIEWER
                    || ($row['latest'] && $row['latest']->status === JournalReviewerRequest::STATUS_REJECTED);
            })
            ->values();
    }
}
