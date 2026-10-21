<?php

namespace App\Events;

use App\Models\Offer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An offer was made, answered or lapsed.
 *
 * On **private** channels, unlike ListingChanged: a negotiation is
 * between two parties and the amounts are nobody else's business.
 *
 * Carries plain data, never the model — a queued job holding only an id
 * cannot rehydrate a deleted row, and Laravel discards it silently.
 */
class OfferChanged implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;

    /** @param  array<string, mixed>  $payload */
    private function __construct(
        public array $payload,
        public int $buyerId,
        public int $sellerId,
    ) {}

    public static function for(Offer $offer): self
    {
        return new self([
            'id' => $offer->id,
            'thread' => $offer->thread_id,
            'listing' => $offer->listing?->slug,
            'status' => $offer->status,
            'from' => $offer->from,
            'awaiting' => $offer->awaiting(),
            // The figure is safe here: both parties already know it, and
            // the channels are private to exactly those two.
            'amount' => $offer->amount()->format(),
        ], (int) $offer->buyer_id, (int) $offer->seller_id);
    }

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('buyer.'.$this->buyerId.'.offers'),
            new PrivateChannel('seller.'.$this->sellerId.'.offers'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'offer.changed';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
