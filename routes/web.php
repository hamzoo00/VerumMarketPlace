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
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public marketplace
|--------------------------------------------------------------------------
| Each of these was a client-side panel in the demo. They are real,
| server-rendered URLs here so listings and storefronts can be crawled.
*/

Route::get('/', HomeController::class)->name('home');

Route::get('shop', ShopController::class)->name('shop');
Route::get('stones/{stone:slug}', StoneController::class)->name('stones.show');

Route::get('auctions', AuctionController::class)->name('auctions');

Route::get('sellers', [SellerController::class, 'index'])->name('sellers.index');
Route::get('sellers/{seller:slug}', [SellerController::class, 'show'])->name('sellers.show');

Route::get('verify/{report?}', VerifyController::class)->name('verify');

Route::get('about', AboutController::class)->name('about');
Route::get('contact', ContactController::class)->name('contact');
Route::get('sell', SellWithUsController::class)->name('sell');
Route::get('help', HelpController::class)->name('help');

// JSON endpoint for the command-palette search.
Route::get('search', SearchController::class)->name('search');

Route::get('cart', [CartController::class, 'index'])->name('cart');
Route::get('checkout', [CartController::class, 'checkout'])->name('checkout');

/*
|--------------------------------------------------------------------------
| Authenticated area
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
    $lines = [
        'User-agent: *',
        'Disallow: /dashboard',
        'Disallow: /settings',
        'Disallow: /horizon',
        'Disallow: /cart',
        'Disallow: /checkout',
        'Disallow: /login',
        'Disallow: /register',
        'Disallow: /forgot-password',
        'Disallow: /reset-password',
        'Disallow: /two-factor-challenge',
        'Disallow: /confirm-password',
        '',
        'Sitemap: '.url('sitemap.xml'),
    ];

    return response(implode(PHP_EOL, $lines), 200)
        ->header('Content-Type', 'text/plain');
})->name('robots');

require __DIR__.'/settings.php';
