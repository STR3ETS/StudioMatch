<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {

        Schema::create('room_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->boolean('is_open')->default(false);
            $table->unsignedTinyInteger('open_hour')->default(9);
            $table->unsignedTinyInteger('close_hour')->default(21);
            $table->timestamps();

            $table->unique(['room_id', 'weekday']);
        });

        Schema::create('room_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('type', 10);
            $table->unsignedTinyInteger('start_hour')->nullable();
            $table->unsignedTinyInteger('end_hour')->nullable();
            $table->string('label', 100)->nullable();
            $table->timestamps();

            $table->index(['room_id', 'date']);
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->boolean('on_vacation')->default(false)->after('status');
            $table->date('vacation_until')->nullable()->after('on_vacation');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['on_vacation', 'vacation_until']);
        });
        Schema::dropIfExists('room_exceptions');
        Schema::dropIfExists('room_hours');
    }
};
