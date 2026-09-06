<?php

namespace App\Notifications;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductionCompleteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Submission $submission)
    {
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $submission = $this->submission;
        $manageJournal = $submission->journal;

        $mail = (new MailMessage)
            ->subject('Production complete: '.$submission->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Production is complete for an accepted manuscript.')
            ->line('**Manuscript:** '.$submission->title)
            ->line('**Journal:** '.($manageJournal?->title ?: '—'));

        if ($manageJournal) {
            $mail->action('Review submission', route('journal.manage.submissions.show', [$manageJournal, $submission]));
        }

        return $mail->line('You can now publish this manuscript to its issue using the final production document.');
    }
}
