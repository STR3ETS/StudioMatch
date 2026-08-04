<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Mailmatrix (scope §2.10): "Boeking bevestigd (met adres + contactgegevens)" → beide.
class BookingConfirmed extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $studio = $this->booking->room->studio;
        $isHost = $notifiable->id === $studio->user_id;

        $message = (new MailMessage)
            ->subject(__('booking.mail.confirmed_subject', ['room' => $this->booking->room->title]))
            ->greeting(__('booking.mail.greeting', ['name' => $notifiable->firstName()]))
            ->line(__('booking.mail.confirmed_line', [
                'room' => $this->booking->room->title,
                'date' => $this->booking->date->translatedFormat('l j F Y'),
                'time' => $this->booking->timeRange(),
            ]))
            ->line(__('booking.mail.confirmed_address', ['address' => $studio->fullAddress()]));

        if ($isHost) {
            return $message
                ->line(__('booking.mail.confirmed_contact_artist', [
                    'name' => $this->booking->user->name,
                    'email' => $this->booking->user->email,
                ]))
                ->action(__('booking.mail.confirmed_action_host'), route('host.bookings.index'));
        }

        return $message
            ->line(__('booking.mail.confirmed_contact_studio', [
                'name' => $studio->name,
                'phone' => $studio->phone ?? $studio->user->hostProfile?->phone ?? '-',
            ]))
            ->action(__('booking.mail.confirmed_action_artist'), route('dashboard.artist'));
    }
}
