<?php

namespace App\Notifications;

use App\Services\Auth\EmailVerificationOtpService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationOtpNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $code) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your email verification code')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Use this one-time code to verify your email address on TFN Journal System:')
            ->line('**'.$this->code.'**')
            ->line('This code expires in '.EmailVerificationOtpService::EXPIRY_MINUTES.' minutes.')
            ->line('If you did not request this, you can ignore this email.');
    }
}
