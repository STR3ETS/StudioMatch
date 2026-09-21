<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Het deel van de huur dat aan de engineer toebehoort, zodat het los op de
            // factuur kan staan.
            $table->unsignedInteger('engineer_cents')->default(0)->after('rent_cents');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('engineer_cents');
        });
    }
};
