<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * A parcel moved: posted, at customs, out for delivery, delivered.
 *
 * Written by App\Actions\Shipping\TrackShipment, which knows what
 * happened; whether it also goes by email is decided there, from
 * config/verum_tracking.php, so a parcel scanned fifteen times in transit
 * does not send fifteen emails.
 *
 * The email's button goes straight to the carrier's tracking page —
 * someone reading it on a phone should not have to sign in first to see
 * where their parcel is. The bell's link stays on this site, as every
 * bell link must (NotificationFeed::safeUrl); for a courier scan it is
 * the order's own redirect to the carrier, so it still lands there.
 */
class ShipmentUpdate extends InAppNotification
{
    public function __construct(
        private readonly string $status,
        private readonly string $title,
        private readonly string $body,
        private readonly string $url,
        private readonly string $orderUrl,
        private readonly ?string $trackingUrl = null,
        private readonly bool $email = false,
    ) {
        parent::__construct();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return $this->email ? ['mail', 'database', 'broadcast'] : ['database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->greeting(__('Hello'))
            ->line($this->body);

        if ($this->trackingUrl !== null) {
            return $mail
                ->action(__('Track your parcel'), $this->trackingUrl)
                ->line(__('Every update, from the carrier and from the seller, is on your order: :url', ['url' => $this->orderUrl]))
                ->salutation(__('— the :app team', ['app' => config('app.name')]));
        }

        return $mail
            ->action(__('See the order'), $this->orderUrl)
            ->salutation(__('— the :app team', ['app' => config('app.name')]));
    }

    protected function inApp(object $notifiable): array
    {
        return [
            'type' => 'shipment.'.$this->status,
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'icon' => 'box',
        ];
    }
}
