<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Notifications\DamageResponded;
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
            'damages' => Booking::whereNotNull('damage_reported_at')
                ->with(['room.studio.user', 'user'])
                ->orderByRaw('damage_resolved_at is null desc')
                ->latest('damage_reported_at')
                ->take(20)
                ->get(),
        ]);
    }

    /**
     * Reply to a problem report from a host and close the ticket. The host gets the
     * reply by mail and sees it back on their bookings page.
     */
    public function respondDamage(Request $request, Booking $booking): RedirectResponse
    {
        abort_if($booking->damage_reported_at === null, 404);

        if ($booking->damage_resolved_at !== null) {
            return redirect()->route('admin.tickets.index')->with('status', __('admin.tickets.already_resolved'));
        }

        $validated = $request->validate([
            'damage_response' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $booking->update([
            'damage_response' => $validated['damage_response'],
            'damage_resolved_at' => now(),
        ]);

        $booking->room->studio->user->notify(new DamageResponded($booking));

        return redirect()->route('admin.tickets.index')->with('status', __('admin.tickets.damage_responded'));
    }

    public function resolve(Request $request, Booking $booking): RedirectResponse
    {
        if ($booking->status !== BookingStatus::Disputed) {
            return redirect()->route('admin.tickets.index')->with('status', __('admin.tickets.already_resolved'));
        }

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
