<?php

namespace App\Notifications;

/**
 * Something an admin should look at: a new application, a flagged
 * message, a case, a listing waiting for approval.
 *
 * Bell only, never email: these arrive in bursts, and a queue that mails
 * every admin for every flag teaches them to filter the mail away.
 */
class StaffAlert extends InAppNotification
{
    public function __construct(
        private readonly string $type,
        private readonly string $title,
        private readonly string $body,
        private readonly string $url,
        private readonly string $icon = 'flame',
    ) {
        parent::__construct();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    protected function inApp(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'icon' => $this->icon,
        ];
    }
}
