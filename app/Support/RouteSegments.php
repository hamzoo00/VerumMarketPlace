<?php

namespace App\Support;

/**
 * Reads URL segments from config/verum_routes.php.
 *
 * Route files call these instead of writing path strings, so a segment can be
 * renamed in config without touching any route, view or redirect.
 */
final class RouteSegments
{
    public static function get(string $key): string
    {
        return (string) config("verum_routes.{$key}");
    }

    /** Joins segments into a path, e.g. path('buyer', 'account.dashboard'). */
    public static function path(string ...$keys): string
    {
        return implode('/', array_map(self::get(...), $keys));
    }

    public static function admin(string ...$keys): string
    {
        return implode('/', [self::get('admin'), ...array_map(self::get(...), $keys)]);
    }

    /**
     * Maps a URL segment back to the guard it belongs to.
     *
     * The {area} route parameter carries whatever the segment is configured
     * as, which is not necessarily the guard name.
     */
    public static function guardFor(string $segment): ?string
    {
        return match ($segment) {
            self::get('buyer') => 'buyer',
            self::get('seller') => 'seller',
            default => null,
        };
    }
}
