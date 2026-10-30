<?php

namespace App\View\Composers;

use App\Support\NotificationAudience;
use App\Support\NotificationFeed;
use Illuminate\Contracts\View\View;

/**
 * Hands the bell its feed, so no layout has to know how it is built.
 *
 * Skipped when the feed was passed in — the live-refresh endpoint does
 * that — and null for a guest, in which case the bell renders nothing.
 */
class NotificationBellComposer
{
    public function compose(View $view): void
    {
        if (array_key_exists('feed', $view->getData())) {
            return;
        }

        $audience = NotificationAudience::current();

        $view->with('feed', $audience === null
            ? null
            : NotificationFeed::bell($audience['area'], $audience['account']));
    }
}
