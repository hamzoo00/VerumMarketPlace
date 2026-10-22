<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One line of an order.
 *
 * The listing is referenced but **not relied upon**: title and price are
 * copied here at the moment of sale, so a seller editing or deleting a
 * listing cannot rewrite what someone already bought. `listing_id` is
 * nullable for exactly that reason — the line outlives the listing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained()->nullOnDelete();

            // Copied, never read back through the listing.
            $table->string('title');
            $table->string('slug')->nullable();
            $table->unsignedBigInteger('unit_price_cents');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('line_total_cents');

            $table->timestamps();

            $table->index(['order_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
