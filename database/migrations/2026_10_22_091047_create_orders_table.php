<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One sale, from one seller to one buyer.
 *
 * Scoped to a single seller deliberately: postage, dispatch and disputes
 * are all that seller's responsibility, so a basket spanning two sellers
 * becomes two orders. Splitting later would mean deciding retrospectively
 * whose promise a line belonged to.
 *
 * Every money column is integer cents and **copied at the moment of
 * sale**, never read back through the listing. A seller may reprice or
 * delete a listing tomorrow; what the buyer agreed to has to survive
 * that, and a dispute is argued over this row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Quotable by both sides: VRM-2026-000123.
            $table->string('reference', 32)->unique();

            $table->foreignId('buyer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();

            /*
             * How the sale came about. A dispute reads differently for a
             * hammer price than for a negotiated one, and a buyer cannot
             * retract a bid the way they can abandon a basket.
             */
            $table->string('origin', 16)->default('checkout');

            $table->string('status', 24)->default('awaiting_payment');

            // The buyer's own currency at the time, for the record. The
            // charge itself is always in the base currency.
            $table->string('currency', 3)->default('USD');

            $table->unsignedBigInteger('subtotal_cents')->default(0);
            $table->unsignedBigInteger('shipping_cents')->default(0);
            $table->unsignedBigInteger('discount_cents')->default(0);
            $table->unsignedBigInteger('total_cents')->default(0);

            // The coupon as it applied here. Kept as text as well as an
            // id: a seller may delete the campaign afterwards.
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('coupon_code', 64)->nullable();

            // The postage the buyer chose, copied the same way.
            $table->string('shipping_service', 32)->nullable();
            $table->string('shipping_destination', 32)->nullable();
            $table->unsignedSmallInteger('handling_days')->default(0);

            // Delivery address, flattened. A buyer may move house; where
            // this parcel was sent must not move with them.
            $table->string('ship_to_name');
            $table->string('ship_to_line1');
            $table->string('ship_to_line2')->nullable();
            $table->string('ship_to_city');
            $table->string('ship_to_region')->nullable();
            $table->string('ship_to_postcode', 32)->nullable();
            $table->string('ship_to_country', 2);
            $table->string('ship_to_phone', 32)->nullable();

            $table->text('buyer_note')->nullable();

            // Fulfilment, each stamped when it happened rather than
            // inferred from the status, so the history reconstructs.
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_reason')->nullable();

            $table->string('tracking_carrier', 64)->nullable();
            $table->string('tracking_number', 128)->nullable();

            $table->timestamps();

            $table->index(['buyer_id', 'created_at']);
            $table->index(['seller_id', 'status']);
            $table->index(['seller_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
