<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stones', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');

            $table->foreignId('gem_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();

            // Money is stored in minor units; never floats.
            $table->unsignedInteger('price_cents')->nullable();
            $table->string('currency', 3)->default('USD');

            $table->decimal('carat', 6, 2);
            $table->string('dimensions');
            $table->string('shape');
            $table->string('cut');
            $table->string('color');
            $table->string('transparency');
            $table->string('treatment');
            $table->boolean('untreated')->default(false);

            $table->string('origin');
            $table->string('country');

            $table->string('report_number')->unique();
            $table->date('report_date');
            $table->string('lab');
            $table->string('lab_number');

            $table->text('description');
            $table->text('comments')->nullable();

            $table->string('tag')->nullable();
            // Demo image styling: which photo to reuse and an optional CSS filter.
            $table->string('photo')->nullable();
            $table->string('tint')->nullable();

            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['gem_type_id', 'price_cents']);
            $table->index('seller_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stones');
    }
};
