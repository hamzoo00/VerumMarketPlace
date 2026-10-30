<?php

namespace App\Events;

use App\Models\Listing;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A listing appeared, changed or left the public catalogue.
 *
 * Fired from the model's lifecycle rather than from controllers, so every
 * write path — form, publish toggle, media upload, delete, console command —
 * announces itself without having to remember to.
 *
 * Deliberately **not** SerializesModels: the payload is built up front and
 * the event carries plain data. A queued job that stored only an id could
 * not rehydrate a deleted listing, and Laravel discards such a job silently,
 * so the "it disappeared" message would be the one that never arrived.
 */
class ListingChanged implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;

    public const PUT = 'put';

    public const DROP = 'drop';

    public const TOUCH = 'touch';

    /** @param  array<string, mixed>  $payload */
    private function __construct(
        public array $payload,
        public int $sellerId,
        /** False for a change only the seller's workspace cares about. */
        private readonly bool $public = true,
    ) {}

    /** A listing that is now publicly visible, with its rendered card. */
    public static function put(Listing $listing): self
    {
        $listing->loadMissing(['category', 'seller']);

        return new self([
            'action' => self::PUT,
            'slug' => $listing->slug,
            'kind' => $listing->category?->kind,
            'category' => $listing->category?->slug,
            'type' => $listing->listing_type,
            'untreated' => (bool) $listing->untreated,
            // The same Blade component the grid renders: a live card is
            // identical to a reloaded one, and markup stays server-side.
            'card' => view('components.verum.listing.card', [
                'listing' => $listing,
                'index' => 0,
            ])->render(),
        ], (int) $listing->seller_id);
    }

    /** A listing that should disappear. Needs no model, only its slug. */
    public static function drop(Listing $listing): self
    {
        return new self([
            'action' => self::DROP,
            'slug' => $listing->slug,
            'card' => null,
        ], (int) $listing->seller_id);
    }

    /**
     * Something changed that no buyer can see — a listing approved into
     * nothing yet, rejected, queued — but its seller's inventory shows.
     *
     * Sent to the seller's channel only. On the public one it would make
     * every open shop grid re-query for a stone it never displayed.
     */
    public static function touch(Listing $listing): self
    {
        return new self([
            'action' => self::TOUCH,
            'slug' => $listing->slug,
            'card' => null,
        ], (int) $listing->seller_id, public: false);
    }

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return array_values(array_filter([
            // Everyone browsing the catalogue.
            $this->public ? new Channel('listings') : null,
            // The seller's own workspace, so their inventory list keeps up.
            new Channel('seller.'.$this->sellerId.'.listings'),
        ]));
    }

    public function broadcastAs(): string
    {
        return 'listing.changed';
    }

    /**
     * The channel is unauthenticated, so nothing private may appear here.
     * Prices and photographs are already public on the page.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
