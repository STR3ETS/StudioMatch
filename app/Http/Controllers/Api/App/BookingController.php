<?php

namespace App\Http\Controllers\Api\App;

use App\Actions\BookRoom;
use App\Actions\ChangeArtistBooking;
use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Support\AppPayload;
use App\Support\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Boeken in de app: de studio bekijken, een moment kiezen, afrekenen en betalen, en daarna annuleren of
 * verzetten. Dezelfde regels als de website (BookRoom, ChangeArtistBooking). Met Stripe betaalt de artiest
 * op de serverpagina (die stuurt door naar Stripe en daarna terug naar de app); zonder Stripe is het een
 * demo en rondt de app de betaling zelf af.
 */
class BookingController extends Controller
{
    public function studio(Room $room): JsonResponse
    {
        abort_unless($room->isPubliclyVisible(), 404);

        return response()->json(AppPayload::studioDetail($room));
    }

    /** Het gekozen moment controleren en de prijs geven, nog zonder iets vast te houden. */
    public function checkout(Request $request, Room $room, BookRoom $book): JsonResponse
    {
        abort_unless($room->isPubliclyVisible(), 404);

        $period = $book->period($room, $request->all());

        return response()->json(AppPayload::checkout($room, $request->user(), $period, $book->prices($room, $period)));
    }

    public function store(Request $request, Room $room, BookRoom $book): JsonResponse
    {
        abort_unless($room->isPubliclyVisible(), 404);

        $booking = $book->reserve($request->user(), $room, $request->all());

        return response()->json($this->detail($booking), 201);
    }

    public function pay(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeBooking($request, $booking);

        if ($booking->status !== BookingStatus::PendingPayment) {
            return response()->json(['message' => __('app.ui.payment.already'), 'booking' => $this->detail($booking)], 409);
        }

        if ($booking->expires_at->isPast()) {
            $booking->update(['status' => BookingStatus::Expired]);

            return response()->json(['message' => __('booking.errors.expired'), 'booking' => $this->detail($booking)], 409);
        }

        // Echt betalen gaat via Stripe op de serverpagina.
        abort_if(StripeService::enabled(), 409);

        $booking->markAsPaid();

        return response()->json(['message' => __('booking.paid'), 'booking' => $this->detail($booking->refresh())]);
    }

    public function cancel(Request $request, Booking $booking, ChangeArtistBooking $change): JsonResponse
    {
        $this->authorizeBooking($request, $booking);

        $change->cancel($booking);

        return response()->json(['message' => __('booking.cancelled'), 'booking' => $this->detail($booking->refresh())]);
    }

    /** Verzetten: de vrije uren van de ruimte, voor dezelfde duur. */
    public function rescheduleOptions(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeBooking($request, $booking);

        abort_unless($booking->canReschedule(), 404);

        $booking->load(['room.studio', 'room.photos']);

        return response()->json([
            'booking' => AppPayload::artistBooking($booking),
            'hours' => $booking->hours(),
            'subtitle' => __('booking.reschedule.subtitle', ['room' => $booking->room->studio->name.' - '.$booking->room->title, 'hours' => $booking->hours()]),
            'note' => __('booking.reschedule.note'),
            'free_hours' => (object) $booking->room->freeHoursByDate(),
        ]);
    }

    public function reschedule(Request $request, Booking $booking, ChangeArtistBooking $change): JsonResponse
    {
        $this->authorizeBooking($request, $booking);

        $change->reschedule($booking, $request->all());

        return response()->json(['message' => __('booking.rescheduled'), 'booking' => $this->detail($booking->refresh())]);
    }

    /** Een boeking van een ander bestaat voor deze artiest niet. */
    private function authorizeBooking(Request $request, Booking $booking): void
    {
        abort_unless($booking->user_id === $request->user()->id, 404);
    }

    /** @return array<string, mixed> */
    private function detail(Booking $booking): array
    {
        return AppPayload::artistBookingDetail($booking->load(['room.studio', 'room.photos']));
    }
}
