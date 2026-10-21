<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One message in a thread.
 *
 * Append-only, like `bids` and `offers`: nothing is edited or deleted, so
 * a conversation produced as evidence in a dispute is the conversation
 * that happened. "Delete" would make the record worthless exactly when
 * it matters.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();

            // Who wrote it: 'buyer', 'seller' or 'admin'. The id is the
            // row in that party's own table, so there is no polymorphic
            // lookup on the hot path.
            $table->string('sender_type', 16);
            $table->unsignedBigInteger('sender_id');

            $table->text('body');

            /*
             * Moderation, recorded at the time of sending.
             *
             * The message is always delivered — flagging is a record for
             * the admin, not a block. Blocking on a regex costs real
             * sales to false positives, which is the mistake eBay's
             * filter is known for.
             */
            $table->unsignedSmallInteger('risk_score')->default(0);

            // Which rules matched, as a list of rule keys.
            $table->jsonb('flags')->nullable();
            $table->boolean('is_flagged')->default(false);

            // An admin's verdict on a flagged message.
            $table->string('review_state', 16)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'id']);
            // The admin queue: flagged and not yet judged.
            $table->index(['is_flagged', 'review_state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
