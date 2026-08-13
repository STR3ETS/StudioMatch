<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SessionReminder extends Notification
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
            ->subject(__('booking.mail.reminder_subject', ['room' => $this->booking->room->title]))
            ->greeting(__('booking.mail.greeting', ['name' => $notifiable->firstName()]))
            ->line(__('booking.mail.reminder_line', [
                'room' => $this->booking->room->title,
                'date' => $this->booking->date->translatedFormat('l j F Y'),
                'time' => $this->booking->timeRange(),
            ]))
            ->line(__('booking.mail.confirmed_address', ['address' => $studio->fullAddress()]));
    }
}
