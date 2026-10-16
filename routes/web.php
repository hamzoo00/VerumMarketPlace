<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\AuctionController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\SellWithUsController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\StoneController;
use App\Http\Controllers\VerifyController;
use App\Support\RouteSegments as Seg;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public marketplace
|--------------------------------------------------------------------------
| Paths come from config/verum_routes.php so any URL can be renamed there
| without touching this file. Route names are the stable contract.
*/

Route::get('/', HomeController::class)->name('home');

Route::get(Seg::get('shop'), ShopController::class)->name('shop');
Route::get(Seg::get('stones').'/{stone:slug}', StoneController::class)->name('stones.show');

Route::get(Seg::get('auctions'), AuctionController::class)->name('auctions');

Route::get(Seg::get('sellers'), [SellerController::class, 'index'])->name('sellers.index');
Route::get(Seg::get('sellers').'/{seller:slug}', [SellerController::class, 'show'])->name('sellers.show');

Route::get(Seg::get('verify').'/{report?}', VerifyController::class)->name('verify');

Route::get(Seg::get('about'), AboutController::class)->name('about');
Route::get(Seg::get('contact'), ContactController::class)->name('contact');
Route::get(Seg::get('sell'), SellWithUsController::class)->name('sell');
Route::get(Seg::get('help'), HelpController::class)->name('help');

// JSON endpoint for the command-palette search.
Route::get(Seg::get('search'), SearchController::class)->name('search');

Route::get(Seg::get('cart'), [CartController::class, 'index'])->name('cart');
Route::get(Seg::get('checkout'), [CartController::class, 'checkout'])->name('checkout');

/*
|--------------------------------------------------------------------------
| Starter-kit area (Fortify `web` guard)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

/*
 * Served dynamically so the Sitemap directive carries an absolute URL,
 * which the robots.txt spec requires, without hardcoding the domain.
 */
Route::get('robots.txt', function () {
    $disallow = [
        Seg::get('buyer'),
        Seg::get('seller'),
        Seg::get('admin'),
        Seg::get('cart'),
        Seg::get('checkout'),
        'dashboard',
        'settings',
        'horizon',
        'login',
        'register',
    ];

    $lines = ['User-agent: *'];

    foreach ($disallow as $path) {
        $lines[] = "Disallow: /{$path}";
    }

    $lines[] = '';
    $lines[] = 'Sitemap: '.url('sitemap.xml');

    return response(implode(PHP_EOL, $lines), 200)
        ->header('Content-Type', 'text/plain');
})->name('robots');

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
