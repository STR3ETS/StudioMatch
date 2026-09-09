<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            // Aparte dagprijs voor de engineer, los van het uurtarief.
            $table->unsignedInteger('engineer_day_rate_cents')->nullable()->after('engineer_rate_cents');
        });

        Schema::table('room_hours', function (Blueprint $table) {
            // Leeg betekent: gebruik het standaard dagtarief van de ruimte.
            $table->unsignedInteger('day_rate_cents')->nullable()->after('close_hour');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('engineer_day_rate_cents');
        });

        Schema::table('room_hours', function (Blueprint $table) {
            $table->dropColumn('day_rate_cents');
        });
    }
};
