<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactConfirmation extends Notification
{
    use Queueable;

    public function __construct(public array $data) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('contact.mail.confirm_subject'))
            ->greeting(__('host.mail.greeting', ['name' => $this->data['name']]))
            ->line(__('contact.mail.confirm_line'))
            ->line(__('contact.mail.message', ['message' => $this->data['message']]));
    }
}
