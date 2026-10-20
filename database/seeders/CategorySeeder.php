<?php

namespace Database\Seeders;

use App\Models\Category;
use Database\Seeders\Taxonomy\JewelleryTaxonomy;
use Database\Seeders\Taxonomy\StoneTaxonomy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Builds the listing taxonomy.
 *
 * Idempotent: re-running updates names and ordering in place rather than
 * duplicating, so the tree can be edited and re-seeded safely.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $stones = [
            ...StoneTaxonomy::first(),
            ...StoneTaxonomy::second(),
            ...StoneTaxonomy::collections(),
        ];

        $this->seedBranch(Category::KIND_STONE, $stones);
        $this->seedBranch(Category::KIND_JEWELLERY, JewelleryTaxonomy::all());
    }

    /** @param  array<string, list<string>>  $tree */
    private function seedBranch(string $kind, array $tree): void
    {
        $position = 0;

        foreach ($tree as $parentName => $children) {
            $parent = $this->upsert($kind, $parentName, null, $position++);

            $childPosition = 0;

            foreach ($children as $childName) {
                $this->upsert($kind, $childName, $parent, $childPosition++);
            }
        }
    }

    private function upsert(string $kind, string $name, ?Category $parent, int $position): Category
    {
        // Slugs are scoped by kind so "Rough Emerald" under stones cannot
        // collide with a same-named jewellery node.
        $slug = Str::slug($kind.'-'.$name);

        $category = Category::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'kind' => $kind,
                'parent_id' => $parent?->id,
                'depth' => $parent === null ? 0 : $parent->depth + 1,
                'position' => $position,
                'is_active' => true,
            ],
        );

        $category->refreshPath();

        return $category;
    }
}
