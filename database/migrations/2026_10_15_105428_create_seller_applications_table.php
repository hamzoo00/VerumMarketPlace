<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Seller applications: the hand-vetting funnel promised on /sell.
 *
 * Submitted -> admin review -> approved or rejected. The seller can watch the
 * stage from their own dashboard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_account_id')->constrained()->cascadeOnDelete();

            // Shown to the applicant, e.g. APP-2026-4821.
            $table->string('reference')->unique();

            $table->string('status')->default('submitted');

            $table->string('business_name');
            $table->string('country');
            $table->string('trade_license_number')->nullable();
            $table->text('specialties')->nullable();
            $table->text('about')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('reviewed_by')->nullable();
            $table->text('decision_note')->nullable();

            $table->timestamps();

            $table->index(['status', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_applications');
    }
};
