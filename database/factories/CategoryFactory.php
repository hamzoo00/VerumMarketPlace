<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Category> */
class CategoryFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = rtrim(fake()->unique()->sentence(2), '.');

        return [
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'name' => $name,
            'kind' => Category::KIND_STONE,
            'parent_id' => null,
            'depth' => 0,
            'position' => 0,
            'is_active' => true,
        ];
    }

    public function jewellery(): static
    {
        return $this->state(fn (): array => ['kind' => Category::KIND_JEWELLERY]);
    }

    public function childOf(Category $parent): static
    {
        return $this->state(fn (): array => [
            'parent_id' => $parent->id,
            'kind' => $parent->kind,
            'depth' => $parent->depth + 1,
        ]);
    }
}
