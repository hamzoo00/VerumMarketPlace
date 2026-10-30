<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A seller's standing after approval: suspended, closing, closed.
 *
 * Timestamps rather than a status column, like premium: "since when"
 * is the question an appeal asks, and approval history must survive a
 * suspension — `approved_at` is left alone, so reinstating is clearing
 * one column rather than re-approving.
 *
 * `sellers.hidden_at` is the public side of the same decision. Every
 * public listing query already goes through `scopePublished()`, so one
 * column there takes a whole storefront off the site and puts it back
 * exactly as it was, without touching a single listing row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_accounts', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable()->after('approved_at');

            /*
             * Closing is a wind-down, not a switch. Parcels already on the
             * road keep the account open until they could have arrived and
             * the buyer's window to dispute has passed.
             */
            $table->timestamp('closing_started_at')->nullable()->after('suspended_at');
            $table->timestamp('closes_at')->nullable()->after('closing_started_at');
            $table->timestamp('closed_at')->nullable()->after('closes_at');

            $table->index(['closing_started_at', 'closed_at']);
        });

        Schema::table('sellers', function (Blueprint $table) {
            $table->timestamp('hidden_at')->nullable()->after('premium_until');
            $table->index('hidden_at');
        });
    }

    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->dropIndex(['hidden_at']);
            $table->dropColumn('hidden_at');
        });

        Schema::table('seller_accounts', function (Blueprint $table) {
            $table->dropIndex(['closing_started_at', 'closed_at']);
            $table->dropColumn(['suspended_at', 'closing_started_at', 'closes_at', 'closed_at']);
        });
    }
};
