<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->text('damage_response')->nullable()->after('damage_photos');
            $table->timestamp('damage_resolved_at')->nullable()->after('damage_response');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['damage_response', 'damage_resolved_at']);
        });
    }
};
