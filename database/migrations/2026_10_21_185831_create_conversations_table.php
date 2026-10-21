<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One thread between a buyer and a seller.
 *
 * Always exactly those two parties — an admin can read and post, but is
 * never a member, so no listing is needed to find "my conversations".
 * A thread may be about a listing, or about nothing in particular.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained()->nullOnDelete();

            $table->string('subject')->nullable();

            // Denormalised so an inbox can be listed without touching
            // `messages` — the hot query on every page load.
            $table->timestamp('last_message_at')->nullable();
            $table->string('last_message_preview', 160)->nullable();

            // Unread counts per side, kept by SendMessage.
            $table->unsignedInteger('buyer_unread')->default(0);
            $table->unsignedInteger('seller_unread')->default(0);

            // Set when a case is raised, so an admin can see the history
            // that led to it without hunting.
            $table->boolean('is_flagged')->default(false);
            $table->timestamp('buyer_archived_at')->nullable();
            $table->timestamp('seller_archived_at')->nullable();

            $table->timestamps();

            // One thread per buyer/seller/listing: a second one would
            // split the history of the same negotiation.
            $table->unique(['buyer_id', 'seller_id', 'listing_id']);
            $table->index(['buyer_id', 'last_message_at']);
            $table->index(['seller_id', 'last_message_at']);
            $table->index(['is_flagged', 'last_message_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
