<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingReceived extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('booking.mail.received_subject', ['room' => $this->booking->room->title]))
            ->greeting(__('booking.mail.greeting', ['name' => $notifiable->firstName()]))
            ->line(__('booking.mail.received_line', [
                'room' => $this->booking->room->title,
                'date' => $this->booking->date->translatedFormat('l j F Y'),
                'time' => $this->booking->timeRange(),
            ]))
            ->line(__('booking.mail.received_refund_note'))
            ->action(__('booking.mail.received_action'), route('dashboard.artist'));
    }
}
