<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingRescheduled;
use App\Notifications\ProblemReported;
use App\Support\StripeService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{

    public function create(Request $request, Room $room): View|RedirectResponse
    {
        abort_unless($room->isPubliclyVisible(), 404);

        [$date, $startHour, $endHour] = $this->validateSlot($request, $room);

        $withEngineer = $room->hasOptionalEngineer() && $request->boolean('engineer');

        return view('book.checkout', [
            'room' => $room->load(['studio', 'photos']),
            'date' => $date,
            'startHour' => $startHour,
            'endHour' => $endHour,
            'withEngineer' => $withEngineer,
            'prices' => $this->prices($room, $endHour - $startHour, $withEngineer),
        ]);
    }

    public function store(Request $request, Room $room): RedirectResponse
    {
        abort_unless($room->isPubliclyVisible(), 404);

        [$date, $startHour, $endHour] = $this->validateSlot($request, $room);

        $request->validate(['terms' => ['accepted']]);

        $user = $request->user();
        if ($user->street === null || $user->postal_code === null || $user->city === null) {
            $address = $request->validate([
                'street' => ['required', 'string', 'max:255'],
                'postal_code' => ['required', 'string', 'max:10'],
                'city' => ['required', 'string', 'max:100'],
            ]);

            $user->update($address);
        }

        $withEngineer = $room->hasOptionalEngineer() && $request->boolean('engineer');
        $prices = $this->prices($room, $endHour - $startHour, $withEngineer);

        $booking = DB::transaction(function () use ($request, $room, $date, $startHour, $endHour, $withEngineer, $prices) {
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
                'with_engineer' => $withEngineer,
                'status' => BookingStatus::PendingPayment,
                'expires_at' => now()->addMinutes((int) config('studio.checkout_hold_minutes')),
                'terms_accepted_at' => now(),
                ...$prices,
            ]);
        });

        return redirect()->route('bookings.payment', $booking);
    }

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

        return view('book.payment', [
            'booking' => $booking->load('room.studio'),
            'stripeEnabled' => StripeService::enabled(),
        ]);
    }

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

        if (StripeService::enabled()) {
            $url = StripeService::createCheckoutSession($booking);

            if ($url === null) {
                return back()->withErrors(['payment' => __('booking.errors.payment_failed')]);
            }

            return redirect()->away($url);
        }

        $booking->markAsPaid();

        return redirect()->route('dashboard.artist')->with('status', __('booking.paid'));
    }

    public function paid(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeBooking($request, $booking);

        $sessionId = (string) $request->query('session_id');

        if ($sessionId === '' || ! StripeService::verifyCheckoutPaid($booking, $sessionId)) {
            return redirect()->route('bookings.payment', $booking);
        }

        if (! $booking->markAsPaid()) {
            $booking->refresh();

            if ($booking->status === BookingStatus::Expired) {
                StripeService::refund($booking, $booking->total_cents);

                return redirect()
                    ->route('studios.show', $booking->room)
                    ->withErrors(['slot' => __('booking.errors.expired_refunded')]);
            }
        }

        return redirect()->route('dashboard.artist')->with('status', __('booking.paid'));
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeBooking($request, $booking);

        abort_unless(
            in_array($booking->status, [BookingStatus::PendingConfirmation, BookingStatus::Confirmed], true)
                && $booking->startsAt()->isFuture(),
            404,
        );

        $refundPercent = $booking->status === BookingStatus::PendingConfirmation
            ? 100
            : $booking->refundPercentForCancellationNow();

        $booking->update(['status' => BookingStatus::Cancelled, 'cancelled_by' => 'artist']);

        StripeService::refund($booking, $booking->refundAmountCents($refundPercent));

        $booking->user->notify(new BookingCancelled($booking, $refundPercent));
        $booking->room->studio->user->notify(new BookingCancelled($booking, $refundPercent));

        return redirect()->route('dashboard.artist')->with('status', __('booking.cancelled'));
    }

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

    public function reportProblem(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeBooking($request, $booking);

        abort_unless($booking->canReportProblem(), 404);

        $validated = $request->validate([
            'dispute_reason' => ['required', 'string', 'min:10', 'max:2000'],
            'dispute_studio_response' => ['nullable', 'string', 'max:2000'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $photos = collect($validated['photos'] ?? [])
            ->map(fn ($photo) => $photo->store('disputes/' . $booking->id, 'public'))
            ->all();

        $booking->update([
            'status' => BookingStatus::Disputed,
            'disputed_at' => now(),
            'dispute_reason' => $validated['dispute_reason'],
            'dispute_studio_response' => $validated['dispute_studio_response'] ?? null,
            'dispute_photos' => $photos !== [] ? $photos : null,
        ]);

        $booking->room->studio->user->notify(new ProblemReported($booking));
        Notification::send(User::where('role', UserRole::Admin)->get(), new ProblemReported($booking));

        return redirect()->route('dashboard.artist')->with('status', __('booking.problem_reported'));
    }

    public function ics(Request $request, Booking $booking): \Illuminate\Http\Response
    {
        $this->authorizeBooking($request, $booking);

        abort_unless($booking->status === BookingStatus::Confirmed, 404);

        $escape = fn (string $value) => str_replace([',', ';', "\n"], ['\,', '\;', '\n'], $value);

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//StudioMatch//NL',
            'BEGIN:VEVENT',
            'UID:artist-booking-' . $booking->id . '@studiomatch',
            'DTSTAMP:' . now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:' . $booking->startsAt()->copy()->utc()->format('Ymd\THis\Z'),
            'DTEND:' . $booking->endsAt()->copy()->utc()->format('Ymd\THis\Z'),
            'SUMMARY:' . $escape(__('booking.ics_summary', ['room' => $booking->room->studio->name . ' - ' . $booking->room->title])),
            'LOCATION:' . $escape($booking->room->studio->fullAddress()),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return response(implode("\r\n", $lines), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="studiomatch-boeking-' . $booking->id . '.ics"',
        ]);
    }

    private function validateSlot(Request $request, Room $room): array
    {
        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start' => ['required', 'integer', 'between:0,23'],
            'hours' => ['required', 'integer', 'min:' . $room->min_hours, 'max:' . config('studio.booking_max_hours')],
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

    private function prices(Room $room, int $hours, bool $withEngineer = false): array
    {
        $hourly = $room->hourly_rate_cents + ($withEngineer ? (int) $room->engineer_rate_cents : 0);
        $rent = $hourly * $hours;
        $fee = (int) round($rent * config('studio.service_fee_percent') / 100);
        $vat = (int) round($fee * config('studio.vat_percent') / 100);

        return [
            'hourly_rate_cents' => $hourly,
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
