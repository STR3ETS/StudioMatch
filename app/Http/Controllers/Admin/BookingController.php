<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Notifications\BookingCancelled;
use App\Support\StripeService;
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
            'bookings' => Booking::query()
                ->when($status, fn ($query) => $query->where('status', $status))
                ->with(['room.studio', 'user'])
                ->latest()
                ->get(),
            'status' => $status,
        ]);
    }

    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(BookingStatus::class)],
        ]);

        $newStatus = BookingStatus::from($validated['status']);

        $booking->update([
            'status' => $newStatus,
            'cancelled_by' => $newStatus === BookingStatus::Cancelled ? 'admin' : $booking->cancelled_by,
        ]);

        if ($newStatus === BookingStatus::Cancelled) {
            StripeService::refund($booking, $booking->refundAmountCents(100));

            $booking->user->notify(new BookingCancelled($booking, 100));
            $booking->room->studio->user->notify(new BookingCancelled($booking, 100));
        }

        return redirect()->route('admin.bookings.index', $request->only('status'))
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
                    __('booking.status.' . $booking->status->value),
                    number_format($booking->rent_cents / 100, 2, ',', ''),
                    number_format($booking->service_fee_cents / 100, 2, ',', ''),
                    number_format($booking->vat_cents / 100, 2, ',', ''),
                    number_format($booking->total_cents / 100, 2, ',', ''),
                    $booking->created_at->format('d-m-Y H:i'),
                ], ';');
            }

            fclose($handle);
        }, 'studiomatch-boekingen-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=utf-8']);
    }
}
