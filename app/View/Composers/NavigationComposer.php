<?php

namespace App\View\Composers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;

/**
 * Supplies the site chrome with its navigation items and ticker lines so the
 * layout never depends on individual controllers passing them through.
 */
class NavigationComposer
{
    public function compose(View $view): void
    {
        $view->with([
            'navigation' => $this->navigation(),
            'ticker' => $this->ticker(),
            'footerLinks' => $this->footerLinks(),
        ]);
    }

    /** @return list<array{key: string, label: string, url: string, active: bool}> */
    private function navigation(): array
    {
        $items = [
            ['key' => 'home', 'label' => __('Home'), 'route' => 'home'],
            ['key' => 'shop', 'label' => __('Shop'), 'route' => 'shop'],
            ['key' => 'auctions', 'label' => __('Auctions'), 'route' => 'auctions'],
            ['key' => 'sellers', 'label' => __('Sellers'), 'route' => 'sellers.index'],
            ['key' => 'about', 'label' => __('About'), 'route' => 'about'],
            ['key' => 'contact', 'label' => __('Contact'), 'route' => 'contact'],
        ];

        return array_map(fn (array $item): array => [
            'key' => $item['key'],
            'label' => $item['label'],
            'url' => route($item['route']),
            'active' => $this->isActive($item['route']),
        ], $items);
    }

    /** Highlights a nav item for its own route and that route's children. */
    private function isActive(string $route): bool
    {
        $current = Route::currentRouteName();

        if ($current === null) {
            return false;
        }

        return match ($route) {
            'home' => $current === 'home',
            'shop' => $current === 'shop' || str_starts_with($current, 'stones.'),
            'sellers.index' => str_starts_with($current, 'sellers.'),
            default => $current === $route,
        };
    }

    /** @return list<string> */
    private function ticker(): array
    {
        return [
            __('Report <b>VR-2026-002448</b> verified · Dubai, UAE'),
            __('Lot 0145 · Swat emerald, no oil · <b>closing soon</b>'),
            __('New listing · <b>Padparadscha sapphire</b>, 1.08 ct · Ratnapura'),
            __('Insured parcel delivered · Paris, France'),
            __('Report <b>VR-2026-002455</b> verified · Singapore'),
            __("This week's sale · <b>The Mogok Reds</b> · closes Sunday 20:00 PKT"),
        ];
    }

    /** @return array<string, array{label: string, links: list<array{label: string, url: string}>}> */
    private function footerLinks(): array
    {
        return [
            'shop' => [
                'label' => __('Shop'),
                'links' => [
                    ['label' => __('All gemstones'), 'url' => route('shop')],
                    ['label' => __('Sapphire'), 'url' => route('shop', ['type' => 'sapphire'])],
                    ['label' => __('Ruby'), 'url' => route('shop', ['type' => 'ruby'])],
                    ['label' => __('Emerald'), 'url' => route('shop', ['type' => 'emerald'])],
                    ['label' => __('Live auctions'), 'url' => route('auctions')],
                ],
            ],
            'company' => [
                'label' => __('Company'),
                'links' => [
                    ['label' => __('About us'), 'url' => route('about')],
                    ['label' => __('Our sellers'), 'url' => route('sellers.index')],
                    ['label' => __('Contact'), 'url' => route('contact')],
                    ['label' => __('Sell on Verum'), 'url' => route('sell')],
                ],
            ],
            'support' => [
                'label' => __('Support'),
                'links' => [
                    ['label' => __('Shipping policy'), 'url' => route('help', ['#' => 'shipping'])],
                    ['label' => __('Returns'), 'url' => route('help', ['#' => 'returns'])],
                    ['label' => __('Certification guide'), 'url' => route('help', ['#' => 'certification'])],
                    ['label' => __('Gem care guide'), 'url' => route('help', ['#' => 'care'])],
                    ['label' => __('Verify a report'), 'url' => route('verify')],
                ],
            ],
        ];
    }
}
