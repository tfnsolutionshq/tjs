<?php

namespace App\Notifications;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReadyForProductionNotification extends Notification implements ShouldQueue
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
        $platform = config('tjs.full_name', config('tjs.name', 'TJS'));

        return (new MailMessage)
            ->subject('Ready for production: '.$submission->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A manuscript has completed review and is **ready for production**.')
            ->line('**Manuscript:** '.$submission->title)
            ->line('**Journal:** '.($submission->journal?->title ?: '—'))
            ->action('Open production queue', route('production.queue.show', $submission))
            ->line('Download the accepted author manuscript, apply journal formatting and branding, then upload the final production document in '.$platform.'.');
    }
}
