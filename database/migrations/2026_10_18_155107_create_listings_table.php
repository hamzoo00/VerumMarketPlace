<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Seller-created listings.
 *
 * Separate from `stones`, which is the seeded demo catalogue. A listing is
 * owned by a seller, lives in the category tree, and is sold either at a fixed
 * price or at auction — never both.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();

            $table->string('slug')->unique();
            $table->string('title');
            $table->string('sku')->nullable();
            $table->text('description')->nullable();

            // 'fixed' or 'auction'. Which price columns apply follows from this.
            $table->string('listing_type')->index();
            $table->string('status')->default('draft')->index();

            // Money as integer minor units; never floats.
            $table->unsignedInteger('price_cents')->nullable();
            $table->unsignedInteger('start_price_cents')->nullable();
            $table->unsignedInteger('reserve_price_cents')->nullable();
            $table->unsignedInteger('bid_increment_cents')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('quantity')->default(1);

            // Absolute end time, so a countdown survives a refresh.
            $table->timestamp('auction_ends_at')->nullable();

            // Gem attributes. Nullable because jewellery listings skip most.
            $table->decimal('carat', 8, 2)->nullable();
            $table->string('dimensions')->nullable();
            $table->string('shape')->nullable();
            $table->string('cut')->nullable();
            $table->string('color')->nullable();
            $table->string('clarity')->nullable();
            $table->string('treatment')->nullable();
            $table->boolean('untreated')->default(false);
            $table->string('origin')->nullable();
            $table->string('origin_country', 2)->nullable();

            // Certification.
            $table->string('lab')->nullable();
            $table->string('report_number')->nullable();
            $table->date('report_date')->nullable();

            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['seller_id', 'status']);
            $table->index(['category_id', 'status']);
            $table->index('auction_ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
