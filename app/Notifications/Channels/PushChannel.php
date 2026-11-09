<?php

namespace App\Notifications\Channels;

use App\Services\FirebaseMessaging;
use Illuminate\Notifications\Notification;
use Throwable;

/**
 * Stuurt een melding als push naar alle telefoons van de ontvanger. De notificatie levert titel, tekst
 * en het pad dat de app bij tikken opent via toPush(); een mislukte push breekt nooit de actie zelf.
 */
class PushChannel
{
    public function __construct(private FirebaseMessaging $firebase) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $this->firebase->isConfigured() || ! method_exists($notifiable, 'deviceTokens') || ! method_exists($notification, 'toPush')) {
            return;
        }

        $push = $notification->toPush($notifiable);

        foreach ($notifiable->deviceTokens as $device) {
            try {
                $valid = $this->firebase->send($device->token, $push['title'], $push['body'] ?? '', ['url' => $push['url'] ?? '/']);

                if (! $valid) {
                    $device->delete();
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }
}
