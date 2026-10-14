<?php

namespace App\Http\Controllers;

use App\Models\Seller;
use Illuminate\Contracts\View\View;

class SellerController extends Controller
{
    public function index(): View
    {
        return view('pages.sellers.index', [
            'sellers' => Seller::query()
                ->withCount('stones')
                ->orderByDesc('rating')
                ->get(),
        ]);
    }

    public function show(Seller $seller): View
    {
        $seller->load([
            'stones.gemType',
            'reviews' => fn ($query) => $query->orderByDesc('reviewed_on'),
        ]);

        return view('pages.sellers.show', [
            'seller' => $seller,
        ]);
    }
}
