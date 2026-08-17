<?php

namespace App\Http\Controllers\Host;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingDeclined;
use App\Support\StripeService;
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
        ]);
    }

    public function accept(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->room->studio->user_id === $request->user()->id, 403);

        if ($booking->status !== BookingStatus::PendingConfirmation) {
            return redirect()->route('host.bookings.index')->with('status', __('host.bookings.already_handled'));
        }

        $booking->update(['status' => BookingStatus::Confirmed, 'confirmed_at' => now()]);

        $booking->user->notify(new BookingConfirmed($booking));
        $booking->room->studio->user->notify(new BookingConfirmed($booking));

        return redirect()->route('host.bookings.index')->with('status', __('host.bookings.accepted'));
    }

    public function decline(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->room->studio->user_id === $request->user()->id, 403);

        if ($booking->status !== BookingStatus::PendingConfirmation) {
            return redirect()->route('host.bookings.index')->with('status', __('host.bookings.already_handled'));
        }

        $booking->update(['status' => BookingStatus::Declined]);

        StripeService::refund($booking, $booking->refundAmountCents(100));

        $booking->user->notify(new BookingDeclined($booking));

        return redirect()->route('host.bookings.index')->with('status', __('host.bookings.declined'));
    }
}
