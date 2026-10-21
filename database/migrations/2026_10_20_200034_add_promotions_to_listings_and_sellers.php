<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Offers are opt-in per listing, exactly as every marketplace does it:
 * a seller decides which items they will negotiate on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->boolean('accepts_offers')->default(false)->after('shipping_price_cents');

            // Anything at or above this is accepted without the seller
            // having to look. Null means every offer waits for them.
            $table->unsignedInteger('auto_accept_cents')->nullable()->after('accepts_offers');

            // Anything below this is declined on arrival, so a derisory
            // offer never reaches the seller's queue.
            $table->unsignedInteger('auto_decline_cents')->nullable()->after('auto_accept_cents');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn(['accepts_offers', 'auto_accept_cents', 'auto_decline_cents']);
        });
    }
};
