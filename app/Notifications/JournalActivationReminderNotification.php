<?php

namespace App\Notifications;

use App\Models\Journal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JournalActivationReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Journal $journal,
        public readonly int $daysRemaining,
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
        $payUrl = route('journal.manage.activation.show', $this->journal);
        $mail = (new MailMessage)->greeting('Hello '.$notifiable->name.'!');

        if ($this->daysRemaining === 0) {
            return $mail
                ->subject('Journal activation expired: '.$title)
                ->line('The activation for **'.$title.'** has expired.')
                ->line('The journal is no longer listed publicly, and major management tools are locked until you renew.')
                ->action('Renew activation', $payUrl)
                ->line('Your catalog content is preserved — renew anytime to unlock management again.');
        }

        $when = $this->daysRemaining === 1
            ? 'tomorrow'
            : ('in '.$this->daysRemaining.' days');

        return $mail
            ->subject('Journal activation expires '.$when.': '.$title)
            ->line('The activation for **'.$title.'** expires '.$when.'.')
            ->line('When it expires, the journal will be unlisted and management tools will lock until renewal.')
            ->action('Renew activation', $payUrl)
            ->line('Thank you for publishing with '.config('tjs.full_name').'.');
    }
}
