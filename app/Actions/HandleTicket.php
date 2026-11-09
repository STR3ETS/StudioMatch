<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\DamageResponded;
use App\Notifications\DisputeResolved;
use App\Support\StripeService;

/**
 * Een ticket afhandelen: een gemeld probleem (terugbetaling 0 tot 100% en een toelichting voor beide
 * partijen) of een probleemmelding van een verhuurder (reactie). Gedeeld door de website
 * (Admin\TicketController) en de app (Api\App\AdminController).
 */
class HandleTicket
{
    public const RESOLVED = 'resolved';

    public const RESPONDED = 'damage_responded';

    public const ALREADY_RESOLVED = 'already_resolved';

    /** Validatieregels, voor website en app gelijk. */
    public const RESOLVE_RULES = [
        'refund_percent' => ['required', 'integer', 'between:0,100'],
        'resolution_note' => ['required', 'string', 'min:10', 'max:2000'],
    ];

    public const RESPOND_RULES = [
        'damage_response' => ['required', 'string', 'min:10', 'max:2000'],
    ];

    /** 0% = melding afwijzen en uitbetaling vrijgeven, 100% = boeking annuleren met volledige terugbetaling. */
    public function resolveDispute(Booking $booking, int $percent, string $note): string
    {
        if ($booking->status !== BookingStatus::Disputed) {
            return self::ALREADY_RESOLVED;
        }

        $booking->update([
            'status' => $percent === 100 ? BookingStatus::Cancelled : BookingStatus::Completed,
            'cancelled_by' => $percent === 100 ? 'admin' : $booking->cancelled_by,
            'resolution_note' => $note,
        ]);

        StripeService::refund($booking, $booking->refundAmountCents($percent));

        $booking->user->notify(new DisputeResolved($booking, $percent));
        $booking->room->studio->user->notify(new DisputeResolved($booking, $percent));

        return self::RESOLVED;
    }

    public function respondToDamage(Booking $booking, string $response): string
    {
        abort_if($booking->damage_reported_at === null, 404);

        if ($booking->damage_resolved_at !== null) {
            return self::ALREADY_RESOLVED;
        }

        $booking->update([
            'damage_response' => $response,
            'damage_resolved_at' => now(),
        ]);

        $booking->room->studio->user->notify(new DamageResponded($booking));

        return self::RESPONDED;
    }
}
