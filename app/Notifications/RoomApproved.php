<?php

namespace App\Notifications;

use App\Models\Room;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RoomApproved extends Notification
{
    use Queueable;

    public function __construct(public Room $room) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('host.mail.approved_subject', ['room' => $this->room->title]))
            ->greeting(__('host.mail.greeting', ['name' => $notifiable->firstName()]))
            ->line(__('host.mail.approved_line', ['room' => $this->room->title, 'studio' => $this->room->studio->name]))
            ->action(__('host.mail.approved_action'), route('host.studios.show', $this->room->studio))
            ->line(__('host.mail.approved_footer'));
    }
}
