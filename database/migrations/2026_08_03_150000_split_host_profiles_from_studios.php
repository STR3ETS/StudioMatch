<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('host_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 30);
            $table->string('owner_type', 20)->default('particulier');
            $table->boolean('btw_plichtig')->default(false);
            $table->string('kvk_number', 20)->nullable();
            $table->string('vat_number', 30)->nullable();
            $table->timestamps();
        });

        foreach (DB::table('studios')->get() as $studio) {
            DB::table('host_profiles')->insert([
                'user_id' => $studio->user_id,
                'name' => $studio->name,
                'phone' => $studio->phone,
                'owner_type' => $studio->owner_type,
                'btw_plichtig' => $studio->btw_plichtig,
                'kvk_number' => $studio->kvk_number,
                'vat_number' => $studio->vat_number,
                'created_at' => $studio->created_at,
                'updated_at' => $studio->updated_at,
            ]);
        }

        Schema::table('studios', function (Blueprint $table) {
            $table->index('user_id');
            $table->dropUnique(['user_id']);
            $table->dropColumn(['owner_type', 'btw_plichtig', 'kvk_number', 'vat_number']);
            $table->string('phone', 30)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('studios', function (Blueprint $table) {
            $table->unique('user_id');
            $table->dropIndex(['user_id']);
            $table->string('owner_type', 20)->default('particulier');
            $table->boolean('btw_plichtig')->default(false);
            $table->string('kvk_number', 20)->nullable();
            $table->string('vat_number', 30)->nullable();
            $table->string('phone', 30)->nullable(false)->change();
        });

        Schema::dropIfExists('host_profiles');
    }
};
