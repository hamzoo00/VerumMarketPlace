<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Things a buyer raises for an admin to settle.
 *
 * Two kinds, one queue, because the admin's job is the same either way:
 *
 *   dispute — a problem with a seller the buyer dealt with
 *   audit   — "this listing looks wrong", reportable by any signed-in buyer
 *
 * One table rather than two: they share a lifecycle, a queue and an
 * outcome, and splitting them would duplicate all three.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 24)->unique();

            $table->string('kind', 16);
            $table->foreignId('buyer_id')->constrained()->cascadeOnDelete();

            // A dispute always names a seller; an audit always names a
            // listing. Both are nullable because only one applies.
            $table->foreignId('seller_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained()->nullOnDelete();

            $table->string('reason', 64);
            $table->text('body');

            $table->string('status', 16)->default('open');

            // The admin's decision, and what they want both sides to read.
            $table->string('resolution', 32)->nullable();
            $table->text('resolution_note')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            // The admin queue, and each party's own view of their cases.
            $table->index(['status', 'kind']);
            $table->index(['buyer_id', 'status']);
            $table->index(['seller_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cases');
    }
};
