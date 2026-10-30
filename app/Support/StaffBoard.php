<?php

namespace App\Support;

use App\Events\StaffBoardChanged;
use Illuminate\Support\Facades\DB;

/**
 * Tells open admin pages which areas to re-read.
 *
 * Deferred until the transaction commits, like ListingObserver's events:
 * a page that refreshes on the signal must find the new row already there.
 */
final class StaffBoard
{
    public const MODERATION = 'moderation';

    public const SELLERS = 'sellers';

    public const BUYERS = 'buyers';

    public const APPROVALS = 'approvals';

    public const FEATURES = 'features';

    public const PREMIUM = 'premium';

    public const APPLICATIONS = 'applications';

    public const CASES = 'cases';

    public const SETTINGS = 'settings';

    public static function changed(string ...$areas): void
    {
        $areas = array_values(array_unique($areas));

        DB::connection()->afterCommit(fn () => event(new StaffBoardChanged($areas)));
    }
}
