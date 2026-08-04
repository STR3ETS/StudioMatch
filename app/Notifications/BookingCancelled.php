<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Mailmatrix (scope §2.10): annulerings-/auto-annuleringsbevestiging → beide.
class BookingCancelled extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking, public int $refundPercent) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $auto = $this->booking->cancelled_by === 'auto';

        $message = (new MailMessage)
            ->subject(__($auto ? 'booking.mail.auto_cancelled_subject' : 'booking.mail.cancelled_subject', ['room' => $this->booking->room->title]))
            ->greeting(__('booking.mail.greeting', ['name' => $notifiable->firstName()]))
            ->line(__($auto ? 'booking.mail.auto_cancelled_line' : 'booking.mail.cancelled_line', [
                'room' => $this->booking->room->title,
                'date' => $this->booking->date->translatedFormat('l j F Y'),
                'time' => $this->booking->timeRange(),
            ]));

        // Restitutie is alleen relevant voor de artiest (scope §2.8 + BESLISSING 5:
        // servicekosten volledig terug, behalve bij annulering binnen 24 uur).
        if ($notifiable->id === $this->booking->user_id) {
            $refund = (int) round($this->booking->rent_cents * $this->refundPercent / 100);
            if ($this->refundPercent > 0) {
                $refund += $this->booking->service_fee_cents + $this->booking->vat_cents;
            }

            $message->line(__('booking.mail.cancelled_refund', [
                'percent' => $this->refundPercent,
                'amount' => '€ ' . number_format($refund / 100, 2, ',', '.'),
            ]));
        }

        return $message;
    }
}
