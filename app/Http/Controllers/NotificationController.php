<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsToActions;
use App\Models\Admin;
use App\Models\Buyer;
use App\Models\SellerAccount;
use App\Support\NotificationAudience;
use App\Support\NotificationFeed;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/**
 * The bell, for whichever area is asking.
 *
 * Each area declares its routes with `->defaults('audience', …)`, like
 * the inbox, so a buyer reads a buyer's notifications even with a seller
 * session open in the same browser. A notification is only ever looked
 * up through the signed-in account's own relation: an id from someone
 * else's bell is simply not found.
 */
class NotificationController extends Controller
{
    use RespondsToActions;

    public function index(Request $request): View
    {
        ['area' => $area, 'account' => $account] = $this->audience($request);

        return view('pages.notifications.index', [
            'viewer' => $area,
            'account' => $account,
            'notifications' => $account->notifications()
                ->latest()
                ->paginate(20)
                ->through(fn (DatabaseNotification $notification): array => NotificationFeed::present($notification, $area)),
            'unread' => $account->unreadNotifications()->count(),
            'routes' => NotificationFeed::routes($area),
        ]);
    }

    /**
     * The bell on its own, for the live refresh.
     *
     * Smaller than re-reading the whole page every time something arrives,
     * and rendered by the same component, so it cannot drift from it.
     */
    public function bell(Request $request): View
    {
        ['area' => $area, 'account' => $account] = $this->audience($request);

        return view('components.verum.notifications.bell', [
            'feed' => NotificationFeed::bell($area, $account),
        ]);
    }

    /** Open one: mark it read, then go where it points. */
    public function read(Request $request, string $notification): JsonResponse|RedirectResponse
    {
        ['area' => $area, 'account' => $account] = $this->audience($request);

        $row = $account->notifications()->whereKey($notification)->firstOrFail();
        $row->markAsRead();

        $target = NotificationFeed::safeUrl($row->data['url'] ?? null) ?? route($area.'.notifications');

        return $request->expectsJson()
            ? response()->json(['url' => $target])
            : redirect()->to($target);
    }

    public function readAll(Request $request): JsonResponse|RedirectResponse
    {
        ['account' => $account] = $this->audience($request);

        $account->unreadNotifications()->update(['read_at' => now()]);

        return $this->done($request, __('All caught up.'));
    }

    /** @return array{area: string, account: Buyer|SellerAccount|Admin} */
    private function audience(Request $request): array
    {
        $audience = NotificationAudience::current($request);

        abort_if($audience === null, 403);

        return $audience;
    }
}
