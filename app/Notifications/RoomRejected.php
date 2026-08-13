<?php

namespace App\Notifications;

use App\Models\Room;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RoomRejected extends Notification
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
            ->subject(__('host.mail.rejected_subject', ['room' => $this->room->title]))
            ->greeting(__('host.mail.greeting', ['name' => $notifiable->firstName()]))
            ->line(__('host.mail.rejected_line', ['room' => $this->room->title, 'studio' => $this->room->studio->name]))
            ->line(__('host.mail.rejected_reason', ['reason' => $this->room->rejection_reason]))
            ->action(__('host.mail.rejected_action'), route('host.rooms.edit', $this->room))
            ->line(__('host.mail.rejected_footer'));
    }
}
