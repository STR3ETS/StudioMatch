<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DamageReported extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $studio = $this->booking->room->studio;

        return (new MailMessage)
            ->subject(__('booking.mail.damage_subject', ['room' => $this->booking->room->title]))
            ->greeting(__('booking.mail.greeting', ['name' => $notifiable->firstName()]))
            ->line(__('booking.mail.damage_line', [
                'host' => $studio->user->name,
                'room' => $this->booking->room->title,
                'date' => $this->booking->date->translatedFormat('l j F Y'),
                'time' => $this->booking->timeRange(),
            ]))
            ->line(__('booking.mail.damage_reason', ['reason' => $this->booking->damage_reason]))
            ->line(__('booking.mail.damage_host_contact', ['name' => $studio->user->name, 'email' => $studio->user->email]))
            ->line(__('booking.mail.damage_artist_contact', ['name' => $this->booking->user->name, 'email' => $this->booking->user->email]))
            ->line(__('booking.mail.damage_note'));
    }
}
