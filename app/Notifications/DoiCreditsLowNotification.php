<?php

namespace App\Notifications;

use App\Models\Journal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DoiCreditsLowNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Journal $journal,
        public readonly string $level, // low|exhausted
    ) {
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
        $title = $this->journal->title;
        $url = route('journal.manage.doi.index', $this->journal);
        $balance = (int) $this->journal->doi_credits_balance;

        if ($this->level === 'exhausted') {
            return (new MailMessage)
                ->subject('DOI credits exhausted: '.$title)
                ->greeting('Hello '.$notifiable->name.'!')
                ->line('Platform DOI credits for **'.$title.'** are exhausted (balance: 0).')
                ->line('New Crossref deposits using the platform pool are paused until credits are topped up, or until you switch to your own Crossref credentials.')
                ->action('Open DOI settings', $url)
                ->line('Contact the platform administrator if you need a top-up.');
        }

        return (new MailMessage)
            ->subject('DOI credits running low: '.$title)
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Platform DOI credits for **'.$title.'** are running low.')
            ->line('Current balance: **'.$balance.'** credit(s).')
            ->action('Open DOI settings', $url)
            ->line('Ask the platform administrator to top up before deposits are blocked.');
    }
}
