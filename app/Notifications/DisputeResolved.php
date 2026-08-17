<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DisputeResolved extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking, public int $percent) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = '€ ' . number_format($this->booking->refundAmountCents($this->percent) / 100, 2, ',', '.');

        $outcome = match (true) {
            $this->percent === 0 => __('booking.mail.resolved_released'),
            $this->percent === 100 => __('booking.mail.resolved_full', ['amount' => $amount]),
            default => __('booking.mail.resolved_partial', ['percent' => $this->percent, 'amount' => $amount]),
        };

        return (new MailMessage)
            ->subject(__('booking.mail.resolved_subject', ['room' => $this->booking->room->title]))
            ->greeting(__('booking.mail.greeting', ['name' => $notifiable->firstName()]))
            ->line(__('booking.mail.resolved_line', [
                'room' => $this->booking->room->title,
                'date' => $this->booking->date->translatedFormat('l j F Y'),
            ]))
            ->line($outcome)
            ->line(__('booking.mail.resolved_note', ['note' => $this->booking->resolution_note]))
            ->action(
                __('booking.mail.resolved_action'),
                $notifiable->isHost() ? route('host.bookings.index') : route('dashboard.artist'),
            );
    }
}
