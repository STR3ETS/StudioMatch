<?php

namespace App\Actions;

use App\Enums\RoomStatus;
use App\Models\Room;
use App\Notifications\RoomApproved;
use App\Notifications\RoomRejected;
use App\Support\StripeService;

/**
 * Een ruimte uit de goedkeuringswachtrij beoordelen: live zetten of afwijzen met een reden, en de
 * verhuurder mailen. Gedeeld door de website (Admin\QueueController) en de app (Api\App\AdminController).
 */
class ReviewRoom
{
    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public const STRIPE_REQUIRED = 'stripe_required';

    public const NOT_IN_REVIEW = 'not_in_review';

    /** Live zetten kan pas als de verhuurder uitbetalingen kan ontvangen (als Stripe aan staat). */
    public function stripeBlocked(Room $room): bool
    {
        return StripeService::enabled()
            && ! $room->studio->user->hostProfile?->stripe_payouts_enabled;
    }

    public function approve(Room $room): string
    {
        if ($room->status !== RoomStatus::InReview) {
            return self::NOT_IN_REVIEW;
        }

        if ($this->stripeBlocked($room)) {
            return self::STRIPE_REQUIRED;
        }

        $room->update(['status' => RoomStatus::Live, 'rejection_reason' => null]);
        $room->studio->user->notify(new RoomApproved($room));

        return self::APPROVED;
    }

    public function reject(Room $room, string $reason): string
    {
        if ($room->status !== RoomStatus::InReview) {
            return self::NOT_IN_REVIEW;
        }

        $room->update(['status' => RoomStatus::Afgekeurd, 'rejection_reason' => $reason]);
        $room->studio->user->notify(new RoomRejected($room));

        return self::REJECTED;
    }
}
