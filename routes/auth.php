<?php

use App\Http\Controllers\Auth\BuyerAuthController;
use App\Http\Controllers\Auth\SellerAuthController;
use App\Http\Controllers\Buyer\BuyerCaseController;
use App\Http\Controllers\Buyer\BuyerDashboardController;
use App\Http\Controllers\Buyer\BuyerOfferController;
use App\Http\Controllers\Buyer\BuyerOrderController;
use App\Http\Controllers\Chat\ConversationController;
use App\Http\Controllers\Seller\SellerApplicationStatusController;
use App\Http\Controllers\Seller\SellerBrandingController;
use App\Http\Controllers\Seller\SellerCaseController;
use App\Http\Controllers\Seller\SellerCouponController;
use App\Http\Controllers\Seller\SellerDashboardController;
use App\Http\Controllers\Seller\SellerListingController;
use App\Http\Controllers\Seller\SellerListingMediaController;
use App\Http\Controllers\Seller\SellerOfferController;
use App\Http\Controllers\Seller\SellerOrderController;
use App\Http\Controllers\Seller\SellerShippingController;
use App\Models\Order;
use App\Support\RouteSegments as Seg;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Buyer
|--------------------------------------------------------------------------
| Buyers need no approval; registering signs them straight in.
*/

$buyer = Seg::get('buyer');

Route::middleware('guest.account')->group(function () use ($buyer) {
    Route::get($buyer.'/'.Seg::get('auth.login'), [BuyerAuthController::class, 'showLogin'])->name('buyer.login');
    Route::post($buyer.'/'.Seg::get('auth.login'), [BuyerAuthController::class, 'login'])->middleware('throttle:10,1');

    Route::get($buyer.'/'.Seg::get('auth.register'), [BuyerAuthController::class, 'showRegister'])->name('buyer.register');
    Route::post($buyer.'/'.Seg::get('auth.register'), [BuyerAuthController::class, 'register'])->middleware('throttle:10,1');
});

