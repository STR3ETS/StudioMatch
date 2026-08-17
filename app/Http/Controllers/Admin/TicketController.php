<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Notifications\DisputeResolved;
use App\Support\StripeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{

    public function index(): View
    {
        return view('admin.tickets.index', [
            'tickets' => Booking::where('status', BookingStatus::Disputed)
                ->with(['room.studio.user', 'user'])
                ->oldest('disputed_at')
                ->get(),
        ]);
    }

    public function resolve(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->status === BookingStatus::Disputed, 404);

        $validated = $request->validate([
            'refund_percent' => ['required', 'integer', 'between:0,100'],
            'resolution_note' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $percent = (int) $validated['refund_percent'];

        $booking->update([
            'status' => $percent === 100 ? BookingStatus::Cancelled : BookingStatus::Completed,
            'cancelled_by' => $percent === 100 ? 'admin' : $booking->cancelled_by,
            'resolution_note' => $validated['resolution_note'],
        ]);

        StripeService::refund($booking, $booking->refundAmountCents($percent));

        $booking->user->notify(new DisputeResolved($booking, $percent));
        $booking->room->studio->user->notify(new DisputeResolved($booking, $percent));

        return redirect()->route('admin.tickets.index')->with('status', __('admin.tickets.resolved'));
    }
}
