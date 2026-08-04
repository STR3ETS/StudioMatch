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
        // Wekelijks schema per ruimte (scope §2.4). Slotraster van 1 uur → uren als integers.
        Schema::create('room_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // 1 = maandag t/m 7 = zondag (ISO)
            $table->boolean('is_open')->default(false);
            $table->unsignedTinyInteger('open_hour')->default(9);   // 0-23
            $table->unsignedTinyInteger('close_hour')->default(21); // 1-24
            $table->timestamps();

            $table->unique(['room_id', 'weekday']);
        });

        // Uitzonderingen (extra open/dicht) en blokkades (eigen sessie/onderhoud) op datum.
        Schema::create('room_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('type', 10); // open / closed / block
            $table->unsignedTinyInteger('start_hour')->nullable();
            $table->unsignedTinyInteger('end_hour')->nullable();
            $table->string('label', 100)->nullable();
            $table->timestamps();

            $table->index(['room_id', 'date']);
        });

        // Vakantiemodus los van de reviewstatus, zodat die omkeerbaar blijft.
        Schema::table('rooms', function (Blueprint $table) {
            $table->boolean('on_vacation')->default(false)->after('status');
            $table->date('vacation_until')->nullable()->after('on_vacation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['on_vacation', 'vacation_until']);
        });
        Schema::dropIfExists('room_exceptions');
        Schema::dropIfExists('room_hours');
    }
};
