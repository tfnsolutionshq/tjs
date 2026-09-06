<?php

namespace App\Notifications;

use App\Models\PaymentTransaction;
use App\Services\Payments\PaymentReceiptService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class PaymentReceiptNotification extends Notification
{
    use Queueable;

    public function __construct(public PaymentTransaction $transaction)
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
        $receipts = app(PaymentReceiptService::class);
        $info = $receipts->describe($this->transaction);
        $platform = config('tjs.full_name', config('tjs.name', 'TJS'));
        $amountLabel = number_format($info['amount']).' '.$info['currency'];

        $mail = (new MailMessage)
            ->subject('Payment receipt: '.$info['type_label'].' ('.$info['reference'].')')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Your payment was successful. A PDF receipt is attached to this email.')
            ->line('**'.$info['type_label'].':** '.$info['detail'])
            ->line('**Amount:** '.$amountLabel)
            ->line('**Reference:** '.$info['reference'])
            ->action('Download receipt', route('payments.receipt', $this->transaction))
            ->line('Thank you for using '.$platform.'.');

        try {
            $mail->attachData(
                $receipts->pdfBinary($this->transaction),
                $receipts->filename($this->transaction),
                ['mime' => 'application/pdf']
            );
        } catch (\Throwable $e) {
            Log::warning('Payment receipt PDF attachment failed', [
                'reference' => $this->transaction->reference,
                'error' => $e->getMessage(),
            ]);
        }

        return $mail;
    }
}
