<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Something on a staff page changed: a flag reviewed, a seller suspended,
 * a listing approved.
 *
 * Carries only the names of the areas, never the data. Every open admin
 * page that shows one of them re-reads itself from the server, so two
 * admins working the same queue see each other's decisions without a
 * reload — and the markup stays Blade's, like every other live region.
 */
class StaffBoardChanged implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;

    /** @param  list<string>  $areas */
    public function __construct(public array $areas) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('staff')];
    }

    public function broadcastAs(): string
    {
        return 'staff.changed';
    }

    /** @return array{areas: list<string>} */
    public function broadcastWith(): array
    {
        return ['areas' => $this->areas];
    }
}
