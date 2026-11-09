<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ChangeBookingStatus;
use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Support\AdminDesk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->validate([
            'status' => ['nullable', Rule::enum(BookingStatus::class)],
        ])['status'] ?? null;

        return view('admin.bookings.index', [
            'bookings' => AdminDesk::bookings($status),
            'status' => $status,
        ]);
    }

    public function updateStatus(Request $request, Booking $booking, ChangeBookingStatus $change): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(BookingStatus::class)],
            'filter_status' => ['nullable', Rule::enum(BookingStatus::class)],
        ]);

        $change($booking, BookingStatus::from($validated['status']));

        return redirect()->route('admin.bookings.index', array_filter(['status' => $validated['filter_status'] ?? null]))
            ->with('status', __('admin.bookings.updated'));
    }

    public function export(): StreamedResponse
    {
        $bookings = Booking::with(['room.studio', 'user'])->latest()->get();

        return response()->streamDownload(function () use ($bookings) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['ID', 'Datum', 'Tijd', 'Studio', 'Ruimte', 'Artiest', 'E-mail', 'Status', 'Huur', 'Servicekosten', 'Btw', 'Totaal', 'Aangemaakt'], ';');

            foreach ($bookings as $booking) {
                fputcsv($handle, [
                    $booking->id,
                    $booking->date->format('d-m-Y'),
                    $booking->timeRange(),
                    $booking->room->studio->name,
                    $booking->room->title,
                    $booking->user->name,
                    $booking->user->email,
                    __('booking.status.'.$booking->status->value),
                    number_format($booking->rent_cents / 100, 2, ',', ''),
                    number_format($booking->service_fee_cents / 100, 2, ',', ''),
                    number_format($booking->vat_cents / 100, 2, ',', ''),
                    number_format($booking->total_cents / 100, 2, ',', ''),
                    $booking->created_at->format('d-m-Y H:i'),
                ], ';');
            }

            fclose($handle);
        }, 'studiomatch-boekingen-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=utf-8']);
    }
}
