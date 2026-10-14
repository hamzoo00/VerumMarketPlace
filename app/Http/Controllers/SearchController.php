<?php

namespace App\Http\Controllers;

use App\Models\Money;
use App\Models\Stone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Backs the command-palette search.
 *
 * Deliberately a simple LIKE query for now; this is the seam where Scout and
 * Meilisearch take over once listings are real.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:80'],
        ]);

        $term = $validated['q'];

        $stones = Stone::query()
            ->with(['gemType', 'seller', 'auctionLot'])
            ->where(function ($query) use ($term): void {
                $query->whereLike('name', "%{$term}%")
                    ->orWhereLike('origin', "%{$term}%")
                    ->orWhereLike('country', "%{$term}%")
                    ->orWhereLike('color', "%{$term}%")
                    ->orWhereLike('report_number', "%{$term}%")
                    ->orWhereRelation('gemType', 'label', 'ilike', "%{$term}%")
                    ->orWhereRelation('seller', 'name', 'ilike', "%{$term}%");
            })
            ->when(
                mb_strtolower($term) === 'unheated',
                fn ($query) => $query->orWhere('untreated', true),
            )
            ->take(8)
            ->get();

        return response()->json(
            $stones->map(fn (Stone $stone): array => [
                'name' => $stone->name,
                'meta' => $stone->caratLabel().' · '.$stone->originLabel(),
                'price' => $stone->auctionLot
                    ? __('Bid :amount', ['amount' => Money::fromCents($stone->auctionLot->current_bid_cents)->format()])
                    : $stone->price()?->format(),
                'url' => route('stones.show', $stone),
                'image' => $stone->imageUrl('160'),
            ])->all()
        );
    }
}
