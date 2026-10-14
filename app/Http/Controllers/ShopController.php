<?php

namespace App\Http\Controllers;

use App\Models\GemType;
use App\Models\Stone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'type' => ['nullable', 'string', 'exists:gem_types,slug'],
            'sort' => ['nullable', 'in:newest,price-asc,price-desc,carat-desc'],
            'untreated' => ['nullable', 'boolean'],
        ]);

        $stones = Stone::forSale()
            ->with(['gemType', 'seller'])
            ->when(
                $validated['type'] ?? null,
                fn ($query, string $slug) => $query->whereRelation('gemType', 'slug', $slug),
            )
            ->when(
                filter_var($validated['untreated'] ?? false, FILTER_VALIDATE_BOOLEAN),
                fn ($query) => $query->where('untreated', true),
            )
            ->tap(fn ($query) => match ($validated['sort'] ?? 'newest') {
                'price-asc' => $query->orderBy('price_cents'),
                'price-desc' => $query->orderByDesc('price_cents'),
                'carat-desc' => $query->orderByDesc('carat'),
                default => $query->orderByDesc('position'),
            })
            ->paginate(12)
            ->withQueryString();

        return view('pages.shop', [
            'stones' => $stones,
            'gemTypes' => GemType::orderBy('position')->withCount('stones')->get(),
            'activeType' => $validated['type'] ?? null,
            'activeSort' => $validated['sort'] ?? 'newest',
            'untreatedOnly' => filter_var($validated['untreated'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
