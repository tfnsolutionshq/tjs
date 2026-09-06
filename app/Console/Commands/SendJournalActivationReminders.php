<?php

namespace App\Console\Commands;

use App\Models\Journal;
use App\Notifications\JournalActivationReminderNotification;
use App\Support\JournalActivation;
use App\Support\JournalTeamRoles;
use Illuminate\Console\Command;

class SendJournalActivationReminders extends Command
{
    protected $signature = 'journals:send-activation-reminders';

    protected $description = 'Email journal admins about upcoming or expired activation fees';

    public function handle(): int
    {
        if (! JournalActivation::enabled()) {
            $this->info('Journal activation fees are disabled — skipping reminders.');

            return self::SUCCESS;
        }

        $sent = 0;

        Journal::query()
            ->whereNotNull('activation_expires_at')
            ->whereIn('activation_status', [
                JournalActivation::STATUS_ACTIVE,
                JournalActivation::STATUS_EXPIRED,
            ])
            ->orderBy('id')
            ->chunkById(100, function ($journals) use (&$sent) {
                foreach ($journals as $journal) {
                    $sent += $this->processJournal($journal) ? 1 : 0;
                }
            });

        $this->info("Sent {$sent} activation reminder(s).");

        return self::SUCCESS;
    }

    private function processJournal(Journal $journal): bool
    {
        $days = $journal->daysUntilActivationExpiry();
        if ($days === null) {
            return false;
        }

        if ($days < 0) {
            if ($journal->activation_status !== JournalActivation::STATUS_EXPIRED) {
                $journal->markActivationExpired();
                $journal->refresh();
            }
            $days = 0;
        }

        if (! in_array($days, JournalActivation::reminderDays(), true)) {
            return false;
        }

        $sentMap = $journal->activation_reminders_sent ?? [];
        $key = (string) $days;
        $period = $journal->activation_expires_at?->toDateString() ?? 'none';
        if (($sentMap[$key] ?? null) === $period) {
            return false;
        }

        $admins = $journal->users()
            ->wherePivot('role', JournalTeamRoles::ADMIN)
            ->get();

        if ($admins->isEmpty()) {
            return false;
        }

        foreach ($admins as $admin) {
            $admin->notify(new JournalActivationReminderNotification($journal, $days));
        }

        $sentMap[$key] = $period;
        $journal->forceFill(['activation_reminders_sent' => $sentMap])->save();

        return true;
    }
}
