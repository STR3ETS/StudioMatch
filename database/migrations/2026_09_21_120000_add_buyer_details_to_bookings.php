<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Boekt de huurder als particulier of zakelijk? Dat bepaalt de gegevens op de
            // factuur en of de consumentenbescherming van toepassing is.
            $table->string('buyer_type', 20)->default('particulier')->after('user_id');
            $table->string('buyer_company')->nullable()->after('buyer_type');
            $table->string('buyer_vat_number', 30)->nullable()->after('buyer_company');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['buyer_type', 'buyer_company', 'buyer_vat_number']);
        });
    }
};
