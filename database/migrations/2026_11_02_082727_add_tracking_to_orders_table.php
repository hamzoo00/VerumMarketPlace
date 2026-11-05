<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where the parcel stands, kept on the order so a list of orders can show
 * it without reading every step.
 *
 * `tracking_status` is always the newest step in `shipment_events`,
 * recomputed whenever one is added — never set on its own.
 * `tracking_registered_at` says the courier API is watching the number;
 * `tracking_synced_at` is when it last answered, which orders the poll.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('tracking_status', 32)->nullable()->after('tracking_number');
            $table->timestamp('tracking_status_at')->nullable()->after('tracking_status');
            $table->timestamp('tracking_registered_at')->nullable()->after('tracking_status_at');
            $table->timestamp('tracking_synced_at')->nullable()->after('tracking_registered_at');

            // Courier reports arrive keyed by number, not by order.
            $table->index('tracking_number');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['tracking_number']);
            $table->dropColumn(['tracking_status', 'tracking_status_at', 'tracking_registered_at', 'tracking_synced_at']);
        });
    }
};
