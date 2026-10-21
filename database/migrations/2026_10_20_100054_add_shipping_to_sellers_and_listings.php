<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Seller-level shipping settings, and the per-listing override.
 *
 * Marketplaces in this trade work the same way: a seller configures
 * postage once, and a listing departs from it only when it needs to —
 * a heavy parcel, or free postage as a promotion.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            // Working days between payment and handover to the carrier.
            $table->unsignedSmallInteger('handling_days')
                ->default(config('verum_shipping.handling.default_days'))
                ->after('ships');

            // Free postage once the order reaches this much. Null = never.
            $table->unsignedInteger('free_shipping_over_cents')->nullable()->after('handling_days');

            $table->text('shipping_note')->nullable()->after('free_shipping_over_cents');
        });

        Schema::table('listings', function (Blueprint $table) {
            // How this listing is posted:
            //   inherit — use the seller's rates (the default)
            //   free    — the seller absorbs postage
            //   custom  — one flat price, overriding every rate
            $table->string('shipping_mode', 16)->default('inherit')->after('quantity');
            $table->unsignedInteger('shipping_price_cents')->nullable()->after('shipping_mode');
        });
    }

    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->dropColumn(['handling_days', 'free_shipping_over_cents', 'shipping_note']);
        });

        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn(['shipping_mode', 'shipping_price_cents']);
        });
    }
};
