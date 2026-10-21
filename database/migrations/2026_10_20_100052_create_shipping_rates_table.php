<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A seller's shipping rates.
 *
 * One row is "this service, to this zone, for this price, arriving in this
 * window". A seller offers a handful; a buyer picks one at checkout.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();

            $table->string('service', 32);
            $table->string('destination', 32);

            // Integer minor units, like every other price in the schema.
            $table->unsignedInteger('price_cents')->default(0);
            $table->unsignedInteger('extra_item_cents')->nullable();

            // The quoted window, in days, once the parcel is handed over.
            $table->unsignedSmallInteger('transit_min_days');
            $table->unsignedSmallInteger('transit_max_days');

            $table->boolean('tracked')->default(true);
            $table->boolean('insured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('note')->nullable();

            $table->timestamps();

            // One price per service and zone: two rows for "Express,
            // domestic" would make the buyer's choice ambiguous.
            $table->unique(['seller_id', 'service', 'destination']);
            $table->index(['seller_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};
