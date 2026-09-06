<?php

namespace App\Services\Doi;

use App\Models\DoiCreditTopup;
use App\Models\Journal;
use App\Models\User;
use App\Notifications\DoiCreditsLowNotification;
use App\Support\DoiSettings;
use App\Support\JournalTeamRoles;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DoiCreditService
{
    public function topUp(
        Journal $journal,
        int $credits,
        ?User $actor = null,
        ?float $amountUsd = null,
        ?int $amountNgn = null,
        ?string $note = null,
    ): DoiCreditTopup {
        if ($credits < 1) {
            throw new RuntimeException('Top-up must add at least 1 credit.');
        }

        return DB::transaction(function () use ($journal, $credits, $actor, $amountUsd, $amountNgn, $note) {
            /** @var Journal $locked */
            $locked = Journal::query()->whereKey($journal->id)->lockForUpdate()->firstOrFail();

            $locked->doi_credits_balance = (int) $locked->doi_credits_balance + $credits;
            $locked->doi_credits_lifetime = (int) $locked->doi_credits_lifetime + $credits;
            $locked->doi_low_balance_notified_at = null;
            $locked->doi_exhausted_notified_at = null;
            $locked->save();

            $rate = DoiSettings::usdToNgn();
            $usd = $amountUsd;
            $ngn = $amountNgn;
            if ($usd === null && $ngn === null) {
                $usd = round($credits * DoiSettings::creditPriceUsd(), 2);
                $ngn = (int) round($usd * $rate);
            } elseif ($usd !== null && $ngn === null) {
                $ngn = (int) round($usd * $rate);
            } elseif ($ngn !== null && $usd === null) {
                $usd = round($ngn / $rate, 2);
            }

            return DoiCreditTopup::query()->create([
                'journal_id' => $locked->id,
                'credits' => $credits,
                'amount_usd' => $usd,
                'amount_ngn' => $ngn,
                'note' => $note,
                'created_by' => $actor?->id,
            ]);
        });
    }

    public function consume(Journal $journal, int $credits = 1): void
    {
        if ($credits < 1) {
            return;
        }

        DB::transaction(function () use ($journal, $credits) {
            /** @var Journal $locked */
            $locked = Journal::query()->whereKey($journal->id)->lockForUpdate()->firstOrFail();

            if ((int) $locked->doi_credits_balance < $credits) {
                throw new RuntimeException(
                    'Platform DOI credits are exhausted for this journal. Ask the platform admin to top up, or switch to your own Crossref credentials.'
                );
            }

            $locked->doi_credits_balance = (int) $locked->doi_credits_balance - $credits;
            $locked->save();

            $this->notifyThresholds($locked);
        });
    }

    public function refund(Journal $journal, int $credits = 1): void
    {
        if ($credits < 1) {
            return;
        }

        DB::transaction(function () use ($journal, $credits) {
            /** @var Journal $locked */
            $locked = Journal::query()->whereKey($journal->id)->lockForUpdate()->firstOrFail();
            $locked->doi_credits_balance = (int) $locked->doi_credits_balance + $credits;
            if ($locked->doi_credits_balance > 0) {
                $locked->doi_exhausted_notified_at = null;
            }
            $locked->save();
        });
    }

    public function notifyThresholds(Journal $journal): void
    {
        $balance = (int) $journal->doi_credits_balance;
        $lifetime = (int) $journal->doi_credits_lifetime;

        if ($balance <= 0) {
            if ($journal->doi_exhausted_notified_at) {
                return;
            }
            $this->notifyManagers($journal, 'exhausted');
            $journal->forceFill(['doi_exhausted_notified_at' => now()])->save();

            return;
        }

        if (DoiSettings::isLowBalance($balance, $lifetime)) {
            if ($journal->doi_low_balance_notified_at) {
                return;
            }
            $this->notifyManagers($journal, 'low');
            $journal->forceFill(['doi_low_balance_notified_at' => now()])->save();
        }
    }

    private function notifyManagers(Journal $journal, string $level): void
    {
        $admins = $journal->users()
            ->wherePivotIn('role', JournalTeamRoles::manageRoles())
            ->get();

        foreach ($admins as $admin) {
            $admin->notify(new DoiCreditsLowNotification($journal, $level));
        }
    }

    public function platformCreditsAvailable(Journal $journal): bool
    {
        return (int) $journal->doi_credits_balance > 0;
    }

    public function statusBanner(Journal $journal): ?array
    {
        if (($journal->doi_mode ?? 'unset') !== 'platform') {
            return null;
        }

        $balance = (int) $journal->doi_credits_balance;
        $lifetime = (int) $journal->doi_credits_lifetime;

        if ($balance <= 0) {
            return [
                'level' => 'exhausted',
                'title' => 'DOI credits exhausted',
                'message' => 'Platform DOI deposits are paused. Ask the platform administrator to top up credits, or switch this journal to your own Crossref credentials.',
            ];
        }

        if (DoiSettings::isLowBalance($balance, $lifetime)) {
            return [
                'level' => 'low',
                'title' => 'DOI credits running low',
                'message' => 'Only '.$balance.' platform DOI credit(s) remain. Request a top-up soon to avoid interrupted deposits.',
            ];
        }

        return null;
    }
}
