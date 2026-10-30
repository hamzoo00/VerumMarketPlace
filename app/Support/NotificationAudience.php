<?php

namespace App\Support;

use App\Models\Admin;
use App\Models\Buyer;
use App\Models\SellerAccount;
use Illuminate\Http\Request;

/**
 * Whose bell this page shows.
 *
 * Guards stack in one browser, so "who is signed in" has several answers.
 * The page decides, the same way the inbox does:
 *
 *  - a route that declares an audience (the notification routes) means it;
 *  - inside an area, the area's own account — a seller page shows the
 *    seller's bell even when a buyer session is also open;
 *  - on the public site, the account the header's menu shows: buyer
 *    first, then seller. Staff read theirs in the admin shell.
 */
final class NotificationAudience
{
    public const AREAS = ['buyer', 'seller', 'admin'];

    /** @return array{area: string, account: Buyer|SellerAccount|Admin}|null */
    public static function current(?Request $request = null): ?array
    {
        $request ??= request();
        $route = $request->route();

        $declared = $route?->defaults['audience'] ?? null;
        $name = (string) $route?->getName();

        $area = $declared ?? collect(self::AREAS)
            ->first(fn (string $candidate): bool => str_starts_with($name, $candidate.'.'));

        if ($area !== null) {
            return self::signedIn($request, $area);
        }

        return self::signedIn($request, 'buyer') ?? self::signedIn($request, 'seller');
    }

    /** @return array{area: string, account: Buyer|SellerAccount|Admin}|null */
    private static function signedIn(Request $request, string $area): ?array
    {
        if (! in_array($area, self::AREAS, true)) {
            return null;
        }

        $account = $request->user($area);

        if (! $account instanceof Buyer && ! $account instanceof SellerAccount && ! $account instanceof Admin) {
            return null;
        }

        return ['area' => $area, 'account' => $account];
    }
}
