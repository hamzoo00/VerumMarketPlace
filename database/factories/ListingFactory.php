<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Listing;
use App\Models\Seller;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Listing> */
class ListingFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(3), '.');

        return [
            'seller_id' => Seller::factory(),
            'category_id' => Category::factory(),
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 99999),
            'title' => $title,
            'listing_type' => Listing::TYPE_FIXED,
            'status' => Listing::STATUS_DRAFT,
            'price_cents' => fake()->numberBetween(5_000, 500_000),
            'currency' => 'USD',
            'quantity' => 1,
        ];
    }

    public function auction(): static
    {
        return $this->state(fn (): array => [
            'listing_type' => Listing::TYPE_AUCTION,
            'price_cents' => null,
            'start_price_cents' => 25_000,
            'bid_increment_cents' => 2_500,
            'auction_ends_at' => now()->addDays(3),
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => now(),
        ]);
    }
}
