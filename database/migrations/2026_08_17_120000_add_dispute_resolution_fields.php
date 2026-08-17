<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->text('dispute_studio_response')->nullable()->after('dispute_reason');
            $table->text('resolution_note')->nullable()->after('dispute_photos');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['dispute_studio_response', 'resolution_note']);
        });
    }
};