Route::middleware('auth:buyer')->prefix($buyer)->name('buyer.')->group(function () {
    Route::get(Seg::get('account.dashboard'), [BuyerDashboardController::class, 'index'])->name('dashboard');
    Route::get(Seg::get('account.saved'), [BuyerDashboardController::class, 'saved'])->name('saved');
    Route::get(Seg::get('account.orders'), [BuyerOrderController::class, 'index'])->name('orders');
    Route::get(Seg::get('account.orders').'/{order}', [BuyerOrderController::class, 'show'])->name('orders.show');
    Route::get(Seg::get('account.bids'), [BuyerDashboardController::class, 'bids'])->name('bids');
    Route::get(Seg::get('account.settings'), [BuyerDashboardController::class, 'settings'])->name('settings');

    // The inbox is shared by all three audiences; these keep each area's
    // own nav honest without duplicating the controller.
    /*
     * The buyer's own inbox, declared as such. Redirecting to the
     * shared /messages let whichever guard ranked highest decide the
     * shell, so a buyer who also held an admin session saw the staff
     * panel instead of their account area.
     */
    Route::get(Seg::get('account.messages'), [ConversationController::class, 'index'])
        ->defaults('audience', 'buyer')
        ->name('messages');

    Route::get(Seg::get('account.messages').'/{conversation}', [ConversationController::class, 'show'])
        ->defaults('audience', 'buyer')
        ->name('messages.show');

    Route::post(Seg::get('account.messages').'/{conversation}', [ConversationController::class, 'store'])
        ->defaults('audience', 'buyer')
        ->middleware('throttle:'.config('verum_chat.limits.per_minute').',1')
        ->name('messages.store');

    // Disputes and audits the buyer has raised.
    Route::get(Seg::get('account.cases'), [BuyerCaseController::class, 'index'])->name('cases');
    Route::get(Seg::get('account.cases').'/{case}', [BuyerCaseController::class, 'show'])->name('cases.show');

    // Offers the buyer has made or been sent.
    Route::get(Seg::get('account.offers'), [BuyerOfferController::class, 'index'])->name('offers');
    Route::post(Seg::get('account.offers').'/{offer}', [BuyerOfferController::class, 'respond'])
        ->name('offers.respond');

    Route::post(Seg::get('auth.logout'), [BuyerAuthController::class, 'logout'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| Seller
|--------------------------------------------------------------------------
| Registering creates a pending application. The account can sign in to watch
| its status, but the seller area stays locked until an admin approves it.
*/

$seller = Seg::get('seller');

Route::middleware('guest.account')->group(function () use ($seller) {
    Route::get($seller.'/'.Seg::get('auth.login'), [SellerAuthController::class, 'showLogin'])->name('seller.login');
    Route::post($seller.'/'.Seg::get('auth.login'), [SellerAuthController::class, 'login'])->middleware('throttle:10,1');

    Route::get($seller.'/'.Seg::get('auth.apply'), [SellerAuthController::class, 'showRegister'])->name('seller.register');
    Route::post($seller.'/'.Seg::get('auth.apply'), [SellerAuthController::class, 'register'])->middleware('throttle:10,1');
});

Route::middleware('auth:seller')->prefix($seller)->name('seller.')->group(function () {
    // Outside the approval gate: a pending seller must be able to see progress.
    Route::get(Seg::get('account.status'), SellerApplicationStatusController::class)->name('status');
    Route::post(Seg::get('auth.logout'), [SellerAuthController::class, 'logout'])->name('logout');

    Route::middleware('seller.approved')->group(function () {
        Route::get(Seg::get('account.dashboard'), [SellerDashboardController::class, 'index'])->name('dashboard');

        // Inventory. `create` sits before `{listing}` so it is not swallowed
        // by the wildcard.
        Route::get(Seg::get('account.listings'), [SellerListingController::class, 'index'])->name('listings');
        Route::get(Seg::get('account.listings').'/new', [SellerListingController::class, 'create'])->name('listings.create');
        Route::post(Seg::get('account.listings'), [SellerListingController::class, 'store'])->name('listings.store');
        Route::get(Seg::get('account.listings').'/{listing}/edit', [SellerListingController::class, 'edit'])->name('listings.edit');
        Route::put(Seg::get('account.listings').'/{listing}', [SellerListingController::class, 'update'])->name('listings.update');
        // A premium seller asks for a slot; an admin grants it.
        Route::post(Seg::get('account.listings').'/{listing}/feature', [SellerListingController::class, 'requestFeature'])
            ->name('listings.feature');

        Route::post(Seg::get('account.listings').'/{listing}/publish', [SellerListingController::class, 'publish'])->name('listings.publish');
        Route::delete(Seg::get('account.listings').'/{listing}', [SellerListingController::class, 'destroy'])->name('listings.destroy');

        // Media. One file per request so progress is real and a single
        // rejection does not fail the batch.
        Route::post(Seg::get('account.listings').'/{listing}/media', [SellerListingMediaController::class, 'store'])
            ->name('listings.media.store');
        Route::delete(Seg::get('account.listings').'/{listing}/media/{media}', [SellerListingMediaController::class, 'destroy'])
            ->name('listings.media.destroy');

        // JSON used by the category browser.
        Route::get('categories/{kind}', [SellerListingController::class, 'categories'])->name('categories');
        // Store branding: avatar and banner, one file each.
        Route::post(Seg::get('account.storefront').'/branding', [SellerBrandingController::class, 'store'])
            ->name('branding.store');
        Route::delete(Seg::get('account.storefront').'/branding', [SellerBrandingController::class, 'destroy'])
            ->name('branding.destroy');

        Route::get(Seg::get('account.messages'), [ConversationController::class, 'index'])
            ->defaults('audience', 'seller')
            ->name('messages');

        Route::get(Seg::get('account.messages').'/{conversation}', [ConversationController::class, 'show'])
            ->defaults('audience', 'seller')
            ->name('messages.show');

        Route::post(Seg::get('account.messages').'/{conversation}', [ConversationController::class, 'store'])
            ->defaults('audience', 'seller')
            ->middleware('throttle:'.config('verum_chat.limits.per_minute').',1')
            ->name('messages.store');

        // Cases raised about this seller; read-only, the admin settles them.
        Route::get(Seg::get('account.cases'), [SellerCaseController::class, 'index'])->name('cases');

        // Offers waiting on the seller.
        Route::get(Seg::get('account.offers'), [SellerOfferController::class, 'index'])->name('offers');
        Route::post(Seg::get('account.offers').'/{offer}', [SellerOfferController::class, 'respond'])
            ->name('offers.respond');

        // Discount codes.
        Route::get(Seg::get('account.coupons'), [SellerCouponController::class, 'index'])->name('coupons');
        Route::post(Seg::get('account.coupons'), [SellerCouponController::class, 'store'])->name('coupons.store');
        Route::put(Seg::get('account.coupons').'/{coupon}', [SellerCouponController::class, 'update'])
            ->name('coupons.update');
        Route::delete(Seg::get('account.coupons').'/{coupon}', [SellerCouponController::class, 'destroy'])
            ->name('coupons.destroy');

        // Postage: seller-level defaults and the rate table.
        Route::get(Seg::get('account.shipping'), [SellerShippingController::class, 'edit'])->name('shipping');
        Route::put(Seg::get('account.shipping'), [SellerShippingController::class, 'update'])->name('shipping.update');

        Route::get(Seg::get('account.orders'), [SellerOrderController::class, 'index'])->name('orders');

        // "Pending checkouts" is the same list, pre-filtered: an order
        // placed but not yet paid for. One controller, one definition.
        Route::get('checkouts', fn () => redirect()->route('seller.orders', [
            'status' => Order::STATUS_AWAITING_PAYMENT,
        ]))->name('checkouts');
        Route::get(Seg::get('account.orders').'/{order}', [SellerOrderController::class, 'show'])->name('orders.show');
        Route::put(Seg::get('account.orders').'/{order}', [SellerOrderController::class, 'update'])->name('orders.update');
        Route::get(Seg::get('account.storefront'), [SellerDashboardController::class, 'storefront'])->name('storefront');
        Route::get(Seg::get('account.settings'), [SellerDashboardController::class, 'settings'])->name('settings');
    });
});

/*
|--------------------------------------------------------------------------
| Password reset and change
|--------------------------------------------------------------------------
| One pair of routes serves both account areas; {area} picks the guard, its
| password broker and its token table. Reset emails are throttled to three
| sends, then a cooldown.
*/

/*
 * The password routes live in routes/password.php, required from
 * FortifyServiceProvider so they register after Fortify's and keep the
 * `password.*` names. See the note there.
 */
