<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Listing approval, featuring, and the commission recorded per sale.
 *
 * Approval and featuring are both admin decisions a seller asks for, so
 * each is a small state machine with its own stamp — not a boolean, or
 * "who approved this and when" has no answer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            /*
             * Approval. Only meaningful while the platform requires it;
             * `null` on an existing listing means it predates the rule,
             * which is why publish() grandfathers those rather than
             * hiding the whole catalogue the day the toggle flips.
             */
            $table->string('approval_state', 16)->nullable()->after('status');
            $table->timestamp('approval_requested_at')->nullable()->after('approval_state');
            $table->timestamp('approved_at')->nullable()->after('approval_requested_at');
            $table->foreignId('approved_by')->nullable()->after('approved_at')
                ->constrained('admins')->nullOnDelete();
            $table->string('approval_note')->nullable()->after('approved_by');

            // Featuring: requested by a premium seller, granted by an admin.
            $table->string('feature_state', 16)->nullable()->after('approval_note');
            $table->timestamp('feature_requested_at')->nullable()->after('feature_state');
            $table->timestamp('featured_at')->nullable()->after('feature_requested_at');
            $table->timestamp('featured_until')->nullable()->after('featured_at');

            $table->index(['approval_state', 'approval_requested_at']);
            $table->index(['feature_state', 'feature_requested_at']);
            $table->index(['featured_at', 'featured_until']);
        });

        Schema::table('orders', function (Blueprint $table) {
            /*
             * Commission, copied at the moment of sale like every other
             * figure here. The rate is stored alongside the amount: a
             * seller asking "why is this £54" a month later needs the
             * rate that applied then, not today's.
             */
            $table->unsignedBigInteger('commission_cents')->default(0)->after('total_cents');
            $table->decimal('commission_rate', 5, 2)->default(0)->after('commission_cents');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn([
                'approval_state',
                'approval_requested_at',
                'approved_at',
                'approval_note',
                'feature_state',
                'feature_requested_at',
                'featured_at',
                'featured_until',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['commission_cents', 'commission_rate']);
        });
    }
};
