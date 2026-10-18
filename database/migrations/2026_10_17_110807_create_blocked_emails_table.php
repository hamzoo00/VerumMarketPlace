<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Emails barred from applying as a seller again.
 *
 * Scoped to seller signup only: a rejected trade licence is no reason to stop
 * someone buying. An admin can lift a block, because decisions get revised.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_emails', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('scope')->default('seller');
            $table->text('reason')->nullable();
            $table->string('blocked_by')->nullable();
            $table->timestamps();

            $table->index(['email', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_emails');
    }
};
