<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingDeclined;
use App\Support\StripeService;

/**
 * Een verhuurder accepteert of weigert een betaalde aanvraag. Gedeeld door de boekingsinbox op de
 * website (Host\BookingController) en de app (Api\App\HostInboxController), zodat beide dezelfde
 * regels volgen. Het resultaat is de sleutel van de melding in host.bookings.*.
 */
class RespondToBookingRequest
{
    public const ACCEPTED = 'accepted';

    public const DECLINED = 'declined';

    public const ALREADY_HANDLED = 'already_handled';

    public const REQUEST_EXPIRED = 'request_expired';

    public function accept(Booking $booking, User $host): string
    {
        if (($blocked = $this->guard($booking, $host)) !== null) {
            return $blocked;
        }

        $booking->update(['status' => BookingStatus::Confirmed, 'confirmed_at' => now()]);

        $booking->user->notify(new BookingConfirmed($booking));
        $booking->room->studio->user->notify(new BookingConfirmed($booking));

        return self::ACCEPTED;
    }

    public function decline(Booking $booking, User $host): string
    {
        if (($blocked = $this->guard($booking, $host)) !== null) {
            return $blocked;
        }

        $booking->update(['status' => BookingStatus::Declined]);

        StripeService::refund($booking, $booking->refundAmountCents(100));

        $booking->user->notify(new BookingDeclined($booking));

        return self::DECLINED;
    }

    public static function ownedBy(Booking $booking, User $host): bool
    {
        return $booking->room->studio->user_id === $host->id;
    }

    /**
     * Alleen de eigen verhuurder, alleen een openstaande aanvraag, en niet meer vlak voor de start:
     * dan is de aanvraag verlopen en annuleren we hem met volledige terugbetaling.
     */
    private function guard(Booking $booking, User $host): ?string
    {
        abort_unless(self::ownedBy($booking, $host), 403);

        if ($booking->status !== BookingStatus::PendingConfirmation) {
            return self::ALREADY_HANDLED;
        }

        if ($booking->startsAt()->subHours(2)->isPast()) {
            $this->cancelExpiredRequest($booking);

            return self::REQUEST_EXPIRED;
        }

        return null;
    }

    private function cancelExpiredRequest(Booking $booking): void
    {
        $booking->update(['status' => BookingStatus::Cancelled, 'cancelled_by' => 'auto']);

        StripeService::refund($booking, $booking->refundAmountCents(100));

        $booking->user->notify(new BookingCancelled($booking, 100));
        $booking->room->studio->user->notify(new BookingCancelled($booking, 100));
    }
}
