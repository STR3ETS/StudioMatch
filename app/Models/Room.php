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
    'engineer_included', 'engineer_rate_cents', 'house_rules', 'equipment', 'equipment_extra', 'daws', 'facilities', 'status',
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

    public function freeHoursOn(CarbonInterface $date, $dayBookings = null, $previousDayBookings = null): array
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

        $bookings = $this->bookings()
            ->active()
            ->whereBetween('date', [$from->copy()->subDay()->toDateString(), $from->copy()->addDays($days)->toDateString()])
            ->get()
            ->groupBy(fn (Booking $booking) => $booking->date->toDateString());

        $result = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $from->copy()->addDays($i);
            $result[$date->toDateString()] = $this->freeHoursOn(
                $date,
                $bookings->get($date->toDateString()),
                $bookings->get($date->copy()->subDay()->toDateString()),
            );
        }

        return $result;
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

    public function hourlyRateEuros(): float
    {
        return $this->hourly_rate_cents / 100;
    }

}
