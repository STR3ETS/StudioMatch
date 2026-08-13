<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('requested_at')->nullable()->after('terms_accepted_at');
            $table->timestamp('rescheduled_at')->nullable()->after('confirmed_at');
            $table->timestamp('disputed_at')->nullable()->after('rescheduled_at');
            $table->text('dispute_reason')->nullable()->after('disputed_at');
            $table->timestamp('reminder_sent_at')->nullable()->after('dispute_reason');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['requested_at', 'rescheduled_at', 'disputed_at', 'dispute_reason', 'reminder_sent_at']);
        });
    }
};
