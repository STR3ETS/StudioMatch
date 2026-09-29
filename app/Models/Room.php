<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\ExceptionType;
use App\Enums\RoomStatus;
use App\Enums\RoomType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

#[Fillable([
    'title', 'description', 'type', 'types', 'hourly_rate_cents', 'day_rate_cents', 'min_hours', 'min_days', 'capacity',
    'engineer_included', 'engineer_rate_cents', 'engineer_day_rate_cents', 'house_rules', 'equipment', 'equipment_extra', 'daws', 'facilities', 'status',
    'rejection_reason', 'on_vacation', 'vacation_until',
])]
class Room extends Model
{
    protected static function booted(): void
    {

        static::creating(function (Room $room) {
            if ($room->slug === null) {
                $base = Str::slug($room->studio->name . ' ' . $room->title);
                $slug = $base;
                $suffix = 2;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $suffix++;
                }
                $room->slug = $slug;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'type' => RoomType::class,
            'types' => 'array',
            'status' => RoomStatus::class,
            'engineer_included' => 'boolean',
            'equipment' => 'array',
            'daws' => 'array',
            'facilities' => 'array',
            'on_vacation' => 'boolean',
            'vacation_until' => 'date',
        ];
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('status', RoomStatus::Live)
            ->where(function (Builder $query) {
                $query->where('on_vacation', false)
                    ->orWhere(function (Builder $query) {
                        $query->whereNotNull('vacation_until')->whereDate('vacation_until', '<', today());
                    });
            });
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status === RoomStatus::Live && ! $this->isOnVacation();
    }

    /**
     * Alle categorieen waaronder de ruimte bekend staat, met de hoofdcategorie voorop.
     *
     * @return array<int, string>
     */
    public function typeValues(): array
    {
        $values = array_values(array_unique(array_filter((array) ($this->types ?: []))));

        if ($values === [] && $this->type !== null) {
            $values = [$this->type->value];
        }

        return $values;
    }

    public function typeLabel(): string
    {
        return collect($this->typeValues())
            ->map(fn (string $value) => __('host.types.' . $value))
            ->join(' · ');
    }

    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(RoomPhoto::class)->orderBy('sort_order');
    }

