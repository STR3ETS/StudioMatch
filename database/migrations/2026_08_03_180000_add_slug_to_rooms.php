<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('studio_id');
        });

        $rooms = DB::table('rooms')
            ->join('studios', 'studios.id', '=', 'rooms.studio_id')
            ->select('rooms.id', 'rooms.title', 'studios.name as studio_name')
            ->get();

        foreach ($rooms as $room) {
            DB::table('rooms')->where('id', $room->id)->update([
                'slug' => Str::slug($room->studio_name . ' ' . $room->title) . '-' . $room->id,
            ]);
        }

        Schema::table('rooms', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
