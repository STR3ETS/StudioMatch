<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->unsignedInteger('engineer_rate_cents')->nullable()->after('engineer_included');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('with_engineer')->default(false)->after('end_hour');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('engineer_rate_cents');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('with_engineer');
        });
    }
};
