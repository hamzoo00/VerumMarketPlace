<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every webhook PayPal delivered, by its event id.
 *
 * PayPal retries a webhook until it is acknowledged, so the same event
 * can arrive several times. The unique id is what makes handling it a
 * second time a no-op. Only the id, type and the thing it concerns are
 * kept — not the payload, which carries the payer's personal details.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 20);
            $table->string('event_id', 80)->unique();
            $table->string('type', 80);
            $table->string('resource_id', 80)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
