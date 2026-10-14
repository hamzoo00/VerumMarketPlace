<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $table->string('reviewer_name');
            $table->string('place');
            $table->unsignedTinyInteger('stars');
            $table->date('reviewed_on');
            $table->string('stone_name');
            $table->text('body');
            $table->timestamps();

            $table->index(['seller_id', 'reviewed_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_reviews');
    }
};
