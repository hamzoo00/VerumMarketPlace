<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Money that moved, or tried to.
 *
 * One row per attempt: a buyer who abandons PayPal and tries again makes
 * a second row, so a failed attempt is never overwritten by a later
 * success. Premium renewals are rows too, one per month paid.
 *
 * Amounts are copied from what PayPal confirmed, not from the order, so
 * this row is the record of what was actually charged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            // Sent to PayPal as the request id and custom id, so a retried
            // call cannot charge twice and every event finds its row.
            $table->uuid()->unique();
            $table->string('purpose', 20);
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('buyer_id')->nullable()->constrained()->nullOnDelete();

            $table->string('provider', 20);
            // The PayPal order (checkout) or subscription this belongs to.
            $table->string('provider_order_id', 64)->nullable()->index();
            // The capture (checkout) or sale (renewal) that moved money.
            $table->string('provider_capture_id', 64)->nullable()->unique();

            $table->string('status', 20);
            $table->unsignedBigInteger('amount_cents');
            // The platform's commission, taken by PayPal on the way.
            $table->unsignedBigInteger('fee_cents')->default(0);
            $table->unsignedBigInteger('refunded_cents')->default(0);
            $table->char('currency', 3);
            $table->string('failure_reason')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->timestamp('disputed_at')->nullable();
            $table->timestamp('tracking_sent_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
