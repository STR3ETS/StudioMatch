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
            // Een ruimte kan onder meerdere noemers bekend staan; 'type' blijft de eerste
            // keuze en wordt gebruikt waar we er maar een kunnen tonen.
            $table->json('types')->nullable()->after('type');
        });

        foreach (DB::table('rooms')->select('id', 'type')->get() as $room) {
            DB::table('rooms')->where('id', $room->id)->update([
                'types' => json_encode([$room->type]),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('types');
        });
    }
};
