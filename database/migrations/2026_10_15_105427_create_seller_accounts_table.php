<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Seller accounts (the login identity).
 *
 * Distinct from the existing `sellers` table, which is the public storefront
 * shown in the catalogue. A seller account is linked to a storefront only once
 * an admin approves the application.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            $table->string('phone')->nullable();
            $table->string('country')->nullable();

            // Gates access to the seller area; set when an admin approves.
            $table->timestamp('approved_at')->nullable();

            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();

            $table->rememberToken();
            $table->timestamps();

            $table->index('approved_at');
        });

        Schema::create('seller_account_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_account_password_reset_tokens');
        Schema::dropIfExists('seller_accounts');
    }
};
