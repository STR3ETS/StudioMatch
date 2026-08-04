<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\View\View;

class RevenueController extends Controller
{
    /**
     * Omzet en platforminkomsten per studio (scope §2.9 admin).
     * Huur gaat naar de verhuurder; servicekosten + btw zijn voor het platform.
     */
    public function __invoke(): View
    {
        $bookings = Booking::whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed, BookingStatus::Disputed])
            ->with('room.studio.user')
            ->get();

        $studios = $bookings
            ->groupBy(fn (Booking $booking) => $booking->room->studio->id)
            ->map(function ($group) {
                $studio = $group->first()->room->studio;

                return [
                    'studio' => $studio,
                    'count' => $group->count(),
                    'rent' => $group->sum('rent_cents'),
                    'fees' => $group->sum('service_fee_cents') + $group->sum('vat_cents'),
                ];
            })
            ->sortByDesc('rent')
            ->values();

        return view('admin.revenue', [
            'studios' => $studios,
            'totalRent' => $studios->sum('rent'),
            'totalFees' => $studios->sum('fees'),
            'totalCount' => $studios->sum('count'),
        ]);
    }
}
