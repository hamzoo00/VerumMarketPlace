<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every message staff send to an account: warnings, suspensions,
 * decisions on a listing, the apology when a store closes.
 *
 * Append-only, like `bids` and `messages`. The text actually sent is
 * stored — the admin's own words or the default they left in place —
 * because "what exactly were they told, and when" is what a dispute or
 * an appeal turns on. A default can change later; this row cannot.
 *
 * Separate from `notifications`, which is the delivery: a recipient may
 * mark those read or they may be pruned, and neither may touch the record.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_notices', function (Blueprint $table) {
            $table->id();

            // A buyer or a seller account — the login identity, never the
            // storefront, because that is who reads the email.
            $table->morphs('recipient');

            // A key into config/verum_notices.php.
            $table->string('kind', 40);

            // What it is about: a message, a listing, an order. Optional.
            $table->nullableMorphs('subject');

            $table->text('body');
            $table->boolean('used_default')->default(true);

            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();

            // Warning counts are read per recipient on every admin list.
            $table->index(['recipient_type', 'recipient_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_notices');
    }
};
