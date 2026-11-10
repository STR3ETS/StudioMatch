<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingRescheduled;
use App\Support\Hours;
use App\Support\StripeService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * De artiest annuleert of verzet zijn eigen boeking. Gedeeld door de website (BookingController) en de app
 * (Api\App\ArtistBookingController). Of de boeking van deze artiest is, controleert de aanroeper.
 */
class ChangeArtistBooking
{
    public static function canCancel(Booking $booking): bool
    {
        return in_array($booking->status, [BookingStatus::PendingConfirmation, BookingStatus::Confirmed], true)
            && $booking->startsAt()->isFuture();
    }

    /**
     * Annuleren volgens de annuleringsvoorwaarden; het percentage van de huur gaat terug, de servicekosten
     * altijd (refundAmountCents).
     */
    public function cancel(Booking $booking): void
    {
        abort_unless(self::canCancel($booking), 404);

        $refundPercent = $booking->refundPercentForArtistCancellation();

        $booking->update(['status' => BookingStatus::Cancelled, 'cancelled_by' => 'artist']);

        StripeService::refund($booking, $booking->refundAmountCents($refundPercent));

        $booking->user->notify(new BookingCancelled($booking, $refundPercent));
        $booking->room->studio->user->notify(new BookingCancelled($booking, $refundPercent));
    }

    /**
     * Eén keer verzetten naar een ander moment met dezelfde duur; de studio moet de nieuwe tijd opnieuw
     * accepteren.
     *
     * @param  array<string, mixed>  $input  date en start
     */
    public function reschedule(Booking $booking, array $input): void
    {
        abort_unless($booking->canReschedule(), 404);

        $validated = Validator::make($input, [
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start' => ['required', 'integer', 'between:0,23'],
        ])->validate();

        $date = Carbon::parse($validated['date']);
        $startHour = (int) $validated['start'];
        $endHour = $startHour + $booking->hours();

        $available = $endHour <= Hours::max()
            && ! $date->copy()->startOfDay()->addHours($startHour)->isPast()
            && $booking->room->isAvailableOn($date->copy(), $startHour, $endHour)
            && ! $booking->room->overlappingBookings($date, $startHour, $endHour)
                ->whereKeyNot($booking->id)
                ->exists();

        if (! $available) {
            throw ValidationException::withMessages(['slot' => __('booking.errors.unavailable')]);
        }

        $booking->update([
            'date' => $date,
            'start_hour' => $startHour,
            'end_hour' => $endHour,
            'status' => BookingStatus::PendingConfirmation,
            'confirmed_at' => null,
            'rescheduled_at' => now(),
            'requested_at' => now(),
        ]);

        $booking->user->notify(new BookingRescheduled($booking));
        $booking->room->studio->user->notify(new BookingRescheduled($booking));
    }
}
