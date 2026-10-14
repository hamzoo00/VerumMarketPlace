<?php

namespace App\Http\Controllers;

use App\Models\AuctionLot;
use Illuminate\Contracts\View\View;

class AuctionController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.auctions', [
            'lots' => AuctionLot::query()
                ->with(['stone.gemType', 'stone.seller'])
                ->orderBy('ends_at')
                ->get(),
        ]);
    }
}
