<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Http\Controllers\Concerns\VerifiesAddress;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Support\Hours;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Een ruimte boeken: het gekozen moment controleren, de prijs berekenen en het tijdslot vasthouden tot er
 * betaald is. Gedeeld door de website (BookingController) en de app (Api\App\BookingController), zodat
 * beide dezelfde regels en dezelfde prijs hebben.
 *
 * Een moment is per uur (date, start, hours) of per hele dag (date, end_date).
 */
class BookRoom
{
    use VerifiesAddress;

    /**
     * @param  array<string, mixed>  $input
     * @return array{date: Carbon, end_date: ?Carbon, start: ?int, end: ?int, days: ?int, engineer: bool}
     */
    public function period(Room $room, array $input): array
    {
        $engineer = $room->hasOptionalEngineer() && filter_var($input['engineer'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (filled($input['end_date'] ?? null)) {
            [$date, $endDate, $days] = $this->dayRange($room, $input);

            return ['date' => $date, 'end_date' => $endDate, 'start' => null, 'end' => null, 'days' => $days, 'engineer' => $engineer];
        }

        [$date, $start, $end] = $this->slot($room, $input);

        return ['date' => $date, 'end_date' => null, 'start' => $start, 'end' => $end, 'days' => null, 'engineer' => $engineer];
    }

    /**
     * @param  array{date: Carbon, end_date: ?Carbon, start: ?int, end: ?int, days: ?int, engineer: bool}  $period
     * @return array<string, int>
     */
    public function prices(Room $room, array $period): array
    {
        return $period['end_date'] !== null
            ? $this->dayPrices($room, $period['date'], $period['end_date'], $period['engineer'])
            : $this->hourPrices($room, $period['end'] - $period['start'], $period['engineer']);
    }

    /**
     * De boeking aanmaken met status "wacht op betaling": het tijdslot staat dan checkout_hold_minutes vast.
     * Ontbreekt het factuuradres van de artiest nog, dan moet dat mee (en bestaan).
     *
     * @param  array<string, mixed>  $input
     */
    public function reserve(User $user, Room $room, array $input): Booking
    {
        $period = $this->period($room, $input);

        Validator::make($input, ['terms' => ['accepted']])->validate();

        // Particulier of zakelijk boeken bepaalt wat er op de factuur komt.
        $buyer = Validator::make($input, [
            'buyer_type' => ['nullable', 'in:particulier,zakelijk'],
            'buyer_company' => ['nullable', 'string', 'max:255', 'required_if:buyer_type,zakelijk'],
            'buyer_vat_number' => ['nullable', 'string', 'max:30'],
        ])->validate();

        // Niets gekozen betekent particulier, dat is de veiligste aanname voor de consument.
        $buyer['buyer_type'] ??= 'particulier';

        if (! $user->hasCompleteAddress()) {
            $address = Validator::make($input, [
                'street' => ['required', 'string', 'max:255'],
                'postal_code' => ['required', 'string', 'max:10'],
                'city' => ['required', 'string', 'max:100'],
            ])->validate();

            $this->verifiedCoords($address, __('account.profile.address_invalid'));

            $user->update($address);
        }

        $prices = $this->prices($room, $period);
        $multiDay = $period['end_date'] !== null;

        return DB::transaction(function () use ($user, $room, $period, $multiDay, $prices, $buyer) {
            $taken = ($multiDay
                ? $room->bookingsBetween($period['date'], $period['end_date'])
                : $room->overlappingBookings($period['date'], $period['start'], $period['end']))
                ->lockForUpdate()
                ->get()
                ->contains(fn (Booking $booking) => $booking->isActive());

            if ($taken) {
                throw ValidationException::withMessages(['slot' => __('booking.errors.taken')]);
            }

            return $room->bookings()->create([
                'user_id' => $user->id,
                ...$buyer,
                'date' => $period['date'],
                'end_date' => $period['end_date'],
                'start_hour' => $multiDay ? 0 : $period['start'],
                'end_hour' => $multiDay ? 24 : $period['end'],
                'with_engineer' => $period['engineer'],
                'status' => BookingStatus::PendingPayment,
                'expires_at' => now()->addMinutes((int) config('studio.checkout_hold_minutes')),
                'terms_accepted_at' => now(),
                ...$prices,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{0: Carbon, 1: int, 2: int}
     */
    private function slot(Room $room, array $input): array
    {
        $validated = Validator::make($input, [
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start' => ['required', 'integer', 'between:0,'.(Hours::max() - 1)],
            'hours' => ['required', 'integer', 'min:'.$room->min_hours, 'max:'.config('studio.booking_max_hours')],
        ])->validate();

        $date = Carbon::parse($validated['date']);
        $startHour = (int) $validated['start'];
        $endHour = $startHour + (int) $validated['hours'];

        // Uren boven 24 vallen op de volgende ochtend, zodat een nachtsessie één boeking blijft.
        if ($endHour > Hours::max()
            || $date->copy()->startOfDay()->addHours($startHour)->isPast()
            || ! $room->isBookableFor($date, $startHour, $endHour)) {
            throw ValidationException::withMessages(['slot' => __('booking.errors.unavailable')]);
        }

        return [$date, $startHour, $endHour];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{0: Carbon, 1: Carbon, 2: int}
     */
    private function dayRange(Room $room, array $input): array
    {
        abort_unless($room->allowsMultiDay(), 404);

        $validated = Validator::make($input, [
            'date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:date'],
        ])->validate();

        $from = Carbon::parse($validated['date'])->startOfDay();
        $until = Carbon::parse($validated['end_date'])->startOfDay();
        $days = $from->diffInDays($until) + 1;

        if ($days < max(1, (int) $room->min_days)
            || $days > (int) config('studio.booking_max_days')
            || ! $room->isBookableForDays($from, $until)) {
            throw ValidationException::withMessages(['slot' => __('booking.errors.unavailable')]);
        }

        return [$from, $until, (int) $days];
    }

    /**
     * @return array<string, int>
     */
    private function hourPrices(Room $room, int $hours, bool $withEngineer): array
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
     *
     * @return array<string, int>
     */
    private function dayPrices(Room $room, Carbon $from, Carbon $until, bool $withEngineer): array
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
}
