<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_lots', function (Blueprint $table) {
            $table->id();
            // One lot per stone: a stone is either a fixed-price listing or a lot.
            $table->foreignId('stone_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('lot_number')->unique();

            $table->unsignedInteger('current_bid_cents');
            $table->unsignedInteger('bid_increment_cents');
            $table->unsignedInteger('estimate_low_cents');
            $table->unsignedInteger('estimate_high_cents');
            $table->unsignedInteger('bids_count')->default(0);

            // Absolute end time; the countdown is derived from this, not stored.
            $table->timestamp('ends_at');
            $table->timestamps();

            $table->index('ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_lots');
    }
};
