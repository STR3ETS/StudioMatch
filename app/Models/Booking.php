<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'room_id', 'user_id', 'date', 'start_hour', 'end_hour',
    'hourly_rate_cents', 'rent_cents', 'service_fee_cents', 'vat_cents', 'total_cents',
    'status', 'expires_at', 'terms_accepted_at', 'requested_at', 'confirmed_at', 'cancelled_by',
    'rescheduled_at', 'disputed_at', 'dispute_reason', 'dispute_photos', 'reminder_sent_at',
    'damage_reported_at', 'damage_reason', 'damage_photos',
])]
class Booking extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'status' => BookingStatus::class,
            'expires_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'requested_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'rescheduled_at' => 'datetime',
            'disputed_at' => 'datetime',
            'dispute_photos' => 'array',
            'reminder_sent_at' => 'datetime',
            'damage_reported_at' => 'datetime',
            'damage_photos' => 'array',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Boekingen die een slot bezet houden: bevestigd, wacht op acceptatie,
     * of nog binnen de 15-minutenblokkade van de checkout.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->whereIn('status', [BookingStatus::PendingConfirmation, BookingStatus::Confirmed])
                ->orWhere(function (Builder $query) {
                    $query->where('status', BookingStatus::PendingPayment)->where('expires_at', '>', now());
                });
        });
    }

    public function isActive(): bool
    {
        return in_array($this->status, [BookingStatus::PendingConfirmation, BookingStatus::Confirmed], true)
            || ($this->status === BookingStatus::PendingPayment && $this->expires_at?->isFuture());
    }

    public function overlaps(int $startHour, int $endHour): bool
    {
        return $this->start_hour < $endHour && $this->end_hour > $startHour;
    }

    public function startsAt(): Carbon
    {
        return $this->date->copy()->startOfDay()->addHours((int) $this->start_hour);
    }

    public function endsAt(): Carbon
    {
        return $this->date->copy()->startOfDay()->addHours((int) $this->end_hour);
    }

    /**
     * Status voor weergave: een bevestigde sessie in het verleden geldt als voltooid,
     * een verlopen betaalblokkade als verlopen.
     */
    public function effectiveStatus(): BookingStatus
    {
        if ($this->status === BookingStatus::Confirmed && $this->endsAt()->isPast()) {
            return BookingStatus::Completed;
        }

        if ($this->status === BookingStatus::PendingPayment && $this->expires_at?->isPast()) {
            return BookingStatus::Expired;
        }

        return $this->status;
    }

    public function hours(): int
    {
        return (int) $this->end_hour - (int) $this->start_hour;
    }

    public function timeRange(): string
    {
        return sprintf('%02d:00', $this->start_hour) . ' – ' . ($this->end_hour == 24 ? '24:00' : sprintf('%02d:00', $this->end_hour));
    }

    /**
     * Verzetten mag eenmalig, tot 48 uur vóór de start (BESLISSING 9).
     */
    public function canReschedule(): bool
    {
        return in_array($this->status, [BookingStatus::PendingConfirmation, BookingStatus::Confirmed], true)
            && $this->rescheduled_at === null
            && now()->addHours(48)->isBefore($this->startsAt());
    }

    /**
     * Probleem melden kan vanaf de starttijd tot 24 uur erna (scope §2.5),
     * en per boeking maar één keer.
     */
    public function canReportProblem(): bool
    {
        return in_array($this->status, [BookingStatus::Confirmed, BookingStatus::Completed], true)
            && $this->disputed_at === null
            && now()->between($this->startsAt(), $this->startsAt()->addHours(24));
    }

    /**
     * Is een gemeld probleem afgewezen? (Uitbetaling vrijgegeven door de admin.)
     */
    public function wasDisputeDismissed(): bool
    {
        return $this->disputed_at !== null && $this->status === BookingStatus::Completed;
    }

    /**
     * Is een gemeld probleem toegekend? (Geannuleerd door de admin + terugbetaling.)
     */
    public function wasDisputeUpheld(): bool
    {
        return $this->disputed_at !== null
            && $this->status === BookingStatus::Cancelled
            && $this->cancelled_by === 'admin';
    }

    /**
     * Schade melden kan na afloop van de sessie, tot 14 dagen erna, en
     * per boeking één keer (scope §2.8).
     */
    public function canReportDamage(): bool
    {
        return in_array($this->status, [BookingStatus::Confirmed, BookingStatus::Completed, BookingStatus::Disputed], true)
            && $this->damage_reported_at === null
            && $this->endsAt()->isPast()
            && $this->endsAt()->addDays(14)->isFuture();
    }

    /**
     * Restitutiepercentage van de huur volgens de staffel (scope §2.8).
     */
    public function refundPercentForCancellationNow(): int
    {
        $hoursUntilStart = (int) floor(now()->diffInMinutes($this->startsAt(), false) / 60);

        return match (true) {
            $hoursUntilStart >= 48 => 100,
            $hoursUntilStart >= 24 => 50,
            default => 0,
        };
    }
}
