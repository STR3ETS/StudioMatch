<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OverviewController extends Controller
{
    /**
     * Artiestendashboard (scope §2.9): komende en eerdere boekingen.
     */
    public function __invoke(Request $request): View
    {
        $bookings = $request->user()->bookings()
            ->with(['room.studio', 'room.photos'])
            ->orderBy('date')
            ->orderBy('start_hour')
            ->get();

        [$upcoming, $past] = $bookings->partition(
            fn (Booking $booking) => $booking->endsAt()->isFuture() && $booking->isActive(),
        );

        return view('dashboard.artist', [
            'upcoming' => $upcoming->values(),
            'past' => $past->sortByDesc(fn (Booking $booking) => $booking->startsAt())->values(),
        ]);
    }
}
