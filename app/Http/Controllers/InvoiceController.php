<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Booking;
use App\Support\Invoices;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class InvoiceController extends Controller
{

    public function index(Request $request): View
    {
        $bookings = Booking::where('user_id', $request->user()->id)
            ->with(['room.studio.user.hostProfile'])
            ->latest('date')
            ->get()
            ->filter(fn (Booking $booking) => Invoices::documentsFor($booking) !== []);

        return view('artist.invoices', ['bookings' => $bookings]);
    }

    public function download(Request $request, Booking $booking, string $type): Response
    {
        $user = $request->user();
        $isArtist = $booking->user_id === $user->id;
        $isHost = $booking->room->studio->user_id === $user->id;

        abort_unless($isArtist || $isHost || $user->role === UserRole::Admin, 403);

        $allowed = $isHost && ! $isArtist && $user->role !== UserRole::Admin
            ? Invoices::hostDocumentsFor($booking)
            : Invoices::documentsFor($booking);

        abort_unless(in_array($type, $allowed, true), 404);

        $data = Invoices::build($booking, $type);

        return Pdf::loadView('invoices.document', $data)->download($data['number'] . '.pdf');
    }
}
