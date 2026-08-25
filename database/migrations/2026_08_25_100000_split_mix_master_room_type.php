<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('rooms')->where('type', 'mix_master')->update(['type' => 'mix']);
    }

    public function down(): void
    {
        DB::table('rooms')->whereIn('type', ['mix', 'master'])->update(['type' => 'mix_master']);
    }
};
