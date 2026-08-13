<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Support\Invoices;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{

    public function index(Request $request): View
    {
        $bookings = Booking::whereIn('room_id', $request->user()->rooms()->select('rooms.id'))
            ->with(['room.studio.user.hostProfile', 'user'])
            ->latest('date')
            ->get()
            ->filter(fn (Booking $booking) => Invoices::hostDocumentsFor($booking) !== []);

        return view('host.invoices', ['bookings' => $bookings]);
    }
}
