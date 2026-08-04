<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Notifications\BookingCancelled;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TicketController extends Controller
{
    /**
     * Tickets: gemelde problemen met uitbetaling op hold (scope §2.9 admin).
     */
    public function index(): View
    {
        return view('admin.tickets.index', [
            'tickets' => Booking::where('status', BookingStatus::Disputed)
                ->with(['room.studio.user', 'user'])
                ->oldest('disputed_at')
                ->get(),
        ]);
    }

    /**
     * Dispuut afgehandeld in het voordeel van de studio: uitbetaling vrijgeven.
     */
    public function release(Booking $booking): RedirectResponse
    {
        abort_unless($booking->status === BookingStatus::Disputed, 404);

        $booking->update(['status' => BookingStatus::Completed]);

        return redirect()->route('admin.tickets.index')->with('status', __('admin.tickets.released'));
    }

    /**
     * Dispuut afgehandeld in het voordeel van de artiest: annuleren + volledige
     * terugbetaling (de echte refund volgt in de Stripe-fase).
     */
    public function cancel(Booking $booking): RedirectResponse
    {
        abort_unless($booking->status === BookingStatus::Disputed, 404);

        $booking->update(['status' => BookingStatus::Cancelled, 'cancelled_by' => 'admin']);

        $booking->user->notify(new BookingCancelled($booking, 100));
        $booking->room->studio->user->notify(new BookingCancelled($booking, 100));

        return redirect()->route('admin.tickets.index')->with('status', __('admin.tickets.cancelled'));
    }
}
