<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Notifications\BookingReceived;
use App\Notifications\BookingRequested;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'room_id', 'user_id', 'date', 'start_hour', 'end_hour', 'with_engineer',
    'hourly_rate_cents', 'rent_cents', 'service_fee_cents', 'vat_cents', 'total_cents',
    'status', 'expires_at', 'terms_accepted_at', 'requested_at', 'confirmed_at', 'cancelled_by',
    'rescheduled_at', 'disputed_at', 'dispute_reason', 'dispute_studio_response', 'dispute_photos', 'resolution_note', 'reminder_sent_at',
    'response_reminder_sent_at', 'damage_reported_at', 'damage_reason', 'damage_photos',
    'damage_response', 'damage_resolved_at',
    'stripe_checkout_session_id', 'stripe_payment_intent_id', 'stripe_refund_id', 'refunded_cents',
    'stripe_transfer_id', 'transferred_at',
])]
class Booking extends Model
{

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'with_engineer' => 'boolean',
            'status' => BookingStatus::class,
            'expires_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'requested_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'rescheduled_at' => 'datetime',
            'disputed_at' => 'datetime',
            'dispute_photos' => 'array',
            'reminder_sent_at' => 'datetime',
            'response_reminder_sent_at' => 'datetime',
            'damage_reported_at' => 'datetime',
            'damage_photos' => 'array',
            'damage_resolved_at' => 'datetime',
            'transferred_at' => 'datetime',
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

    public function canReschedule(): bool
    {
        return in_array($this->status, [BookingStatus::PendingConfirmation, BookingStatus::Confirmed], true)
            && $this->rescheduled_at === null
            && now()->addHours(48)->isBefore($this->startsAt());
    }

    public function canReportProblem(): bool
    {
        return in_array($this->status, [BookingStatus::Confirmed, BookingStatus::Completed], true)
            && $this->disputed_at === null
            && now()->between($this->startsAt(), $this->startsAt()->addHours(24));
    }

    public function disputeOutcome(): ?string
    {
        if ($this->disputed_at === null) {
            return null;
        }

        if ($this->status === BookingStatus::Cancelled && $this->cancelled_by === 'admin') {
            return 'upheld';
        }

        if ($this->status === BookingStatus::Completed) {
            return ($this->refunded_cents ?? 0) > 0 ? 'partial' : 'dismissed';
        }

        return null;
    }

    public function canReportDamage(): bool
    {
        return in_array($this->status, [BookingStatus::Confirmed, BookingStatus::Completed, BookingStatus::Disputed], true)
            && $this->damage_reported_at === null
            && $this->endsAt()->isPast()
            && $this->endsAt()->addDays(14)->isFuture();
    }

    public function wasPaid(): bool
    {
        return ! in_array($this->status, [BookingStatus::PendingPayment, BookingStatus::Expired], true);
    }

    public function markAsPaid(): bool
    {
        $marked = DB::transaction(function () {
            $booking = self::whereKey($this->id)->lockForUpdate()->first();

            if ($booking->status === BookingStatus::Expired) {
                $taken = $booking->room->bookings()
                    ->active()
                    ->whereKeyNot($booking->id)
                    ->whereDate('date', $booking->date)
                    ->where('start_hour', '<', $booking->end_hour)
                    ->where('end_hour', '>', $booking->start_hour)
                    ->exists();

                if ($taken) {
                    return false;
                }
            } elseif ($booking->status !== BookingStatus::PendingPayment) {
                return false;
            }

            $booking->update(['status' => BookingStatus::PendingConfirmation, 'requested_at' => now()]);

            return true;
        });

        if ($marked) {
            $this->refresh();
            $this->room->studio->user->notify(new BookingRequested($this));
            $this->user->notify(new BookingReceived($this));
        }

        return $marked;
    }

    public function refundAmountCents(int $percent): int
    {
        if ($percent <= 0) {
            return 0;
        }

        return (int) round($this->rent_cents * $percent / 100) + $this->service_fee_cents + $this->vat_cents;
    }

    public function hostPayoutCents(): int
    {
        $refundedFromRent = max(0, ($this->refunded_cents ?? 0) - $this->service_fee_cents - $this->vat_cents);

        return max(0, $this->rent_cents - $refundedFromRent);
    }

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
