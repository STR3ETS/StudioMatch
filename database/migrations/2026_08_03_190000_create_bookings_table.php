<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedTinyInteger('start_hour');
            $table->unsignedTinyInteger('end_hour');
            $table->unsignedInteger('hourly_rate_cents');
            $table->unsignedInteger('rent_cents');
            $table->unsignedInteger('service_fee_cents');
            $table->unsignedInteger('vat_cents');
            $table->unsignedInteger('total_cents');
            $table->string('status', 30)->default('pending_payment')->index();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('terms_accepted_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->string('cancelled_by', 10)->nullable();
            $table->timestamps();

            $table->index(['room_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
