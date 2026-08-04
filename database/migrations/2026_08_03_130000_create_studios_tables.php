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
        // Studioprofiel per verhuurder (scope §2.2). IBAN + identiteit lopen via Stripe.
        Schema::create('studios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 30);
            $table->string('street');
            $table->string('postal_code', 10);
            $table->string('city', 100);
            $table->string('owner_type', 20)->default('particulier');
            $table->boolean('btw_plichtig')->default(false);
            $table->string('kvk_number', 20)->nullable();
            $table->string('vat_number', 30)->nullable();
            $table->timestamps();
        });

        // Ruimtes: apart boekbaar, één studio kan er meerdere hebben (scope §2.2).
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('type', 20); // opname / mix_master (BESLISSING 11)
            $table->unsignedInteger('hourly_rate_cents');
            $table->unsignedTinyInteger('min_hours')->default(2); // BESLISSING 1
            $table->unsignedTinyInteger('capacity');
            $table->boolean('engineer_included')->default(false);
            $table->text('house_rules')->nullable();
            $table->json('equipment')->nullable();
            $table->string('equipment_extra')->nullable(); // vrij veld naast de vaste lijst
            $table->json('daws')->nullable();
            $table->json('facilities')->nullable();
            $table->string('status', 20)->default('concept')->index(); // concept → in_review → live / afgekeurd → vakantie
            $table->timestamps();
        });

        Schema::create('room_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_photos');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('studios');
    }
};
