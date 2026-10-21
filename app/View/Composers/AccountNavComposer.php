<?php

namespace App\View\Composers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;

/**
 * Navigation for the private buyer and seller areas.
 *
 * Each guard gets its own set; nothing from one area is ever offered to the
 * other.
 */
class AccountNavComposer
{
    public function compose(View $view): void
    {
        $view->with([
            'buyerNav' => $this->items([
                ['dashboard', __('Overview'), 'grid'],
                ['saved', __('Saved stones'), 'heart'],
                ['bids', __('My bids'), 'gavel'],
                ['offers', __('My offers'), 'scale'],
                ['cases', __('Disputes'), 'shield'],
                ['messages', __('Messages'), 'chat'],
                ['orders', __('Orders'), 'box'],
                ['settings', __('Settings'), 'user'],
            ], 'buyer'),

            'sellerNav' => $this->items([
                ['dashboard', __('Overview'), 'grid'],
                ['listings', __('Listings'), 'gem'],
                ['orders', __('Orders'), 'box'],
                ['storefront', __('Storefront'), 'shield'],
                ['settings', __('Settings'), 'user'],
            ], 'seller'),
        ]);
    }

    /**
     * @param  list<array{0: string, 1: string, 2: string}>  $items
     * @return list<array{label: string, url: string, icon: string, active: bool}>
     */
    private function items(array $items, string $area): array
    {
        $current = Route::currentRouteName();

        return array_map(fn (array $item): array => [
            'label' => $item[1],
            'url' => route("{$area}.{$item[0]}"),
            'icon' => $item[2],
            'active' => $current === "{$area}.{$item[0]}",
        ], $items);
    }
}
