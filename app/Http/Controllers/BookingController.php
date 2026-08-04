<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingReceived;
use App\Notifications\BookingRequested;
use App\Notifications\BookingRescheduled;
use App\Notifications\ProblemReported;
use Illuminate\Support\Facades\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    /**
     * Checkout: overzicht met prijsopbouw en akkoord op huisregels + AV (scope §2.5).
     */
    public function create(Request $request, Room $room): View|RedirectResponse
    {
        abort_unless($room->isPubliclyVisible(), 404);

        [$date, $startHour, $endHour] = $this->validateSlot($request, $room);

        return view('book.checkout', [
            'room' => $room->load(['studio', 'photos']),
            'date' => $date,
            'startHour' => $startHour,
            'endHour' => $endHour,
            'prices' => $this->prices($room, $endHour - $startHour),
        ]);
    }

    /**
     * Reserveer het slot: boeking in pending_payment met een 15-minutenblokkade,
     * beschermd met een database-lock tegen dubbele boekingen (scope §2.5).
     */
    public function store(Request $request, Room $room): RedirectResponse
    {
        abort_unless($room->isPubliclyVisible(), 404);

        [$date, $startHour, $endHour] = $this->validateSlot($request, $room);

        $request->validate(['terms' => ['accepted']]);

        $prices = $this->prices($room, $endHour - $startHour);

        $booking = DB::transaction(function () use ($request, $room, $date, $startHour, $endHour, $prices) {
            $taken = $room->bookings()
                ->whereDate('date', $date)
                ->where('start_hour', '<', $endHour)
                ->where('end_hour', '>', $startHour)
                ->lockForUpdate()
                ->get()
                ->contains(fn (Booking $booking) => $booking->isActive());

            if ($taken) {
                throw ValidationException::withMessages(['slot' => __('booking.errors.taken')]);
            }

            return $room->bookings()->create([
                'user_id' => $request->user()->id,
                'date' => $date,
                'start_hour' => $startHour,
                'end_hour' => $endHour,
                'status' => BookingStatus::PendingPayment,
                'expires_at' => now()->addMinutes((int) config('studio.checkout_hold_minutes')),
                'terms_accepted_at' => now(),
                ...$prices,
            ]);
        });

        return redirect()->route('bookings.payment', $booking);
    }

    /**
     * Betaalpagina. De echte Stripe-betaling volgt in de volgende fase;
     * tot die tijd is dit een duidelijk gemarkeerde simulatie.
     */
    public function payment(Request $request, Booking $booking): View|RedirectResponse
    {
        $this->authorizeBooking($request, $booking);

        if ($booking->status !== BookingStatus::PendingPayment) {
            return redirect()->route('dashboard.artist');
        }

        if ($booking->expires_at->isPast()) {
            $booking->update(['status' => BookingStatus::Expired]);

            return redirect()
                ->route('studios.show', $booking->room)
                ->withErrors(['slot' => __('booking.errors.expired')]);
        }

        return view('book.payment', ['booking' => $booking->load('room.studio')]);
    }

    /**
     * Simuleer een geslaagde betaling: pending_payment → pending_confirmation.
     */
    public function pay(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeBooking($request, $booking);

        abort_unless($booking->status === BookingStatus::PendingPayment, 404);

        if ($booking->expires_at->isPast()) {
            $booking->update(['status' => BookingStatus::Expired]);

            return redirect()
                ->route('studios.show', $booking->room)
                ->withErrors(['slot' => __('booking.errors.expired')]);
        }

        $booking->update(['status' => BookingStatus::PendingConfirmation, 'requested_at' => now()]);

        $booking->room->studio->user->notify(new BookingRequested($booking));
        $booking->user->notify(new BookingReceived($booking));

        return redirect()->route('dashboard.artist')->with('status', __('booking.paid'));
    }

    /**
     * Annuleren door de artiest, met de restitutiestaffel uit scope §2.8.
     */
    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeBooking($request, $booking);

        abort_unless(
            in_array($booking->status, [BookingStatus::PendingConfirmation, BookingStatus::Confirmed], true)
                && $booking->startsAt()->isFuture(),
            404,
        );

        $refundPercent = $booking->status === BookingStatus::PendingConfirmation
            ? 100 // nog niet geaccepteerd door de studio: alles terug
            : $booking->refundPercentForCancellationNow();

        $booking->update(['status' => BookingStatus::Cancelled, 'cancelled_by' => 'artist']);

        $booking->user->notify(new BookingCancelled($booking, $refundPercent));
        $booking->room->studio->user->notify(new BookingCancelled($booking, $refundPercent));

        return redirect()->route('dashboard.artist')->with('status', __('booking.cancelled'));
    }

    /**
     * Verzetten (BESLISSING 9): eenmalig, tot 48 uur voor start, zelfde ruimte
     * en duur, alleen naar een vrij slot. Gaat opnieuw langs de verhuurder.
     */
    public function reschedule(Request $request, Booking $booking): View|RedirectResponse
    {
        $this->authorizeBooking($request, $booking);

        abort_unless($booking->canReschedule(), 404);

        return view('book.reschedule', [
            'booking' => $booking->load('room.studio'),
            'freeHours' => $booking->room->freeHoursByDate(),
        ]);
    }

    public function rescheduleStore(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeBooking($request, $booking);

        abort_unless($booking->canReschedule(), 404);

        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start' => ['required', 'integer', 'between:0,23'],
        ]);

        $date = Carbon::parse($validated['date']);
        $startHour = (int) $validated['start'];
        $endHour = $startHour + $booking->hours();

        $available = $endHour <= 24
            && ! ($date->isToday() && $startHour <= now()->hour)
            && $booking->room->isAvailableOn($date->copy(), $startHour, $endHour)
            && ! $booking->room->bookings()
                ->active()
                ->whereKeyNot($booking->id)
                ->whereDate('date', $date)
                ->where('start_hour', '<', $endHour)
                ->where('end_hour', '>', $startHour)
                ->exists();

        if (! $available) {
            throw ValidationException::withMessages(['slot' => __('booking.errors.unavailable')]);
        }

        $booking->update([
            'date' => $date,
            'start_hour' => $startHour,
            'end_hour' => $endHour,
            'status' => BookingStatus::PendingConfirmation,
            'confirmed_at' => null,
            'rescheduled_at' => now(),
            'requested_at' => now(),
        ]);

        $booking->user->notify(new BookingRescheduled($booking));
        $booking->room->studio->user->notify(new BookingRescheduled($booking));

        return redirect()->route('dashboard.artist')->with('status', __('booking.rescheduled'));
    }

    /**
     * "Meld een probleem" (scope §2.5): tot 24 uur na de starttijd. Zet de
     * uitbetaling op hold en maakt een ticket aan voor de admin.
     */
    public function reportProblem(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeBooking($request, $booking);

        abort_unless($booking->canReportProblem(), 404);

        $validated = $request->validate([
            'dispute_reason' => ['required', 'string', 'min:10', 'max:2000'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        // Bewijsfoto's voor een betere beoordeling door de admin.
        $photos = collect($validated['photos'] ?? [])
            ->map(fn ($photo) => $photo->store('disputes/' . $booking->id, 'public'))
            ->all();

        $booking->update([
            'status' => BookingStatus::Disputed,
            'disputed_at' => now(),
            'dispute_reason' => $validated['dispute_reason'],
            'dispute_photos' => $photos !== [] ? $photos : null,
        ]);

        $booking->room->studio->user->notify(new ProblemReported($booking));
        Notification::send(User::where('role', UserRole::Admin)->get(), new ProblemReported($booking));

        return redirect()->route('dashboard.artist')->with('status', __('booking.problem_reported'));
    }

    /**
     * Valideer datum en tijdvak tegen het slotraster en de echte beschikbaarheid.
     *
     * @return array{0: Carbon, 1: int, 2: int}
     */
    private function validateSlot(Request $request, Room $room): array
    {
        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start' => ['required', 'integer', 'between:0,23'],
            'hours' => ['required', 'integer', 'min:' . $room->min_hours, 'max:8'],
        ]);

        $date = Carbon::parse($validated['date']);
        $startHour = (int) $validated['start'];
        $endHour = $startHour + (int) $validated['hours'];

        if ($endHour > 24) {
            throw ValidationException::withMessages(['slot' => __('booking.errors.unavailable')]);
        }

        if ($date->isToday() && $startHour <= now()->hour) {
            throw ValidationException::withMessages(['slot' => __('booking.errors.unavailable')]);
        }

        if (! $room->isBookableFor($date, $startHour, $endHour)) {
            throw ValidationException::withMessages(['slot' => __('booking.errors.unavailable')]);
        }

        return [$date, $startHour, $endHour];
    }

    /**
     * Prijsopbouw: huur + servicekosten voor de artiest + btw over de servicekosten.
     * De verhuurder ontvangt zijn volledige uurtarief (klantkeuze bij BESLISSING 2).
     *
     * @return array<string, int>
     */
    private function prices(Room $room, int $hours): array
    {
        $rent = $room->hourly_rate_cents * $hours;
        $fee = (int) round($rent * config('studio.service_fee_percent') / 100);
        $vat = (int) round($fee * config('studio.vat_percent') / 100);

        return [
            'hourly_rate_cents' => $room->hourly_rate_cents,
            'rent_cents' => $rent,
            'service_fee_cents' => $fee,
            'vat_cents' => $vat,
            'total_cents' => $rent + $fee + $vat,
        ];
    }

    private function authorizeBooking(Request $request, Booking $booking): void
    {
        abort_unless($booking->user_id === $request->user()->id, 403);
    }
}
