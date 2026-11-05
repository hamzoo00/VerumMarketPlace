<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A parcel's journey, one step per row.
 *
 * Append-only, like `bids` and `messages`: a step is never edited or
 * removed, because "when did it leave, where did it stop" is exactly what
 * a dispute is argued over. Two sources write here — the courier's own
 * scans (through the tracking API) and the seller's updates — and every
 * row says which, so a buyer can weigh a seller's word against a scan.
 *
 * `fingerprint` makes a courier scan idempotent: a provider resends the
 * whole history on every update, and the unique index turns a repeat
 * into a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('source', 12);
            $table->string('status', 32);
            $table->string('description', 500)->nullable();
            $table->string('location', 160)->nullable();
            $table->timestamp('occurred_at');
            $table->foreignId('recorded_by')->nullable()->constrained('seller_accounts')->nullOnDelete();
            $table->string('fingerprint', 64);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['order_id', 'fingerprint']);
            $table->index(['order_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_events');
    }
};
