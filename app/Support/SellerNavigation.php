<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * Builds the seller sidebar from config/verum_seller_nav.php.
 *
 * Items marked `built: false` render as disabled rather than being hidden, so
 * a seller can see what is coming without hitting a dead link.
 */
final class SellerNavigation
{
    /**
     * @return list<array{
     *     label: string,
     *     items: list<array{label: string, icon: string, url: ?string, active: bool, built: bool}>
     * }>
     */
    public static function groups(): array
    {
        $current = Route::currentRouteName();

        return array_values(array_map(
            fn (array $group): array => [
                'label' => __($group['label']),
                'items' => array_map(
                    fn (array $item): array => self::item($item, $current),
                    $group['items'],
                ),
            ],
            self::config(),
        ));
    }

    /**
     * @return array<string, array{label: string, items: list<array{key: string, label: string, icon: string, built: bool}>}>
     */
    private static function config(): array
    {
        /** @var array<string, array{label: string, items: list<array{key: string, label: string, icon: string, built: bool}>}> */
        return config('verum_seller_nav', []);
    }

    /**
     * @param  array{key: string, label: string, icon: string, built: bool}  $item
     * @return array{label: string, icon: string, url: ?string, active: bool, built: bool}
     */
    private static function item(array $item, ?string $current): array
    {
        $name = "seller.{$item['key']}";
        $exists = $item['built'] && Route::has($name);

        return [
            'label' => __($item['label']),
            'icon' => $item['icon'],
            'url' => $exists ? route($name) : null,
            'active' => $exists && self::isActive($name, $current),
            'built' => $exists,
        ];
    }

    /** A parent stays lit while a child route is open, but "create" is its own item. */
    private static function isActive(string $name, ?string $current): bool
    {
        if ($current === null) {
            return false;
        }

        if ($current === $name) {
            return true;
        }

        return $name !== 'seller.listings.create'
            && str_starts_with($current, $name.'.')
            && $current !== 'seller.listings.create';
    }
}
