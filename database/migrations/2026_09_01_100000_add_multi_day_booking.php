<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            // Zonder dagtarief kan een ruimte alleen per uur geboekt worden.
            $table->unsignedInteger('day_rate_cents')->nullable()->after('hourly_rate_cents');
            $table->unsignedTinyInteger('min_days')->default(2)->after('min_hours');
        });

        Schema::table('bookings', function (Blueprint $table) {
            // Gevuld bij een meerdaagse boeking: hele dagen van date tot en met end_date.
            $table->date('end_date')->nullable()->after('date');
            $table->unsignedInteger('day_rate_cents')->nullable()->after('hourly_rate_cents');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['day_rate_cents', 'min_days']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['end_date', 'day_rate_cents']);
        });
    }
};
