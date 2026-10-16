<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a public storefront (`sellers`) to the account that owns it.
 *
 * Nullable because the catalogue is seeded with demo storefronts that have no
 * login yet, and because an account has no storefront until it is approved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->foreignId('seller_account_id')
                ->nullable()
                ->unique()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('seller_account_id');
        });
    }
};
