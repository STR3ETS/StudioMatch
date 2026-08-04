<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Mailmatrix (scope §2.10): "Probleem gemeld" → admin + verhuurder.
class ProblemReported extends Notification
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
        $isAdmin = $notifiable->isAdmin();

        $message = (new MailMessage)
            ->subject(__('booking.mail.problem_subject', ['room' => $this->booking->room->title]))
            ->greeting(__('booking.mail.greeting', ['name' => $notifiable->firstName()]))
            ->line(__('booking.mail.problem_line', [
                'artist' => $this->booking->user->name,
                'room' => $this->booking->room->title,
                'date' => $this->booking->date->translatedFormat('l j F Y'),
                'time' => $this->booking->timeRange(),
            ]))
            ->line(__('booking.mail.problem_reason', ['reason' => $this->booking->dispute_reason]))
            ->line(__('booking.mail.problem_hold'));

        if ($isAdmin) {
            return $message->action(__('booking.mail.problem_action_admin'), route('admin.tickets.index'));
        }

        return $message->line(__('booking.mail.problem_host_note'));
    }
}
