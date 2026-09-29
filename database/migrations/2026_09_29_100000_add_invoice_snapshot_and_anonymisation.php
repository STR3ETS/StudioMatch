<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Een factuur moet zeven jaar bewaard blijven, maar een account moet verwijderd kunnen
 * worden. Dat kan alleen als de factuurgegevens op de boeking zelf staan in plaats van
 * dat ze live uit het account worden gelezen. Deze migratie legt die momentopname vast
 * en vult hem voor bestaande boekingen alsnog in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('buyer_name')->nullable()->after('buyer_vat_number');
            $table->string('buyer_email')->nullable()->after('buyer_name');
            $table->string('buyer_street')->nullable()->after('buyer_email');
            $table->string('buyer_postal_code')->nullable()->after('buyer_street');
            $table->string('buyer_city')->nullable()->after('buyer_postal_code');

            $table->string('seller_name')->nullable()->after('buyer_city');
            $table->string('seller_address')->nullable()->after('seller_name');
            $table->string('seller_kvk_number')->nullable()->after('seller_address');
            $table->string('seller_vat_number')->nullable()->after('seller_kvk_number');
            $table->boolean('seller_vat_liable')->nullable()->after('seller_vat_number');

            // De naam van de ruimte staat op de factuurregel en mag niet verdwijnen
            // als de verhuurder zijn ruimte later hernoemt of weghaalt.
            $table->string('room_label')->nullable()->after('seller_vat_liable');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('anonymised_at')->nullable()->after('locale');
        });

        $this->backfill();
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'buyer_name', 'buyer_email', 'buyer_street', 'buyer_postal_code', 'buyer_city',
                'seller_name', 'seller_address', 'seller_kvk_number', 'seller_vat_number', 'seller_vat_liable', 'room_label',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('anonymised_at');
        });
    }

    /** Vult de momentopname voor boekingen die er al waren. */
    private function backfill(): void
    {
        DB::table('bookings')->orderBy('id')->chunkById(200, function ($bookings) {
            foreach ($bookings as $booking) {
                $buyer = DB::table('users')->where('id', $booking->user_id)->first();

                $room = DB::table('rooms')->where('id', $booking->room_id)->first();
                $studio = $room ? DB::table('studios')->where('id', $room->studio_id)->first() : null;
                $host = $studio ? DB::table('users')->where('id', $studio->user_id)->first() : null;
                $profile = $host ? DB::table('host_profiles')->where('user_id', $host->id)->first() : null;

                $address = $studio
                    ? trim(($studio->street ?? '') . ', ' . trim(($studio->postal_code ?? '') . ' ' . ($studio->city ?? '')), ' ,')
                    : null;

                DB::table('bookings')->where('id', $booking->id)->update([
                    'buyer_name' => $buyer->name ?? null,
                    'buyer_email' => $buyer->email ?? null,
                    'buyer_street' => $buyer->street ?? null,
                    'buyer_postal_code' => $buyer->postal_code ?? null,
                    'buyer_city' => $buyer->city ?? null,
                    'seller_name' => $profile->name ?? $host->name ?? null,
                    'seller_address' => $address ?: null,
                    'seller_kvk_number' => $profile->kvk_number ?? null,
                    'seller_vat_number' => $profile->vat_number ?? null,
                    'seller_vat_liable' => $profile->btw_plichtig ?? null,
                    'room_label' => $studio && $room ? $studio->name . ' - ' . $room->title : null,
                ]);
            }
        });
    }
};
