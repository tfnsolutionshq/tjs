<?php

namespace App\Services\Journal;

use App\Models\Issue;
use App\Models\JournalFee;
use App\Support\JournalFeePurpose;

class JournalFeeResolver
{
    public function findForJournal(int $journalId, ?int $feeId, string $purpose): ?JournalFee
    {
        if (! $feeId) {
            return null;
        }

        if (! in_array($purpose, JournalFeePurpose::all(), true)) {
            return null;
        }

        return JournalFee::query()
            ->where('journal_id', $journalId)
            ->where('id', $feeId)
            ->active()
            ->forPurpose($purpose)
            ->first();
    }

    /**
     * Active submission fee authors must pay when submitting to this journal.
     * Returns null when the journal has no paid submission fee configured.
     */
    public function requiredSubmissionFeeForJournal(int $journalId): ?JournalFee
    {
        return JournalFee::query()
            ->where('journal_id', $journalId)
            ->active()
            ->forPurpose(JournalFeePurpose::SUBMISSION)
            ->where('amount', '>=', 1)
            ->orderBy('amount')
            ->orderBy('name')
            ->first();
    }

    /**
     * Active publication fee (APC) required for the issue, if configured.
     */
    public function requiredPublicationFeeForIssue(?int $issueId): ?JournalFee
    {
        if (! $issueId) {
            return null;
        }

        $issue = Issue::query()
            ->with('journalFee')
            ->find($issueId);

        if (! $issue?->journalFee) {
            return null;
        }

        $fee = $issue->journalFee;

        if (! $fee->is_active || $fee->purpose !== JournalFeePurpose::ARTICLE || (int) $fee->amount < 1) {
            return null;
        }

        return $fee;
    }

    /**
     * @return array<string, list<array{id: int, name: string, amount: int, currency: string}>>
     */
    public function articleFeesByJournal(): array
    {
        return JournalFee::query()
            ->active()
            ->forPurpose(JournalFeePurpose::ARTICLE)
            ->orderBy('name')
            ->get(['id', 'journal_id', 'name', 'amount', 'currency'])
            ->groupBy('journal_id')
            ->map(fn ($group) => $group->map(fn (JournalFee $fee) => [
                'id' => $fee->id,
                'name' => $fee->name,
                'amount' => (int) $fee->amount,
                'currency' => strtoupper((string) $fee->currency),
            ])->values()->all())
            ->all();
    }

    /**
     * @return array<string, list<array{id: int, name: string, amount: int, currency: string, description: string|null}>>
     */
    public function submissionFeesByJournalIds(array $journalIds): array
    {
        return $this->feesByJournalIds($journalIds, JournalFeePurpose::SUBMISSION);
    }

    /**
     * @return array<string, list<array{id: int, name: string, amount: int, currency: string, description: string|null}>>
     */
    public function publicationFeesByJournalIds(array $journalIds): array
    {
        return $this->feesByJournalIds($journalIds, JournalFeePurpose::ARTICLE);
    }

    /**
     * @return array<string, list<array{id: int, name: string, amount: int, currency: string, description: string|null}>>
     */
    private function feesByJournalIds(array $journalIds, string $purpose): array
    {
        if ($journalIds === []) {
            return [];
        }

        return JournalFee::query()
            ->active()
            ->forPurpose($purpose)
            ->whereIn('journal_id', $journalIds)
            ->orderBy('name')
            ->get(['id', 'journal_id', 'name', 'amount', 'currency', 'description'])
            ->groupBy('journal_id')
            ->map(fn ($group) => $group->map(fn (JournalFee $fee) => [
                'id' => $fee->id,
                'name' => $fee->name,
                'amount' => (int) $fee->amount,
                'currency' => strtoupper((string) $fee->currency),
                'description' => $fee->description,
            ])->values()->all())
            ->all();
    }
}
