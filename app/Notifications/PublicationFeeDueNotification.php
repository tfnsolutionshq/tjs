<?php

namespace App\Notifications;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class PublicationFeeDueNotification extends Notification implements ShouldQueue
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
        $fee = $submission->publicationJournalFee;
        $journal = $submission->journal;
        $platform = config('tjs.full_name', config('tjs.name', 'TJS'));
        $amountLabel = $fee
            ? number_format((int) $fee->amount).' '.strtoupper((string) $fee->currency)
            : 'the required amount';

        $paymentUrl = URL::temporarySignedRoute(
            'author.submissions.checkout-publication-fee',
            now()->addDays(30),
            ['submission' => $submission->id],
        );

        $mail = (new MailMessage)
            ->subject('Accepted — publication fee due: '.$submission->title)
            ->greeting('Congratulations, '.$notifiable->name.'!')
            ->line('Your manuscript has been **accepted** after review.')
            ->line('**Manuscript:** '.$submission->title);

        if ($journal) {
            $mail->line('**Journal:** '.$journal->title);
        }

        if ($submission->issue) {
            $mail->line('**Target issue:** '.$submission->issue->label());
        }

        $mail->line('To proceed toward publication, please pay the publication fee (APC): **'.$amountLabel.'**'.($fee ? ' ('.$fee->name.')' : '').'.')
            ->action('Pay publication fee', $paymentUrl)
            ->line('You can also pay any time from your submission page in '.$platform.'.')
            ->line('Thank you for submitting your work.');

        return $mail;
    }
}
