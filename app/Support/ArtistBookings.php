<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * De boekingen van een artiest, verdeeld in komend en eerder. Gedeeld door het artiestendashboard op
 * de website (Artist\OverviewController) en "Mijn boekingen" in de app (Api\App\ArtistBookingController).
 */
class ArtistBookings
{
    /**
     * @return array{upcoming: Collection<int, Booking>, past: Collection<int, Booking>}
     */
    public static function split(User $artist): array
    {
        $bookings = $artist->bookings()
            ->with(['room.studio', 'room.photos'])
            ->orderBy('date')
            ->orderBy('start_hour')
            ->get();

        [$upcoming, $past] = $bookings->partition(
            fn (Booking $booking) => $booking->endsAt()->isFuture() && $booking->isActive(),
        );

        $statusPriority = fn (Booking $booking) => match ($booking->effectiveStatus()) {
            BookingStatus::Completed, BookingStatus::PendingPayment => 0,
            BookingStatus::PendingConfirmation => 1,
            default => 2,
        };

        return [
            'upcoming' => $upcoming->values(),
            'past' => $past
                ->sortByDesc(fn (Booking $booking) => $booking->startsAt())
                ->sortBy($statusPriority)
                ->values(),
        ];
    }
}
