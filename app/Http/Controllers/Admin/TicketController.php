<?php

namespace App\Http\Controllers\Admin;

use App\Actions\HandleTicket;
use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Support\AdminDesk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(): View
    {
        // Dezelfde tickets als in de app (AdminDesk).
        return view('admin.tickets.index', [
            'tickets' => AdminDesk::disputes(),
            'damages' => AdminDesk::damages(),
        ]);
    }

    /**
     * Reply to a problem report from a host and close the ticket. The host gets the
     * reply by mail and sees it back on their bookings page.
     */
    public function respondDamage(Request $request, Booking $booking, HandleTicket $handle): RedirectResponse
    {
        abort_if($booking->damage_reported_at === null, 404);

        if ($booking->damage_resolved_at !== null) {
            return redirect()->route('admin.tickets.index')->with('status', __('admin.tickets.already_resolved'));
        }

        $validated = $request->validate(HandleTicket::RESPOND_RULES);

        return redirect()->route('admin.tickets.index')
            ->with('status', __('admin.tickets.'.$handle->respondToDamage($booking, $validated['damage_response'])));
    }

    public function resolve(Request $request, Booking $booking, HandleTicket $handle): RedirectResponse
    {
        if ($booking->status !== BookingStatus::Disputed) {
            return redirect()->route('admin.tickets.index')->with('status', __('admin.tickets.already_resolved'));
        }

        $validated = $request->validate(HandleTicket::RESOLVE_RULES);

        return redirect()->route('admin.tickets.index')
            ->with('status', __('admin.tickets.'.$handle->resolveDispute($booking, (int) $validated['refund_percent'], $validated['resolution_note'])));
    }
}
