<?php

namespace Database\Seeders;

use App\Models\AuctionLot;
use App\Models\GemType;
use App\Models\Seller;
use App\Models\SellerReview;
use App\Models\Stone;
use Illuminate\Database\Seeder;

class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $gemTypes = $this->seedGemTypes();
        $sellers = $this->seedSellers();
        $stones = $this->seedStones($gemTypes, $sellers);

        $this->seedAuctionLots($stones);
        $this->seedReviews($sellers);
    }

    /** @return array<string, GemType> */
    private function seedGemTypes(): array
    {
        $types = [];

        foreach (DemoCatalogData::gemTypes() as $row) {
            $types[$row['slug']] = GemType::updateOrCreate(
                ['slug' => $row['slug']],
                $row,
            );
        }

        return $types;
    }

    /** @return array<string, Seller> */
    private function seedSellers(): array
    {
        $sellers = [];

        foreach (DemoCatalogData::sellers() as $row) {
            $sellers[$row['slug']] = Seller::updateOrCreate(
                ['slug' => $row['slug']],
                $row,
            );
        }

        return $sellers;
    }

    /**
     * @param  array<string, GemType>  $gemTypes
     * @param  array<string, Seller>  $sellers
     * @return array<string, Stone>
     */
    private function seedStones(array $gemTypes, array $sellers): array
    {
        $stones = [];

        foreach (DemoCatalogData::stones() as $row) {
            $gemTypeSlug = $row['gem_type'];
            $sellerSlug = $row['seller'];
            unset($row['gem_type'], $row['seller']);

            $stones[$row['slug']] = Stone::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    ...$row,
                    'gem_type_id' => $gemTypes[$gemTypeSlug]->id,
                    'seller_id' => $sellers[$sellerSlug]->id,
                ],
            );
        }

        return $stones;
    }

    /** @param  array<string, Stone>  $stones */
    private function seedAuctionLots(array $stones): void
    {
        foreach (DemoCatalogData::auctionLots() as $row) {
            $stoneSlug = $row['stone'];
            // The demo expressed time left as minutes from load; store an
            // absolute end time so countdowns survive a page refresh.
            $endsAt = now()->addMinutes((int) round($row['ends_in_minutes']));
            unset($row['stone'], $row['ends_in_minutes']);

            AuctionLot::updateOrCreate(
                ['stone_id' => $stones[$stoneSlug]->id],
                [...$row, 'ends_at' => $endsAt],
            );
        }
    }

    /** @param  array<string, Seller>  $sellers */
    private function seedReviews(array $sellers): void
    {
        foreach (DemoCatalogData::reviews() as $row) {
            $sellerSlug = $row['seller'];
            unset($row['seller']);

            SellerReview::updateOrCreate(
                [
                    'seller_id' => $sellers[$sellerSlug]->id,
                    'reviewer_name' => $row['reviewer_name'],
                    'reviewed_on' => $row['reviewed_on'],
                ],
                $row,
            );
        }
    }
}