    /**
     * De uitgelichte ruimtes op de homepage: eerst de populairste, daarna de nieuwste.
     *
     * Populariteit meten we aan het aantal afgeronde boekingen. Bewust simpel gehouden:
     * zonder reviewsysteem zou een zwaarder algoritme nergens op gebaseerd zijn. Zolang
     * er nog niets is afgerond, is de lijst precies wat hij altijd was: de nieuwste
     * aanmeldingen. Zo krijgen nieuwe studio's ook altijd een plek naast de bekende.
     */
    public static function featured(int $limit = 8, int $popularSlots = 4): Collection
    {
        $popular = static::query()
            ->publiclyVisible()
            ->whereHas('bookings', fn (Builder $query) => $query->where('status', BookingStatus::Completed))
            ->withCount(['bookings as completed_bookings_count' => fn (Builder $query) => $query->where('status', BookingStatus::Completed)])
            ->with(['studio', 'photos'])
            ->orderByDesc('completed_bookings_count')
            ->latest()
            ->orderByDesc('id')
            ->take(max(0, min($popularSlots, $limit)))
            ->get();

        $newest = static::query()
            ->publiclyVisible()
            ->whereKeyNot($popular->modelKeys())
            ->with(['studio', 'photos'])
            ->latest()
            ->orderByDesc('id')
            ->take($limit - $popular->count())
            ->get();

        return $popular->concat($newest);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function hours(): HasMany
    {
        return $this->hasMany(RoomHour::class)->orderBy('weekday');
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(RoomException::class)->orderBy('date');
    }

    public function isOnVacation(): bool
    {
        return $this->on_vacation
            && ($this->vacation_until === null || $this->vacation_until->endOfDay()->isFuture());
    }

    public function effectiveStatus(): RoomStatus
    {
        if ($this->status === RoomStatus::Live && $this->isOnVacation()) {
            return RoomStatus::Vakantie;
        }

        return $this->status;
    }

    public function isAvailableOn(CarbonInterface $date, ?int $startHour = null, ?int $endHour = null): bool
    {
        $dayExceptions = $this->exceptions->filter(fn ($exception) => $exception->date->isSameDay($date));

        if ($dayExceptions->contains(fn ($exception) => $exception->type === ExceptionType::Closed)) {
            return false;
        }

        $windows = collect();
        $weekly = $this->hours->firstWhere('weekday', $date->isoWeekday());
        if ($weekly?->is_open) {
            $windows->push([(int) $weekly->open_hour, (int) $weekly->close_hour]);
        }
        foreach ($dayExceptions->where('type', ExceptionType::Open) as $exception) {
            $windows->push([(int) $exception->start_hour, (int) $exception->end_hour]);
        }

        if ($windows->isEmpty()) {
            return false;
        }

        if ($startHour === null || $endHour === null) {
            return true;
        }

        $covered = $windows->contains(fn ($window) => $window[0] <= $startHour && $window[1] >= $endHour);

        if (! $covered) {
            return false;
        }

        return ! $dayExceptions->where('type', ExceptionType::Block)
            ->contains(fn ($exception) => $exception->start_hour < $endHour && $exception->end_hour > $startHour);
    }

    public function freeHoursOn(CarbonInterface $date, $dayBookings = null, $previousDayBookings = null, $multiDayBookings = null): array
    {
        $dayExceptions = $this->exceptions->filter(fn ($exception) => $exception->date->isSameDay($date));

        if ($dayExceptions->contains(fn ($exception) => $exception->type === ExceptionType::Closed)) {
            return [];
        }

        // Een meerdaagse boeking legt beslag op de hele dag.
        foreach ($multiDayBookings ?? [] as $booking) {
            if ($booking->date->lte($date) && $booking->end_date->gte($date)) {
                return [];
            }
        }

        $free = [];

        $weekly = $this->hours->firstWhere('weekday', $date->isoWeekday());
        if ($weekly?->is_open) {
            for ($h = (int) $weekly->open_hour; $h < (int) $weekly->close_hour; $h++) {
                $free[$h] = true;
            }
        }
        foreach ($dayExceptions->where('type', ExceptionType::Open) as $exception) {
            for ($h = (int) $exception->start_hour; $h < (int) $exception->end_hour; $h++) {
                $free[$h] = true;
            }
        }

        foreach ($dayExceptions->where('type', ExceptionType::Block) as $exception) {
            for ($h = (int) $exception->start_hour; $h < (int) $exception->end_hour; $h++) {
                unset($free[$h]);
            }
        }
        foreach ($dayBookings ?? [] as $booking) {
            for ($h = (int) $booking->start_hour; $h < (int) $booking->end_hour; $h++) {
                unset($free[$h]);
            }
        }

        // Yesterday's late session runs into this morning: hour 25 there is hour 1 here.
        foreach ($previousDayBookings ?? [] as $booking) {
            for ($h = max(0, (int) $booking->start_hour - 24); $h < (int) $booking->end_hour - 24; $h++) {
                unset($free[$h]);
            }
        }

        if ($date->isToday()) {
            foreach (array_keys($free) as $h) {
                if ($h <= now()->hour) {
                    unset($free[$h]);
                }
            }
        }

        $hours = array_keys($free);
        sort($hours);

        return $hours;
    }

    public function freeHoursByDate(?int $days = null): array
    {
        $days ??= (int) config('studio.booking_horizon_days');

        $this->loadMissing(['hours', 'exceptions']);

        $from = today();

        $until = $from->copy()->addDays($days);

        $bookings = $this->bookings()
            ->active()
            ->whereBetween('date', [$from->copy()->subDay()->toDateString(), $until->toDateString()])
            ->get();

        $multiDay = $this->bookings()
            ->active()
            ->whereNotNull('end_date')
            ->whereDate('date', '<=', $until)
            ->whereDate('end_date', '>=', $from)
            ->get();

        $byDate = $bookings->groupBy(fn (Booking $booking) => $booking->date->toDateString());

        $result = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $from->copy()->addDays($i);
            $result[$date->toDateString()] = $this->freeHoursOn(
                $date,
                $byDate->get($date->toDateString()),
                $byDate->get($date->copy()->subDay()->toDateString()),
                $multiDay,
            );
        }

        return $result;
    }

    /**
     * Dates that are free for a whole day booking: the room is open and nothing touches
     * that day, including a night session that spills over from the day before.
     *
     * @return array<int, string>
     */
    public function freeWholeDays(?int $days = null): array
    {
        $days ??= (int) config('studio.booking_horizon_days');

        $this->loadMissing(['hours', 'exceptions']);

        $from = today();
        $until = $from->copy()->addDays($days);

        $occupied = [];
        $bookings = $this->bookings()
            ->active()
            ->where(function (Builder $query) use ($from, $until) {
                $query->whereBetween('date', [$from->copy()->subDay()->toDateString(), $until->toDateString()])
                    ->orWhere(function (Builder $query) use ($from) {
                        $query->whereNotNull('end_date')->whereDate('end_date', '>=', $from);
                    });
            })
            ->get();

        foreach ($bookings as $booking) {
            $last = $booking->end_date?->copy() ?? $booking->date->copy();

            if (! $booking->isMultiDay() && (int) $booking->end_hour > 24) {
                $last->addDay();
            }

            for ($date = $booking->date->copy(); $date->lte($last); $date->addDay()) {
                $occupied[$date->toDateString()] = true;
            }
        }

        $free = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $from->copy()->addDays($i);

            if (! isset($occupied[$date->toDateString()]) && $this->isAvailableOn($date)) {
                $free[] = $date->toDateString();
            }
        }

