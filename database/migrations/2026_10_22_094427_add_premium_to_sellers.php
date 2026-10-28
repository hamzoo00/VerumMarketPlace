<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Premium standing and the certificate that comes with it.
 *
 * Both are granted by an admin and revocable, so each carries its own
 * timestamp rather than a boolean: "when did this seller become
 * premium" is a question that gets asked, and a flag cannot answer it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            // Premium standing. Null means an ordinary seller.
            $table->timestamp('premium_since')->nullable()->after('verified_on');
            $table->timestamp('premium_until')->nullable()->after('premium_since');

            /*
             * The certificate. Issued separately from premium, because a
             * seller may be premium while their paperwork is still being
             * checked — and revoking the certificate must not revoke
             * their standing.
             */
            $table->string('certificate_reference', 32)->nullable()->unique()->after('premium_until');
            $table->timestamp('certified_at')->nullable()->after('certificate_reference');
            $table->foreignId('certified_by')->nullable()->after('certified_at')
                ->constrained('admins')->nullOnDelete();
            $table->string('certificate_note')->nullable()->after('certified_by');

            $table->index(['premium_since', 'premium_until']);
        });
    }

    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('certified_by');
            $table->dropColumn([
                'premium_since',
                'premium_until',
                'certificate_reference',
                'certified_at',
                'certificate_note',
            ]);
        });
    }
};
