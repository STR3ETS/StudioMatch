<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HostWelcome extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('host.mail.welcome_subject'))
            ->greeting(__('host.mail.greeting', ['name' => $notifiable->firstName()]))
            ->line(__('host.mail.welcome_line'))
            ->action(__('host.mail.welcome_action'), route('dashboard.host'))
            ->line(__('host.mail.welcome_footer'));
    }
}
