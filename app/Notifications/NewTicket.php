<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Notifications\Channels\PushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Pushmelding voor de beheerder: er is een nieuw ticket, een probleem van een artiest ('dispute') of
 * een probleemmelding van een verhuurder ('damage'). Tikken opent Tickets in de app. Los van de mails
 * (ProblemReported, DamageReported), die blijven zoals ze zijn. Versturen via MailDelivery::send: meteen,
 * met de wachtrij als vangnet (zoals alle notificaties); een mislukte push breekt nooit de melding van de
 * gebruiker (PushChannel).
 */
class NewTicket extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking, public string $kind) {}

    /** @return list<class-string> */
    public function via(object $notifiable): array
    {
        return [PushChannel::class];
    }

    /** @return array{title: string, body: string, url: string} */
    public function toPush(object $notifiable): array
    {
        $dispute = $this->kind === 'dispute';
        $room = $this->booking->room;

        return [
            'title' => __($dispute ? 'app.push.dispute_title' : 'app.push.damage_title'),
            'body' => __('app.push.ticket_body', [
                'room' => $room->studio->name.' - '.$room->title,
                'reason' => Str::limit((string) ($dispute ? $this->booking->dispute_reason : $this->booking->damage_reason), 120),
            ]),
            'url' => route('admin.tickets.index', [], false),
        ];
    }
}
