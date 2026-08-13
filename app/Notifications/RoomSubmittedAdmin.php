<?php

namespace App\Notifications;

use App\Models\Room;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RoomSubmittedAdmin extends Notification
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
            ->subject(__('admin.mail.room_submitted_subject', ['room' => $this->room->title]))
            ->greeting(__('host.mail.greeting', ['name' => $notifiable->firstName()]))
            ->line(__('admin.mail.room_submitted_line', [
                'room' => $this->room->title,
                'studio' => $this->room->studio->name,
                'host' => $this->room->studio->user->name,
            ]))
            ->action(__('admin.mail.room_submitted_action'), route('admin.queue.show', $this->room));
    }
}
