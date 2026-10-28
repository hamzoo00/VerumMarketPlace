<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Switches an admin can throw without a deploy.
 *
 * A key/value table rather than columns, because these are *operational*
 * decisions that change on a whim — a commission rate, whether listings
 * need approving this month. Config files need a deploy; these do not.
 *
 * Anything an engineer decides still belongs in `config/`. The line is:
 * if the business changes it, it lives here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();

            // Typed on read by PlatformSettings, not here: one column
            // keeps the table trivial and the casting in one place.
            $table->text('value')->nullable();

            // Who last moved it, because a commission rate changing is
            // the kind of thing someone asks about afterwards.
            $table->foreignId('updated_by')->nullable()->constrained('admins')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
