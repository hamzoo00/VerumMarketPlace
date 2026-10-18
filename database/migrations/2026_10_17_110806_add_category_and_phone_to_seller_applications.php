<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Richer seller applications: what they trade in, and how to reach them.
 *
 * `specialties` becomes json so the dependent checkboxes round-trip cleanly;
 * the old free-text column held a comma-separated string.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_applications', function (Blueprint $table) {
            $table->string('category')->nullable()->after('business_name');
            $table->json('specialty_keys')->nullable()->after('category');
            $table->string('phone')->nullable()->after('country');
            // Country moves to an ISO code; keep the old text for reference.
            $table->string('country_code', 2)->nullable()->after('country');
            $table->timestamp('archived_at')->nullable()->after('decision_note');

            $table->index('archived_at');
        });
    }

    public function down(): void
    {
        Schema::table('seller_applications', function (Blueprint $table) {
            $table->dropIndex(['archived_at']);
            $table->dropColumn(['category', 'specialty_keys', 'phone', 'country_code', 'archived_at']);
        });
    }
};
