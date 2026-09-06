<?php

namespace App\Notifications;

use App\Models\Journal;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JournalFeaturedRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Journal $journal,
        public readonly User $requester,
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

        return (new MailMessage)
            ->subject('Featured journal request: '.$title)
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('**'.$this->requester->name.'** ('.$this->requester->email.') asked to feature **'.$title.'** on the platform homepage.')
            ->line('Only platform administrators can mark a journal as featured.')
            ->action('Review journal', route('admin.journals.edit', $this->journal).'#journal-visibility')
            ->line('Open Visibility on the journal edit page and toggle Featured if you approve.');
    }
}
