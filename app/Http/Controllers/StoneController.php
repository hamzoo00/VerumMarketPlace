<?php

namespace App\Http\Controllers;

use App\Models\Stone;
use Illuminate\Contracts\View\View;

class StoneController extends Controller
{
    public function __invoke(Stone $stone): View
    {
        $stone->load(['gemType', 'seller', 'auctionLot']);

        return view('pages.stone', [
            'stone' => $stone,
            'related' => Stone::forSale()
                ->with(['gemType', 'seller'])
                ->where('gem_type_id', $stone->gem_type_id)
                ->whereKeyNot($stone->getKey())
                ->take(4)
                ->get(),
        ]);
    }
}
