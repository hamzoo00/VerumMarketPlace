<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where a storefront is paid, and its premium subscription.
 *
 * On the storefront rather than the login account: payment goes to the
 * business that sells, the same row orders and payments point at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->string('paypal_merchant_id', 32)->nullable();
            $table->timestamp('paypal_connected_at')->nullable();
            // Sent to PayPal when connecting and checked on the way back,
            // so a return link cannot attach someone else's account.
            $table->string('paypal_onboarding_token', 64)->nullable();

            $table->string('premium_subscription_id', 64)->nullable()->index();
            $table->string('premium_subscription_status', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->dropIndex(['premium_subscription_id']);
            $table->dropColumn([
                'paypal_merchant_id',
                'paypal_connected_at',
                'paypal_onboarding_token',
                'premium_subscription_id',
                'premium_subscription_status',
            ]);
        });
    }
};
