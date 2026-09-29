<?php

namespace App\Support;

use App\Enums\RoomStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Wist een account zonder de administratie te slopen.
 *
 * Alles verwijderen kan niet: facturen moeten fiscaal zeven jaar bewaard blijven en die
 * horen de naam en het adres van koper en verkoper te bevatten. Daarom leggen we die
 * gegevens eerst vast op de boeking zelf en halen we ze daarna overal anders weg. Wat
 * overblijft is een lege huls van een account waar niemand meer op in kan loggen, en
 * boekingen en facturen die blijven kloppen.
 */
class AccountAnonymiser
{
    public static function run(User $user): void
    {
        DB::transaction(function () use ($user) {
            self::captureInvoices($user);
            self::takeRoomsOffline($user);
            self::wipeHostProfile($user);
            self::wipeStudios($user);
            self::wipeUser($user);
        });
    }

    /** Boekingen waar deze persoon koper of verhuurder bij was hun eigen kopie geven. */
    private static function captureInvoices(User $user): void
    {
        Booking::query()
            ->where('user_id', $user->id)
            ->orWhereHas('room.studio', fn ($query) => $query->where('user_id', $user->id))
            ->with(['user', 'room.studio.user.hostProfile'])
            ->chunkById(100, fn ($bookings) => $bookings->each->captureInvoiceDetails());
    }

    /** Ruimtes van de site af en de foto's echt weg, inclusief de bestanden. */
    private static function takeRoomsOffline(User $user): void
    {
        $user->rooms()->with('photos')->get()->each(function ($room) {
            $room->photos->each->delete();
            $room->update(['status' => RoomStatus::Concept, 'on_vacation' => true]);
        });
    }

    private static function wipeHostProfile(User $user): void
    {
        $user->hostProfile?->forceFill([
            'name' => __('account.delete.removed'),
            'phone' => '-',
            'kvk_number' => null,
            'vat_number' => null,
            'stripe_account_id' => null,
            'stripe_details_submitted' => false,
            'stripe_payouts_enabled' => false,
        ])->save();
    }

    /**
     * Het studio-adres is bij een particuliere verhuurder gewoon een woonadres, dus dat
     * gaat er ook uit. De factuur heeft zijn eigen kopie en heeft dit niet meer nodig.
     */
    private static function wipeStudios(User $user): void
    {
        $user->studios()->get()->each(fn ($studio) => $studio->forceFill([
            'name' => __('account.delete.removed'),
            'phone' => null,
            'street' => '-',
            'postal_code' => '-',
            'city' => '-',
            'lat' => null,
            'lng' => null,
        ])->save());
    }

    private static function wipeUser(User $user): void
    {
        $user->forceFill([
            'name' => __('account.delete.removed'),
            // Een onbestaanbaar domein, zodat het adres uniek blijft en nooit post krijgt.
            'email' => 'verwijderd-' . $user->id . '@account.invalid',
            'email_verified_at' => null,
            'password' => Hash::make(Str::random(64)),
            'remember_token' => null,
            'street' => null,
            'postal_code' => null,
            'city' => null,
            'anonymised_at' => now(),
        ])->save();
    }
}
