<?php

namespace App\Support;

use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/*
 * Al onze notificaties staan op ShouldQueue. Dat is prettig zolang de wachtrij draait,
 * maar staat die stil dan verdwijnt de mail geruisloos: geen foutmelding, niets in het
 * log, alleen een rij in de jobs-tabel die blijft liggen. Juist bij een verificatiemail
 * of een wachtwoordherstel is dat fataal, want daar wacht iemand op.
 *
 * Daarom versturen we die direct en houden we de wachtrij als vangnet achter de hand.
 * Valt de mailprovider weg, dan ziet de bezoeker nog steeds geen foutpagina terwijl zijn
 * account al is aangemaakt.
 */
class MailDelivery
{
    public static function send(mixed $target, BaseNotification $notification): void
    {
        try {
            Notification::sendNow($target, $notification);
        } catch (Throwable $exception) {
            Log::error('Mail kon niet direct worden verstuurd, nu via de wachtrij.', [
                'notification' => $notification::class,
                'error' => $exception->getMessage(),
            ]);

            Notification::send($target, $notification);
        }
    }
}
