<?php

namespace App\Enums;

enum BookingStatus: string
{

    case PendingPayment = 'pending_payment';
    case PendingConfirmation = 'pending_confirmation';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Declined = 'declined';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case Disputed = 'disputed';

    public function badgeClasses(): string
    {
        return match ($this) {
            self::PendingPayment => 'bg-prussian-blue/5 text-prussian-blue/60',
            self::PendingConfirmation => 'bg-amber-500/10 text-amber-600',
            self::Confirmed => 'bg-emerald-500/10 text-emerald-600',
            self::Completed => 'bg-prussian-blue/10 text-prussian-blue',
            self::Declined, self::Cancelled, self::Expired, self::Disputed => 'bg-ruby-red/10 text-ruby-red',
        };
    }
}
