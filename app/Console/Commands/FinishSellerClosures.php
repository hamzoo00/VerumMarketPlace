<?php

namespace App\Console\Commands;

use App\Actions\Sellers\DecideSellerStanding;
use App\Models\SellerAccount;
use Illuminate\Console\Command;
use Throwable;

/**
 * Closes seller accounts whose wind-down has run its course.
 *
 * A closing seller stays open while parcels are on the road and buyers
 * can still dispute them. This asks again for each one whose date has
 * come: a late delivery or an open case pushes it out, otherwise the
 * account closes and the seller is told.
 *
 * Safe to run repeatedly — an account already closed is not picked up.
 */
class FinishSellerClosures extends Command
{
    protected $signature = 'sellers:finish-closures';

    protected $description = 'Close seller accounts whose closing window has ended';

    public function handle(DecideSellerStanding $standing): int
    {
        $due = SellerAccount::query()
            ->whereNotNull('closing_started_at')
            ->whereNull('closed_at')
            ->where('closes_at', '<=', now())
            ->get();

        $closed = 0;

        foreach ($due as $account) {
            try {
                $closed += $standing->finishClosure($account) ? 1 : 0;
            } catch (Throwable $e) {
                // One account must not stop the sweep.
                $this->error("{$account->email}: {$e->getMessage()}");
            }
        }

        $this->info("{$closed} of {$due->count()} closing account(s) closed.");

        return self::SUCCESS;
    }
}
