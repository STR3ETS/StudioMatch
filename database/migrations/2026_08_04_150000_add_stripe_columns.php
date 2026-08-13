<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('host_profiles', function (Blueprint $table) {
            $table->string('stripe_account_id')->nullable()->after('vat_number');
            $table->boolean('stripe_details_submitted')->default(false)->after('stripe_account_id');
            $table->boolean('stripe_payouts_enabled')->default(false)->after('stripe_details_submitted');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('stripe_checkout_session_id')->nullable()->after('damage_photos');
            $table->string('stripe_payment_intent_id')->nullable()->after('stripe_checkout_session_id');
            $table->string('stripe_refund_id')->nullable()->after('stripe_payment_intent_id');
            $table->unsignedInteger('refunded_cents')->nullable()->after('stripe_refund_id');
            $table->string('stripe_transfer_id')->nullable()->after('refunded_cents');
            $table->timestamp('transferred_at')->nullable()->after('stripe_transfer_id');
        });
    }

    public function down(): void
    {
        Schema::table('host_profiles', function (Blueprint $table) {
            $table->dropColumn(['stripe_account_id', 'stripe_details_submitted', 'stripe_payouts_enabled']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['stripe_checkout_session_id', 'stripe_payment_intent_id', 'stripe_refund_id', 'refunded_cents', 'stripe_transfer_id', 'transferred_at']);
        });
    }
};
