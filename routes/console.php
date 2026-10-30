<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * The provider publishes once a day. Twice daily gives a second chance
 * after an outage without pulling figures that have not moved, and
 * withoutOverlapping stops a slow fetch stacking.
 */
Schedule::command('currency:refresh')
    ->twiceDaily(1, 13)
    ->withoutOverlapping()
    ->runInBackground();

/*
 * The hammer has to fall somewhere. Without this a lot lapses: the page
 * says "ended", the winner is owed a stone and nothing records a sale.
 * Every minute, because an anti-snipe extension moves the close time.
 */
Schedule::command('auctions:close')
    ->everyMinute()
    ->withoutOverlapping();

/*
 * A closing seller's window is measured in days, so hourly is plenty —
 * and nothing is lost by running late: the account simply stays open
 * a little longer for buyers whose parcels are still on the way.
 */
Schedule::command('sellers:finish-closures')
    ->hourly()
    ->withoutOverlapping();
