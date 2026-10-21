<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Price negotiations on a fixed-price listing.
 *
 * Append-only, like `bids`: a counter-offer is a new row that supersedes
 * the one before it, never an edit. That way a negotiation can always be
 * reconstructed, and "who offered what, when" is never in doubt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();

            // A thread groups the rounds of one negotiation. The opening
            // offer's own id, so no extra table is needed.
            $table->unsignedBigInteger('thread_id')->nullable();
            $table->unsignedSmallInteger('round')->default(1);

            // Who made this particular offer.
            $table->string('from', 16);

            $table->unsignedInteger('amount_cents');
            $table->string('currency', 3)->default('USD');
            $table->string('message', 500)->nullable();

            $table->string('status', 16)->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('responded_at')->nullable();

            $table->timestamps();

            // The hot query: "what is waiting for me?"
            $table->index(['seller_id', 'status']);
            $table->index(['buyer_id', 'status']);
            $table->index(['listing_id', 'status']);
            $table->index(['thread_id', 'round']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
