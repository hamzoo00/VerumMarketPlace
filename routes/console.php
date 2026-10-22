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
