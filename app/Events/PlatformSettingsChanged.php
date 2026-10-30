<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An admin saved a section of Settings, and sellers' pages may read
 * differently because of it — "Submit for approval" becomes "Publish",
 * a "Request feature" button comes or goes.
 *
 * Carries the section's name only, never the values: a page that shows
 * something from that section re-reads itself, so the wording stays
 * Blade's, like every other live region. StaffBoardChanged does the same
 * for admin pages.
 */
class PlatformSettingsChanged implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;

    public function __construct(public string $section) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('platform.sellers')];
    }

    public function broadcastAs(): string
    {
        return 'settings.changed';
    }

    /** @return array{sections: list<string>} */
    public function broadcastWith(): array
    {
        return ['sections' => [$this->section]];
    }
}
