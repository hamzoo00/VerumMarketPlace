<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sellers', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('initials', 4);
            $table->string('location');
            $table->text('about');
            $table->string('ships');

            $table->unsignedInteger('listings_count')->default(0);
            $table->string('sold_label')->nullable();
            // Ratings are displayed to one decimal (4.9); decimal keeps it exact.
            $table->decimal('rating', 2, 1)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->unsignedSmallInteger('years_active')->default(0);
            $table->string('since', 4)->nullable();
            $table->date('verified_on')->nullable();

            $table->string('accent')->nullable();
            $table->json('specialties')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sellers');
    }
};
