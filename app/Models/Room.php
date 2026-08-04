<?php

namespace App\Models;

use App\Enums\ExceptionType;
use App\Enums\RoomStatus;
use App\Enums\RoomType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'title', 'description', 'type', 'hourly_rate_cents', 'min_hours', 'capacity',
    'engineer_included', 'house_rules', 'equipment', 'equipment_extra', 'daws', 'facilities', 'status',
    'rejection_reason', 'on_vacation', 'vacation_until',
])]
class Room extends Model
{
    protected static function booted(): void
    {
        // Slug voor de publieke, indexeerbare URL (scope §2.1 SEO). Blijft daarna stabiel.
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

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => RoomType::class,
            'status' => RoomStatus::class,
            'engineer_included' => 'boolean',
            'equipment' => 'array',
            'daws' => 'array',
            'facilities' => 'array',
            'on_vacation' => 'boolean',
            'vacation_until' => 'date',
        ];
    }

    /**
     * Alleen ruimtes die publiek zichtbaar zijn: live en niet in vakantiemodus.
     */
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

    public function typeLabel(): string
    {
        return __('host.types.' . $this->type->value);
    }

    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(RoomPhoto::class)->orderBy('sort_order');
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

    /**
     * Vakantiemodus actief? Loopt automatisch af op de einddatum.
     */
    public function isOnVacation(): bool
    {
        return $this->on_vacation
            && ($this->vacation_until === null || $this->vacation_until->endOfDay()->isFuture());
    }

    /**
     * Status voor weergave: een live ruimte in vakantiemodus toont "vakantie" (scope §2.2).
     */
    public function effectiveStatus(): RoomStatus
    {
        if ($this->status === RoomStatus::Live && $this->isOnVacation()) {
            return RoomStatus::Vakantie;
        }

        return $this->status;
    }

    /**
     * Open op deze datum (en optioneel binnen dit tijdvak)? Kijkt naar het
     * weekschema, uitzonderingen en blokkades (scope §2.4). Verwacht dat de
     * relaties hours en exceptions geladen zijn.
     */
    public function isAvailableOn(CarbonInterface $date, ?int $startHour = null, ?int $endHour = null): bool
    {
        $dayExceptions = $this->exceptions->filter(fn ($exception) => $exception->date->isSameDay($date));

        if ($dayExceptions->contains(fn ($exception) => $exception->type === ExceptionType::Closed)) {
            return false;
        }

        // Open vensters: het weekschema plus eventuele extra-open-uitzonderingen.
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

    /**
     * De vrije uren op een datum: open vensters minus blokkades, actieve
     * boekingen en (voor vandaag) verstreken uren. Verwacht geladen relaties.
     *
     * @param  \Illuminate\Support\Collection<int, Booking>|null  $dayBookings
     * @return array<int, int>
     */
    public function freeHoursOn(CarbonInterface $date, $dayBookings = null): array
    {
        $dayExceptions = $this->exceptions->filter(fn ($exception) => $exception->date->isSameDay($date));

        if ($dayExceptions->contains(fn ($exception) => $exception->type === ExceptionType::Closed)) {
            return [];
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

    /**
     * Vrije uren per dag voor de boekingskalender, vanaf vandaag.
     *
     * @return array<string, array<int, int>>
     */
    public function freeHoursByDate(int $days = 84): array
    {
        $this->loadMissing(['hours', 'exceptions']);

        $from = today();

        $bookings = $this->bookings()
            ->active()
            ->whereBetween('date', [$from->toDateString(), $from->copy()->addDays($days)->toDateString()])
            ->get()
            ->groupBy(fn (Booking $booking) => $booking->date->toDateString());

        $result = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $from->copy()->addDays($i);
            $result[$date->toDateString()] = $this->freeHoursOn($date, $bookings->get($date->toDateString()));
        }

        return $result;
    }

    /**
     * Is dit tijdvak echt boekbaar? Schema en uitzonderingen (§2.4) plus
     * bestaande actieve boekingen: geboekte slots zijn niet meer beschikbaar.
     */
    public function isBookableFor(CarbonInterface $date, int $startHour, int $endHour): bool
    {
        if (! $this->isAvailableOn($date, $startHour, $endHour)) {
            return false;
        }

        return ! $this->bookings()
            ->active()
            ->whereDate('date', $date)
            ->where('start_hour', '<', $endHour)
            ->where('end_hour', '>', $startHour)
            ->exists();
    }

    /**
     * Zet een standaard weekschema klaar: ma t/m vr open van 09:00-21:00.
     */
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
     * Uurtarief in euro's voor weergave en formulieren.
     */
    public function hourlyRateEuros(): float
    {
        return $this->hourly_rate_cents / 100;
    }

}
