<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Boekingen met de statusmachine uit scope §2.5. Bedragen worden vastgelegd
     * in centen op het moment van boeken, zodat latere prijswijzigingen geen
     * invloed hebben op bestaande boekingen.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedTinyInteger('start_hour'); // slotraster van 1 uur
            $table->unsignedTinyInteger('end_hour');
            $table->unsignedInteger('hourly_rate_cents');
            $table->unsignedInteger('rent_cents');
            $table->unsignedInteger('service_fee_cents');
            $table->unsignedInteger('vat_cents');
            $table->unsignedInteger('total_cents');
            $table->string('status', 30)->default('pending_payment')->index();
            $table->timestamp('expires_at')->nullable();       // 15-minutenblokkade tijdens checkout
            $table->timestamp('terms_accepted_at');            // akkoord huisregels + AV, gelogd (scope §2.5)
            $table->timestamp('confirmed_at')->nullable();
            $table->string('cancelled_by', 10)->nullable();    // artist / host / auto
            $table->timestamps();

            $table->index(['room_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
