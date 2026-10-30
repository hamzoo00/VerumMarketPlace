<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * A notification that appears under the bell.
 *
 * Fixes the one shape the bell reads — type, title, body, url, icon —
 * so every notification renders the same way and none can smuggle a
 * field the view does not expect. Stored for the bell and pushed over the
 * account's private channel, so the count moves without a reload.
 *
 * Queued and dispatched after commit: a notification that arrives before
 * the row it describes is visible would be read as wrong.
 */
abstract class InAppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->afterCommit();
    }

    /**
     * What the bell shows.
     *
     * @return array{type: string, title: string, body: string, url: string|null, icon: string}
     */
    abstract protected function inApp(object $notifiable): array;

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    /** @return array{type: string, title: string, body: string, url: string|null, icon: string} */
    public function toArray(object $notifiable): array
    {
        return $this->inApp($notifiable);
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->inApp($notifiable));
    }
}
