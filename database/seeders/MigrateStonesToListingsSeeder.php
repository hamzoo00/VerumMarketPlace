<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Listing;
use App\Models\Stone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Moves the seeded demo catalogue into `listings`.
 *
 * The shop and auction pages read one table, so the demo rows have to live
 * in the same shape as seller-created ones. Idempotent: matched on slug, so
 * re-running updates rather than duplicating.
 */
class MigrateStonesToListingsSeeder extends Seeder
{
    public function run(): void
    {
        $stones = Stone::with(['gemType', 'seller', 'auctionLot'])->get();

        foreach ($stones as $stone) {
            $category = $this->categoryFor($stone);

            if ($category === null) {
                $this->command->warn("No category for {$stone->name}; skipped.");

                continue;
            }

            Listing::updateOrCreate(
                ['slug' => $stone->slug],
                $this->attributes($stone, $category),
            );
        }

        $this->command->info("Migrated {$stones->count()} demo stones into listings.");
    }

    /** Maps a demo stone onto the closest node in the taxonomy. */
    private function categoryFor(Stone $stone): ?Category
    {
        $gem = $stone->gemType?->label;

        if ($gem === null) {
            return null;
        }

        // Prefer a child whose name contains the stone's origin or colour,
        // e.g. "Burmese Ruby" over plain "Ruby".
        $parent = Category::query()
            ->ofKind(Category::KIND_STONE)
            ->where('name', $gem)
            ->first();

        if ($parent === null) {
            return null;
        }

        // Match a child by the stone's own name first ("Star sapphire" ->
        // "Star Sapphire"), then by origin. Guessing the first child instead
        // would file an amethyst under Ametrine.
        $child = $parent->children()
            ->get()
            ->first(fn (Category $candidate): bool => $this->matches($candidate, $stone));

        if ($child !== null) {
            return $child;
        }

        // No confident match: use the parent when it can hold a listing,
        // otherwise create a plain child for the family.
        return $parent->isSelectable() ? $parent : $this->generalChild($parent);
    }

    /** Does this category name appear in the stone's name or origin? */
    private function matches(Category $category, Stone $stone): bool
    {
        $needle = mb_strtolower($category->name);
        $haystack = mb_strtolower($stone->name.' '.$stone->origin.' '.$stone->country.' '.$stone->color);
        $family = $stone->gemType === null ? '' : $stone->gemType->label;

        // Compare on the distinguishing word, so "Burmese Ruby" matches a
        // stone from Mogok, Myanmar only via "Burmese" when present.
        return str_contains($haystack, $needle)
            || str_contains($haystack, trim(str_ireplace($family, '', $category->name)));
    }

    /** A catch-all leaf so a family's stones are not forced into a variety. */
    private function generalChild(Category $parent): Category
    {
        $name = $parent->name.' (other)';

        $child = Category::updateOrCreate(
            ['slug' => Str::slug("stone-{$name}")],
            [
                'name' => $name,
                'kind' => $parent->kind,
                'parent_id' => $parent->id,
                'depth' => $parent->depth + 1,
                'position' => 999,
                'is_active' => true,
            ],
        );

        $child->refreshPath();

        return $child;
    }

    /** @return array<string, mixed> */
    private function attributes(Stone $stone, Category $category): array
    {
        $lot = $stone->auctionLot;
        $isAuction = $lot !== null;

        return [
            'seller_id' => $stone->seller_id,
            'category_id' => $category->id,
            'title' => $stone->name,
            'description' => $stone->description,
            'listing_type' => $isAuction ? Listing::TYPE_AUCTION : Listing::TYPE_FIXED,
            'status' => Listing::STATUS_ACTIVE,
            'published_at' => $stone->created_at ?? now(),

            'price_cents' => $isAuction ? null : $stone->price_cents,
            'start_price_cents' => $lot?->estimate_low_cents,
            'reserve_price_cents' => null,
            'bid_increment_cents' => $lot?->bid_increment_cents,
            'current_bid_cents' => $lot?->current_bid_cents,
            'bids_count' => $lot === null ? 0 : $lot->bids_count,
            'auction_ends_at' => $lot?->ends_at,
            'currency' => $stone->currency,
            'quantity' => 1,

            'carat' => $stone->carat,
            'dimensions' => $stone->dimensions,
            'shape' => $stone->shape,
            'cut' => $stone->cut,
            'color' => $stone->color,
            'treatment' => $stone->treatment,
            'untreated' => $stone->untreated,
            'origin' => $stone->origin,

            'lab' => $stone->lab,
            'report_number' => $stone->report_number,
            'report_date' => $stone->report_date,
        ];
    }
}
