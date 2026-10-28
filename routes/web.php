<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\AuctionController;
use App\Http\Controllers\Buyer\BuyerBidController;
use App\Http\Controllers\Buyer\BuyerCaseController;
use App\Http\Controllers\Buyer\BuyerOfferController;
use App\Http\Controllers\Buyer\BuyerOrderController;
use App\Http\Controllers\Buyer\BuyerReviewController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\Chat\ConversationController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\SellWithUsController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\StoneController;
use App\Http\Controllers\VerifyController;
use App\Support\RouteSegments as Seg;
use Illuminate\Http\Request;
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

// Seller-published listings. `stones` remains for the seeded demo detail pages.
Route::get(Seg::get('listings').'/{listing:slug}', ListingController::class)->name('listings.show');

// Bidding, reviewing and reporting all need a signed-in buyer. Each action
// re-checks its own rules; the middleware only keeps guests out.
Route::middleware(['auth:buyer'])->group(function () {
    Route::post(Seg::get('listings').'/{listing:slug}/'.Seg::get('account.bids_place'), [BuyerBidController::class, 'store'])
        ->middleware('throttle:60,1')
        ->name('listings.bids.store');

    Route::post(Seg::get('listings').'/{listing:slug}/'.Seg::get('account.reviews'), [BuyerReviewController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('listings.reviews.store');

    // "Audit this listing" — open to any buyer, rate-limited because it
    // is the one case anyone can raise.
    Route::post(Seg::get('listings').'/{listing:slug}/'.Seg::get('account.cases'), [BuyerCaseController::class, 'audit'])
        ->middleware('throttle:5,1')
        ->name('listings.audit');

    Route::post(Seg::get('sellers').'/{seller}/'.Seg::get('account.cases'), [BuyerCaseController::class, 'dispute'])
        ->middleware('throttle:5,1')
        ->name('sellers.dispute');

    /*
     * Buying one listing.
     *
     * Per listing rather than through a basket: a gem is one of a kind,
     * and postage and dispatch are one seller's promise. The form is
     * server-rendered so the price a buyer sees is the price the server
     * computed, not one assembled in the browser.
     */
    Route::get(Seg::get('listings').'/{listing:slug}/'.Seg::get('checkout'), [BuyerOrderController::class, 'create'])
        ->name('listings.checkout');

    Route::post(Seg::get('listings').'/{listing:slug}/'.Seg::get('checkout'), [BuyerOrderController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('listings.checkout.store');
});

/*
 * Messaging.
 *
 * One set of routes for all three audiences: the controller resolves who
 * is asking from the session and checks membership against the thread,
 * so a buyer, a seller and an admin reach the same inbox safely.
 */
Route::middleware(['auth.any'])->prefix(Seg::get('account.messages'))->name('chat.')->group(function () {
    /*
     * The shared entry, kept for anyone arriving at /messages directly.
     * It only forwards: each area owns its own inbox routes so the page
     * renders in that area's shell, which precedence alone got wrong —
     * a seller holding an admin session was shown the staff panel.
     */
    Route::get('/', function (Request $request) {
        return redirect()->route(match (true) {
            $request->user('seller') !== null => 'seller.messages',
            $request->user('buyer') !== null => 'buyer.messages',
            default => 'admin.messages',
        });
    })->name('index');
});

// Only a buyer opens a thread, and only from a listing.
Route::post(Seg::get('listings').'/{listing:slug}/'.Seg::get('account.messages'), [ConversationController::class, 'start'])
    ->middleware(['auth:buyer', 'throttle:20,1'])
    ->name('listings.chat.start');

// Making an offer needs a signed-in buyer; the action re-checks every rule.
Route::post(Seg::get('listings').'/{listing:slug}/'.Seg::get('account.offers'), [BuyerOfferController::class, 'store'])
    ->middleware(['auth:buyer', 'throttle:20,1'])
    ->name('listings.offers.store');

Route::get(Seg::get('sellers'), [SellerController::class, 'index'])->name('sellers.index');
Route::get(Seg::get('sellers').'/{seller:slug}', [SellerController::class, 'show'])->name('sellers.show');

Route::get(Seg::get('verify').'/{report?}', VerifyController::class)->name('verify');

Route::get(Seg::get('about'), AboutController::class)->name('about');
Route::get(Seg::get('contact'), ContactController::class)->name('contact');
Route::get(Seg::get('sell'), SellWithUsController::class)->name('sell');
Route::get(Seg::get('help'), HelpController::class)->name('help');

// JSON endpoint for the command-palette search.
Route::get(Seg::get('search'), SearchController::class)->name('search');

Route::post(Seg::get('currency'), CurrencyController::class)->name('currency.switch');

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
