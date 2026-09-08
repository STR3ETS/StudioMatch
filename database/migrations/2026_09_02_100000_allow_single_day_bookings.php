<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->unsignedTinyInteger('min_days')->default(1)->change();
        });

        // Het minimum stond bij invoering op twee dagen, maar een enkele dag mag ook.
        DB::table('rooms')->where('min_days', 2)->update(['min_days' => 1]);
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->unsignedTinyInteger('min_days')->default(2)->change();
        });
    }
};
