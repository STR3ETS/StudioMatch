<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Coördinaten per studio, gevuld via de PDOK Locatieserver (gratis
     * NL-geocoding) zodra het adres wordt opgeslagen (scope: adres + kaartje).
     */
    public function up(): void
    {
        Schema::table('studios', function (Blueprint $table) {
            $table->decimal('lat', 10, 7)->nullable()->after('city');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('studios', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lng']);
        });
    }
};
