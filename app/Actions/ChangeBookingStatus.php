<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\BookingCancelled;
use App\Support\StripeService;

/**
 * De status van een boeking handmatig wijzigen: het vangnet van de beheerder voor randgevallen. Bij
 * annuleren volledig terugbetalen en beide partijen mailen. Gedeeld door de website
 * (Admin\BookingController) en de app (Api\App\AdminController).
 */
class ChangeBookingStatus
{
    public function __invoke(Booking $booking, BookingStatus $status): void
    {
        $booking->update([
            'status' => $status,
            'cancelled_by' => $status === BookingStatus::Cancelled ? 'admin' : $booking->cancelled_by,
        ]);

        if ($status === BookingStatus::Cancelled) {
            StripeService::refund($booking, $booking->refundAmountCents(100));
            $booking->user->notify(new BookingCancelled($booking, 100));
            $booking->room->studio->user->notify(new BookingCancelled($booking, 100));
        }
    }
}
