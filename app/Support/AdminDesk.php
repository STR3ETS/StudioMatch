<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Enums\RoomStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Post;
use App\Models\Room;
use App\Models\Studio;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Het werk van de beheerder: het overzicht, de tickets (problemen die artiesten melden, plus
 * probleemmeldingen van verhuurders), de wachtrij, boekingen, gebruikers, omzet en blog. Gedeeld door de
 * website (Admin\*Controller) en de app (Api\App\AdminController), zodat beide hetzelfde laten zien.
 */
class AdminDesk
{
    /**
     * Ruimtes die op beoordeling wachten, de oudste eerst.
     *
     * @return Collection<int, Room>
     */
    public static function queue(): Collection
    {
        return Room::where('status', RoomStatus::InReview)
            ->with(['studio.user', 'photos'])
            ->oldest('updated_at')
            ->get();
    }

    public static function queueCount(): int
    {
        return Room::where('status', RoomStatus::InReview)->count();
    }

    /**
     * Alle boekingen, de nieuwste eerst, eventueel op één status.
     *
     * @return Collection<int, Booking>
     */
    public static function bookings(?string $status = null): Collection
    {
        return Booking::query()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->with(['room.studio', 'user'])
            ->latest()
            ->get();
    }

    /**
     * Artiesten en verhuurders (geen beheerders), de nieuwste eerst.
     *
     * @return Collection<int, User>
     */
    public static function users(?string $role = null): Collection
    {
        return User::query()
            ->where('role', '!=', UserRole::Admin)
            ->when($role, fn ($query) => $query->where('role', $role))
            ->withCount(['bookings', 'studios'])
            ->latest()
            ->get();
    }

    /**
     * Omzet per studio (bevestigd, afgerond of in geschil): huur naar de verhuurder, servicekosten en btw
     * voor het platform.
     *
     * @return array{studios: \Illuminate\Support\Collection<int, array{studio: Studio, count: int, rent: int, fees: int}>, totalRent: int, totalFees: int, totalCount: int}
     */
    public static function revenue(): array
    {
        $studios = Booking::whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed, BookingStatus::Disputed])
            ->with('room.studio.user')
            ->get()
            ->groupBy(fn (Booking $booking) => $booking->room->studio->id)
            ->map(fn ($group) => [
                'studio' => $group->first()->room->studio,
                'count' => $group->count(),
                'rent' => (int) $group->sum('rent_cents'),
                'fees' => (int) ($group->sum('service_fee_cents') + $group->sum('vat_cents')),
            ])
            ->sortByDesc('rent')
            ->values();

        return [
            'studios' => $studios,
            'totalRent' => (int) $studios->sum('rent'),
            'totalFees' => (int) $studios->sum('fees'),
            'totalCount' => (int) $studios->sum('count'),
        ];
    }

    /**
     * Blogartikelen, het laatst bijgewerkte eerst, eventueel op één status.
     *
     * @return Collection<int, Post>
     */
    public static function posts(?string $status = null): Collection
    {
        return Post::query()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->withCount('sections')
            ->with('author')
            ->latest('updated_at')
            ->get();
    }

    /** @return array<string, int> */
    public static function overview(): array
    {
        return [
            'openDisputes' => Booking::where('status', BookingStatus::Disputed)->count(),
            'openDamages' => self::openDamages()->count(),
            'inReviewCount' => Room::where('status', RoomStatus::InReview)->count(),
            'liveCount' => Room::where('status', RoomStatus::Live)->count(),
            'studioCount' => Studio::count(),
            'artistCount' => User::where('role', UserRole::Artiest)->count(),
            'hostCount' => User::where('role', UserRole::Verhuurder)->count(),
        ];
    }

    /** Wat nog op de beheerder wacht: open geschillen en onbeantwoorde probleemmeldingen. */
    public static function openTicketCount(): int
    {
        return Booking::where('status', BookingStatus::Disputed)->count() + self::openDamages()->count();
    }

    /**
     * Gemelde problemen bij boekingen, de oudste eerst.
     *
     * @return Collection<int, Booking>
     */
    public static function disputes(): Collection
    {
        return Booking::where('status', BookingStatus::Disputed)
            ->with(['room.studio.user', 'user'])
            ->oldest('disputed_at')
            ->get();
    }

    /**
     * Probleemmeldingen van verhuurders: de open meldingen eerst, dan de laatst afgehandelde.
     *
     * @return Collection<int, Booking>
     */
    public static function damages(int $limit = 20): Collection
    {
        return Booking::whereNotNull('damage_reported_at')
            ->with(['room.studio.user', 'user'])
            ->orderByRaw('damage_resolved_at is null desc')
            ->latest('damage_reported_at')
            ->take($limit)
            ->get();
    }

    /** @return Builder<Booking> */
    private static function openDamages()
    {
        return Booking::whereNotNull('damage_reported_at')->whereNull('damage_resolved_at');
    }
}
