<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Seller-issued discount codes.
 *
 * Scoped to one seller: a code is redeemed against that seller's items,
 * so two sellers may both run "WELCOME10" without collision.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();

            $table->string('code', 20);
            $table->string('type', 20);

            // Exactly one of these applies, decided by `type`.
            $table->unsignedSmallInteger('percent')->nullable();
            $table->unsignedInteger('amount_cents')->nullable();

            $table->unsignedInteger('min_spend_cents')->nullable();

            // Null means unlimited; `used_count` is the running total.
            $table->unsignedInteger('max_redemptions')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->unsignedSmallInteger('per_buyer_limit')->default(1);

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Codes are matched case-insensitively in the application, so
            // they are stored upper-cased and unique per seller.
            $table->unique(['seller_id', 'code']);
            $table->index(['seller_id', 'is_active']);
        });

        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('discount_cents');
            $table->timestamps();

            $table->index(['coupon_id', 'buyer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_redemptions');
        Schema::dropIfExists('coupons');
    }
};
