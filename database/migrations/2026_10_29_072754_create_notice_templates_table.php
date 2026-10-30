<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An admin's own wording for a standard message.
 *
 * The defaults live in config/verum_notices.php and stay the fallback: a
 * kind with no row here, or one reset, reads from code. One row per kind,
 * so an edit replaces the last one — what each person was actually sent
 * is kept separately in `account_notices`, which is the history that
 * matters.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notice_templates', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 40)->unique();
            $table->string('subject', 160);
            $table->text('body');
            $table->foreignId('updated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notice_templates');
    }
};
