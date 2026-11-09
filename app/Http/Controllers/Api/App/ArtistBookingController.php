<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Support\AppPayload;
use App\Support\ArtistBookings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * "Mijn boekingen" in de app: dezelfde verdeling als het artiestendashboard op de website.
 */
class ArtistBookingController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        ['upcoming' => $upcoming, 'past' => $past] = ArtistBookings::split($request->user());

        return response()->json([
            'upcoming' => $upcoming->map(fn (Booking $booking) => AppPayload::artistBooking($booking))->values(),
            'past' => $past->map(fn (Booking $booking) => AppPayload::artistBooking($booking))->values(),
        ]);
    }

    public function show(Request $request, Booking $booking): JsonResponse
    {
        // Een boeking van een ander bestaat voor deze artiest niet.
        abort_unless($booking->user_id === $request->user()->id, 404);

        $booking->load(['room.studio', 'room.photos']);

        return response()->json(AppPayload::artistBookingDetail($booking));
    }
}
