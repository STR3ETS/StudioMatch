<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactMessage extends Notification
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
            ->subject(__('contact.mail.subject', ['subject' => __('contact.form.subjects.' . $this->data['subject'])]))
            ->replyTo($this->data['email'], $this->data['name'])
            ->greeting(__('host.mail.greeting', ['name' => $notifiable->firstName()]))
            ->line(__('contact.mail.from', ['name' => $this->data['name'], 'email' => $this->data['email']]))
            ->line(__('contact.mail.message', ['message' => $this->data['message']]));
    }
}
