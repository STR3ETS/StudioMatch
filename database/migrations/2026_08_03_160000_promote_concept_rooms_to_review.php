<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{

    public function up(): void
    {
        DB::table('rooms')->where('status', 'concept')->update(['status' => 'in_review']);
    }

    public function down(): void
    {

    }
};
