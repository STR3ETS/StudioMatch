<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRescheduled extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isHost = $notifiable->id === $this->booking->room->studio->user_id;

        $message = (new MailMessage)
            ->subject(__('booking.mail.rescheduled_subject', ['room' => $this->booking->room->title]))
            ->greeting(__('booking.mail.greeting', ['name' => $notifiable->firstName()]))
            ->line(__('booking.mail.rescheduled_line', [
                'room' => $this->booking->room->title,
                'date' => $this->booking->date->translatedFormat('l j F Y'),
                'time' => $this->booking->timeRange(),
            ]));

        if ($isHost) {
            return $message
                ->line(__('booking.mail.rescheduled_host_note'))
                ->action(__('booking.mail.requested_action'), route('host.bookings.index'));
        }

        return $message
            ->line(__('booking.mail.rescheduled_artist_note'))
            ->action(__('booking.mail.received_action'), route('dashboard.artist'));
    }
}
