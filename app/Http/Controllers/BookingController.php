<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Concerns\VerifiesAddress;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingRescheduled;
use App\Notifications\ProblemReported;
use App\Support\Hours;
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
    use VerifiesAddress;

    public function create(Request $request, Room $room): View|RedirectResponse
    {
        abort_unless($room->isPubliclyVisible(), 404);

        $withEngineer = $room->hasOptionalEngineer() && $request->boolean('engineer');

        if ($request->filled('end_date')) {
            [$date, $endDate, $days] = $this->validateDayRange($request, $room);

            return view('book.checkout', [
                'room' => $room->load(['studio', 'photos']),
                'date' => $date,
                'endDate' => $endDate,
                'days' => $days,
                'startHour' => null,
                'endHour' => null,
                'withEngineer' => $withEngineer,
                'prices' => $this->dayPrices($room, $date, $endDate, $withEngineer),
            ]);
        }

        [$date, $startHour, $endHour] = $this->validateSlot($request, $room);

        return view('book.checkout', [
            'room' => $room->load(['studio', 'photos']),
            'date' => $date,
            'endDate' => null,
            'days' => null,
            'startHour' => $startHour,
            'endHour' => $endHour,
            'withEngineer' => $withEngineer,
            'prices' => $this->prices($room, $endHour - $startHour, $withEngineer),
        ]);
    }

    public function store(Request $request, Room $room): RedirectResponse
    {
        abort_unless($room->isPubliclyVisible(), 404);

        $multiDay = $request->filled('end_date');

        if ($multiDay) {
            [$date, $endDate, $days] = $this->validateDayRange($request, $room);
            $startHour = null;
            $endHour = null;
        } else {
            [$date, $startHour, $endHour] = $this->validateSlot($request, $room);
            $endDate = null;
        }

        $request->validate(['terms' => ['accepted']]);

        // Particulier of zakelijk boeken bepaalt wat er op de factuur komt.
        $buyer = $request->validate([
            'buyer_type' => ['nullable', 'in:particulier,zakelijk'],
            'buyer_company' => ['nullable', 'string', 'max:255', 'required_if:buyer_type,zakelijk'],
            'buyer_vat_number' => ['nullable', 'string', 'max:30'],
        ]);

        // Niets gekozen betekent particulier, dat is de veiligste aanname voor de consument.
        $buyer['buyer_type'] ??= 'particulier';

        $user = $request->user();
        if (! $user->hasCompleteAddress()) {
            $address = $request->validate([
                'street' => ['required', 'string', 'max:255'],
                'postal_code' => ['required', 'string', 'max:10'],
                'city' => ['required', 'string', 'max:100'],
            ]);

            $this->verifiedCoords($address, __('account.profile.address_invalid'));

            $user->update($address);
        }

        $withEngineer = $room->hasOptionalEngineer() && $request->boolean('engineer');
        $prices = $multiDay
            ? $this->dayPrices($room, $date, $endDate, $withEngineer)
            : $this->prices($room, $endHour - $startHour, $withEngineer);

        $booking = DB::transaction(function () use ($request, $room, $date, $endDate, $startHour, $endHour, $multiDay, $withEngineer, $prices, $buyer) {
            $taken = ($multiDay
                ? $room->bookingsBetween($date, $endDate)
                : $room->overlappingBookings($date, $startHour, $endHour))
                ->lockForUpdate()
                ->get()
                ->contains(fn (Booking $booking) => $booking->isActive());

            if ($taken) {
                throw ValidationException::withMessages(['slot' => __('booking.errors.taken')]);
            }

            return $room->bookings()->create([
                'user_id' => $request->user()->id,
                ...$buyer,
                'date' => $date,
                'end_date' => $endDate,
                'start_hour' => $multiDay ? 0 : $startHour,
                'end_hour' => $multiDay ? 24 : $endHour,
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

        $available = $endHour <= Hours::max()
            && ! $date->copy()->startOfDay()->addHours($startHour)->isPast()
            && $booking->room->isAvailableOn($date->copy(), $startHour, $endHour)
            && ! $booking->room->overlappingBookings($date, $startHour, $endHour)
                ->whereKeyNot($booking->id)
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
            'start' => ['required', 'integer', 'between:0,' . (Hours::max() - 1)],
            'hours' => ['required', 'integer', 'min:' . $room->min_hours, 'max:' . config('studio.booking_max_hours')],
        ]);

        $date = Carbon::parse($validated['date']);
        $startHour = (int) $validated['start'];
        $endHour = $startHour + (int) $validated['hours'];

        // Hours above 24 fall on the next morning, so a night session stays one booking.
        if ($endHour > Hours::max()) {
            throw ValidationException::withMessages(['slot' => __('booking.errors.unavailable')]);
        }

        if ($date->copy()->startOfDay()->addHours($startHour)->isPast()) {
            throw ValidationException::withMessages(['slot' => __('booking.errors.unavailable')]);
        }

        if (! $room->isBookableFor($date, $startHour, $endHour)) {
            throw ValidationException::withMessages(['slot' => __('booking.errors.unavailable')]);
        }

        return [$date, $startHour, $endHour];
    }

    private function prices(Room $room, int $hours, bool $withEngineer = false): array
    {
        $engineerPerHour = $withEngineer ? (int) $room->engineer_rate_cents : 0;
        $hourly = $room->hourly_rate_cents + $engineerPerHour;
        $rent = $hourly * $hours;
        $fee = (int) round($rent * config('studio.service_fee_percent') / 100);
        $vat = (int) round($fee * config('studio.vat_percent') / 100);

        return [
            'hourly_rate_cents' => $hourly,
            'rent_cents' => $rent,
            'engineer_cents' => $engineerPerHour * $hours,
            'service_fee_cents' => $fee,
            'vat_cents' => $vat,
            'total_cents' => $rent + $fee + $vat,
        ];
    }

    /**
     * Meerdaagse boekingen rekenen af per hele dag, zonder starttijd en zonder uurtarief.
     */
    private function dayPrices(Room $room, Carbon $from, Carbon $until, bool $withEngineer = false): array
    {
        $engineer = $withEngineer ? $room->engineerDayRateCents() : 0;

        // Per dag optellen, want een verhuurder mag bijvoorbeeld in het weekend meer vragen.
        $rent = 0;
        $days = 0;
        for ($date = $from->copy(); $date->lte($until); $date->addDay()) {
            $rent += $room->dayRateOn($date->isoWeekday()) + $engineer;
            $days++;
        }

        $fee = (int) round($rent * config('studio.service_fee_percent') / 100);
        $vat = (int) round($fee * config('studio.vat_percent') / 100);

        return [
            'hourly_rate_cents' => $room->hourly_rate_cents,
            'day_rate_cents' => $days > 0 ? (int) round($rent / $days) : (int) $room->day_rate_cents,
            'rent_cents' => $rent,
            'engineer_cents' => $engineer * $days,
            'service_fee_cents' => $fee,
            'vat_cents' => $vat,
            'total_cents' => $rent + $fee + $vat,
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: int}
     */
    private function validateDayRange(Request $request, Room $room): array
    {
        abort_unless($room->allowsMultiDay(), 404);

        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:date'],
        ]);

        $from = Carbon::parse($validated['date'])->startOfDay();
        $until = Carbon::parse($validated['end_date'])->startOfDay();
        $days = $from->diffInDays($until) + 1;

        if ($days < max(1, (int) $room->min_days) || $days > (int) config('studio.booking_max_days')) {
            throw ValidationException::withMessages(['slot' => __('booking.errors.unavailable')]);
        }

        if (! $room->isBookableForDays($from, $until)) {
            throw ValidationException::withMessages(['slot' => __('booking.errors.unavailable')]);
        }

        return [$from, $until, $days];
    }

    private function authorizeBooking(Request $request, Booking $booking): void
    {
        abort_unless($booking->user_id === $request->user()->id, 403);
    }
}
