<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The listing taxonomy: a self-referencing tree.
 *
 * Adjacency list plus a materialised `path`, so the common reads — breadcrumbs
 * and "everything under this branch" — are one query each instead of a
 * recursive walk per node.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->cascadeOnDelete();

            $table->string('slug')->unique();
            $table->string('name');

            // 'stone' or 'jewellery': which branch of the catalogue this sits in.
            $table->string('kind')->index();

            // Depth from the root, and the ancestor path "1/14/87" for subtree queries.
            $table->unsignedSmallInteger('depth')->default(0);
            $table->string('path')->nullable()->index();

            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['parent_id', 'position']);
            $table->index(['kind', 'depth']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
