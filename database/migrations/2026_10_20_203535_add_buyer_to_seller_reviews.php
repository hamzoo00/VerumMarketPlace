<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ties a review to the buyer who wrote it.
 *
 * A rating is only a trust signal if the reviewer actually bought
 * something, so a review now belongs to a buyer and to the listing it is
 * about. The seeded demo rows keep a null buyer and stay readable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_reviews', function (Blueprint $table) {
            $table->foreignId('buyer_id')->nullable()->after('seller_id')
                ->constrained()->cascadeOnDelete();

            $table->foreignId('listing_id')->nullable()->after('buyer_id')
                ->constrained()->nullOnDelete();

            $table->boolean('is_verified')->default(false)->after('stars');

            // One review per buyer per listing: a buyer may review each
            // thing they bought, but not the same one twice.
            $table->unique(['buyer_id', 'listing_id']);
        });
    }

    public function down(): void
    {
        Schema::table('seller_reviews', function (Blueprint $table) {
            $table->dropUnique(['buyer_id', 'listing_id']);
            $table->dropConstrainedForeignId('buyer_id');
            $table->dropConstrainedForeignId('listing_id');
            $table->dropColumn('is_verified');
        });
    }
};
