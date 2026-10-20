<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bids on auction listings.
 *
 * Append-only: a bid is never edited or deleted, so the history of a lot is
 * always reconstructable. The current price is the highest bid, not a
 * mutable column that could drift from the record.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained()->cascadeOnDelete();

            $table->unsignedInteger('amount_cents');
            $table->string('currency', 3)->default('USD');

            // Set when a later bid overtakes this one.
            $table->timestamp('outbid_at')->nullable();
            // A bid that triggered the anti-sniping extension.
            $table->boolean('extended_auction')->default(false);

            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['listing_id', 'amount_cents']);
            $table->index(['buyer_id', 'created_at']);
        });

        Schema::table('listings', function (Blueprint $table) {
            // Denormalised for listing pages: recomputed on every accepted bid.
            $table->unsignedInteger('current_bid_cents')->nullable()->after('bid_increment_cents');
            $table->unsignedInteger('bids_count')->default(0)->after('current_bid_cents');
            // How many times anti-sniping has pushed the clock out.
            $table->unsignedSmallInteger('extensions_count')->default(0)->after('bids_count');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn(['current_bid_cents', 'bids_count', 'extensions_count']);
        });

        Schema::dropIfExists('bids');
    }
};
