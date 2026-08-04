<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Schademelding door de verhuurder (scope §2.8): melding + bewijs
            // via het dashboard, afhandeling buiten het platform.
            $table->timestamp('damage_reported_at')->nullable()->after('reminder_sent_at');
            $table->text('damage_reason')->nullable()->after('damage_reported_at');
            $table->json('damage_photos')->nullable()->after('damage_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['damage_reported_at', 'damage_reason', 'damage_photos']);
        });
    }
};
