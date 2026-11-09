<?php

namespace App\Http\Controllers\Host;

use App\Actions\RespondToBookingRequest;
use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $bookings = Booking::query()
            ->whereIn('room_id', $request->user()->rooms()->select('rooms.id'))
            ->with(['room.studio', 'user'])
            ->get();

        return view('host.bookings.index', [
            'requests' => $bookings->where('status', BookingStatus::PendingConfirmation)
                ->sortBy(fn (Booking $booking) => $booking->startsAt())->values(),
            'upcoming' => $bookings->where('status', BookingStatus::Confirmed)
                ->filter(fn (Booking $booking) => $booking->endsAt()->isFuture())
                ->sortBy(fn (Booking $booking) => $booking->startsAt())->values(),
            'disputes' => $bookings->whereNotNull('disputed_at')
                ->sortByDesc('disputed_at')->values(),
            'recent' => $bookings->filter(fn (Booking $booking) => $booking->canReportDamage() || $booking->damage_reported_at !== null)
                ->sortByDesc(fn (Booking $booking) => $booking->startsAt())->take(10)->values(),
        ]);
    }

    public function accept(Request $request, Booking $booking, RespondToBookingRequest $respond): RedirectResponse
    {
        $outcome = $respond->accept($booking, $request->user());

        return redirect()->route('host.bookings.index')->with('status', __('host.bookings.'.$outcome));
    }

    public function decline(Request $request, Booking $booking, RespondToBookingRequest $respond): RedirectResponse
    {
        $outcome = $respond->decline($booking, $request->user());

        return redirect()->route('host.bookings.index')->with('status', __('host.bookings.'.$outcome));
    }
}
