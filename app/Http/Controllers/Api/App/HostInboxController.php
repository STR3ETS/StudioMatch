<?php

namespace App\Http\Controllers\Api\App;

use App\Actions\RespondToBookingRequest;
use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Support\AppPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Boekingsinbox van de verhuurder in de app: openstaande aanvragen (accepteren of weigeren, met
 * dezelfde regels als de website via RespondToBookingRequest) en de komende sessies.
 */
class HostInboxController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::query()
            ->whereIn('room_id', $request->user()->rooms()->select('rooms.id'))
            ->whereIn('status', [BookingStatus::PendingConfirmation, BookingStatus::Confirmed])
            ->with(['room.studio', 'user'])
            ->get();

        return response()->json([
            'requests' => $bookings->where('status', BookingStatus::PendingConfirmation)
                ->sortBy(fn (Booking $booking) => $booking->startsAt())
                ->map(fn (Booking $booking) => AppPayload::hostBooking($booking))->values(),
            'upcoming' => $bookings->where('status', BookingStatus::Confirmed)
                ->filter(fn (Booking $booking) => $booking->endsAt()->isFuture())
                ->sortBy(fn (Booking $booking) => $booking->startsAt())
                ->map(fn (Booking $booking) => AppPayload::hostBooking($booking))->values(),
        ]);
    }

    public function accept(Request $request, Booking $booking, RespondToBookingRequest $respond): JsonResponse
    {
        return $this->respond($request, $respond->accept($booking, $request->user()));
    }

    public function decline(Request $request, Booking $booking, RespondToBookingRequest $respond): JsonResponse
    {
        return $this->respond($request, $respond->decline($booking, $request->user()));
    }

    /** De uitkomst met de melding van de website, plus de bijgewerkte inbox. */
    private function respond(Request $request, string $outcome): JsonResponse
    {
        return response()->json([
            'outcome' => $outcome,
            'message' => __('host.bookings.' . $outcome),
            'inbox' => $this->index($request)->getData(true),
        ], in_array($outcome, [RespondToBookingRequest::ACCEPTED, RespondToBookingRequest::DECLINED], true) ? 200 : 409);
    }
}
