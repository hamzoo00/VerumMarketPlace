<?php

namespace App\Http\Controllers;

use App\Models\Seller;
use Illuminate\Contracts\View\View;

class AboutController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.about', [
            'sellers' => Seller::orderByDesc('rating')->get(),
        ]);
    }
}