        return $free;
    }

    public function hasOptionalEngineer(): bool
    {
        return ! $this->engineer_included && $this->engineer_rate_cents !== null;
    }

    public function isBookableFor(CarbonInterface $date, int $startHour, int $endHour): bool
    {
        if (! $this->isAvailableOn($date, $startHour, $endHour)) {
            return false;
        }

        return ! $this->overlappingBookings($date, $startHour, $endHour)->exists();
    }

    /**
     * Bookings that occupy any part of the requested block. A session that runs past
     * midnight stays on its starting date with an hour above 24, so the day before has to
     * be checked as well: its hours 24 and up land on this day.
     */
    public function overlappingBookings(CarbonInterface $date, int $startHour, int $endHour): HasMany
    {
        return $this->bookings()
            ->active()
            ->where(function (Builder $query) use ($date, $startHour, $endHour) {
                $query->where(function (Builder $query) use ($date, $startHour, $endHour) {
                    $query->whereDate('date', $date)
                        ->where('start_hour', '<', $endHour)
                        ->where('end_hour', '>', $startHour);
                })->orWhere(function (Builder $query) use ($date, $startHour) {
                    $query->whereDate('date', $date->copy()->subDay())
                        ->where('end_hour', '>', $startHour + 24);
                })->orWhere(function (Builder $query) use ($date) {
                    // Een meerdaagse boeking bezet de hele dag.
                    $query->whereNotNull('end_date')
                        ->whereDate('date', '<=', $date)
                        ->whereDate('end_date', '>=', $date);
                });
            });
    }

    public function seedDefaultHours(): void
    {
        for ($weekday = 1; $weekday <= 7; $weekday++) {
            $this->hours()->firstOrCreate(
                ['weekday' => $weekday],
                ['is_open' => $weekday <= 5, 'open_hour' => 9, 'close_hour' => 21],
            );
        }
    }

    /**
     * Consumentenprijs: het tarief inclusief servicekosten en de btw daarover, zodat de
     * getoonde prijs het bedrag is dat de huurder ook echt betaalt.
     */
    public static function allInCents(int $cents): int
    {
        $fee = (int) round($cents * config('studio.service_fee_percent') / 100);
        $vat = (int) round($fee * config('studio.vat_percent') / 100);

        return $cents + $fee + $vat;
    }

    /**
     * Zelfde bedrag, afgerond op hele euro's: onder 50 cent omlaag, anders omhoog.
     */
    public static function allInEuros(int $cents): int
    {
        return (int) round(self::allInCents($cents) / 100);
    }

    public function displayHourlyEuros(): int
    {
        return self::allInEuros((int) $this->hourly_rate_cents);
    }

    public function displayDayEuros(): ?int
    {
        return $this->day_rate_cents === null ? null : self::allInEuros((int) $this->day_rate_cents);
    }

    public function hourlyRateEuros(): float
    {
        return $this->hourly_rate_cents / 100;
    }

    public function dayRateEuros(): ?float
    {
        return $this->day_rate_cents === null ? null : $this->day_rate_cents / 100;
    }

    /**
     * Only rooms with a day rate can be booked for whole days.
     */
    public function allowsMultiDay(): bool
    {
        return $this->day_rate_cents !== null && $this->day_rate_cents > 0;
    }

    /**
     * Day rate for one weekday. Hosts may charge more in the weekend; without an override
     * the base day rate of the room applies.
     */
    public function dayRateOn(int $weekday): int
    {
        $override = $this->hours->firstWhere('weekday', $weekday)?->day_rate_cents;

        return (int) ($override ?: $this->day_rate_cents);
    }

    /**
     * @return array<int, int> weekday (1-7) => day rate in cents
     */
    public function dayRatesByWeekday(): array
    {
        $this->loadMissing('hours');

        return collect(range(1, 7))
            ->mapWithKeys(fn (int $weekday) => [$weekday => $this->dayRateOn($weekday)])
            ->all();
    }

    public function engineerDayRateCents(): int
    {
        // Zonder eigen dagprijs rekenen we de engineer voor acht uur per dag.
        return (int) ($this->engineer_day_rate_cents ?: (int) $this->engineer_rate_cents * 8);
    }

    /**
     * A whole day counts as booked when the room is closed that day, or when any booking
     * touches it. Hourly bookings and multi day bookings both block the day.
     */
    public function isBookableForDays(CarbonInterface $from, CarbonInterface $until): bool
    {
        $from = $from->copy()->startOfDay();
        $until = $until->copy()->startOfDay();

        for ($date = $from->copy(); $date->lte($until); $date->addDay()) {
            if (! $this->isAvailableOn($date)) {
                return false;
            }
        }

        return ! $this->bookingsBetween($from, $until)->exists();
    }

    /**
     * Bookings that touch any day in the range, including a night session that started the
     * day before and a multi day booking that started earlier.
     */
    public function bookingsBetween(CarbonInterface $from, CarbonInterface $until): HasMany
    {
        return $this->bookings()
            ->active()
            ->where(function (Builder $query) use ($from, $until) {
                $query->whereBetween('date', [$from->toDateString(), $until->toDateString()])
                    ->orWhere(function (Builder $query) use ($from) {
                        $query->whereDate('date', $from->copy()->subDay())
                            ->where('end_hour', '>', 24);
                    })
                    ->orWhere(function (Builder $query) use ($from, $until) {
                        $query->whereNotNull('end_date')
                            ->whereDate('date', '<=', $until)
                            ->whereDate('end_date', '>=', $from);
                    });
            });
    }

}
