<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Ruimtes gaan voortaan direct in review bij het aanmaken; de losse
     * concept-stap vervalt. Bestaande concepten schuiven mee.
     */
    public function up(): void
    {
        DB::table('rooms')->where('status', 'concept')->update(['status' => 'in_review']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Niet omkeerbaar: we weten niet welke ruimtes concept waren.
    }
};
