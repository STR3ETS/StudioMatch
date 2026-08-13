<?php

namespace App\Http\Controllers\Host;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RevenueController extends Controller
{

    public function __invoke(Request $request): View
    {
        $bookings = Booking::whereIn('room_id', $request->user()->rooms()->select('rooms.id'))
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed, BookingStatus::Disputed])
            ->with('room')
            ->get();

        [$realised, $expected] = $bookings->partition(fn (Booking $booking) => $booking->endsAt()->isPast());

        $onHold = $realised->where('status', BookingStatus::Disputed);

        $months = $realised
            ->sortByDesc(fn (Booking $booking) => $booking->date)
            ->groupBy(fn (Booking $booking) => $booking->date->format('Y-m'))
            ->map(fn ($group) => [
                'label' => $group->first()->date->translatedFormat('F Y'),
                'count' => $group->count(),
                'total' => $group->sum('rent_cents'),
            ]);

        return view('host.revenue', [
            'months' => $months,
            'realisedTotal' => $realised->sum('rent_cents'),
            'expectedTotal' => $expected->sum('rent_cents'),
            'thisMonthTotal' => $realised->filter(fn (Booking $booking) => $booking->date->isSameMonth(today()))->sum('rent_cents'),
            'onHoldTotal' => $onHold->sum('rent_cents'),
        ]);
    }
}
