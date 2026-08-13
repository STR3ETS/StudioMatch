<?php

namespace App\Http\Controllers\Artist;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OverviewController extends Controller
{

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

        $statusPriority = fn (Booking $booking) => match ($booking->effectiveStatus()) {
            BookingStatus::Completed, BookingStatus::PendingPayment => 0,
            BookingStatus::PendingConfirmation => 1,
            default => 2,
        };

        return view('dashboard.artist', [
            'upcoming' => $upcoming->values(),
            'past' => $past
                ->sortByDesc(fn (Booking $booking) => $booking->startsAt())
                ->sortBy($statusPriority)
                ->values(),
        ]);
    }
}
