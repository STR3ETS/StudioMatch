<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DamageResponded extends Notification
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
            ->subject(__('booking.mail.damage_response_subject', ['room' => $this->booking->room->title]))
            ->greeting(__('booking.mail.greeting', ['name' => $notifiable->firstName()]))
            ->line(__('booking.mail.damage_response_line', [
                'room' => $this->booking->room->title,
                'date' => $this->booking->date->translatedFormat('l j F Y'),
            ]))
            ->line(__('booking.mail.damage_response_reason', ['reason' => $this->booking->damage_reason]))
            ->line(__('booking.mail.damage_response_body', ['response' => $this->booking->damage_response]))
            ->action(__('booking.mail.damage_response_action'), route('host.bookings.index'));
    }
}
