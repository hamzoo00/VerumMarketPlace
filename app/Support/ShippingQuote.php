<?php

namespace App\Support;

use App\Models\Listing;
use App\Models\Money;
use App\Models\ShippingRate;
use Illuminate\Support\Collection;

/**
 * What a buyer would pay to have a listing posted to them.
 *
 * Pure decisions, no persistence — the same shape as AuctionRules. The
 * precedence is fixed and lives only here, so the listing page, the
 * seller preview and (later) checkout cannot disagree about a price.
 *
 * Order of precedence, strongest first:
 *   1. listing is `free`   → nothing to pay, whatever the seller's rates say
 *   2. listing is `custom` → one flat price to anywhere
 *   3. otherwise           → the seller's own rates for that zone
 */
final readonly class ShippingQuote
{
    /**
     * @param  Collection<int, ShippingRate>  $options  What the buyer may choose from.
     */
    private function __construct(
        public Collection $options,
        public ?Money $flat,
        public bool $free,
        public int $handlingDays,
        public ?Money $freeOver,
        public ?string $note,
    ) {}

    /**
     * @param  string|null  $destination  One zone, or null for every zone
     *                                    the seller posts to.
     */
    public static function for(Listing $listing, ?string $destination = null): self
    {
        $seller = $listing->seller;
        $currency = $listing->currency;

        $handling = (int) ($seller->handling_days ?? config('verum_shipping.handling.default_days'));

        $freeOver = $seller?->free_shipping_over_cents === null
            ? null
            : new Money((int) $seller->free_shipping_over_cents, $currency);

        // A listing's own setting wins: the seller chose it for this item.
        if ($listing->shipping_mode === Listing::SHIPPING_FREE) {
            return new self(new Collection, null, true, $handling, $freeOver, $seller?->shipping_note);
        }

        if ($listing->shipping_mode === Listing::SHIPPING_CUSTOM) {
            $cents = (int) ($listing->shipping_price_cents ?? 0);

            return new self(
                new Collection,
                new Money($cents, $currency),
                $cents === 0,
                $handling,
                $freeOver,
                $seller?->shipping_note,
            );
        }

        // Without a zone, show everything the seller offers: a buyer needs
        // to see the price to their own country, and the page does not
        // know where they are.
        $options = $seller === null
            ? new Collection
            : $seller->shippingRates()
                ->active()
                ->when($destination, fn ($query, string $zone) => $query->to($zone))
                ->orderBy('destination')
                ->orderBy('price_cents')
                ->get();

        return new self($options, null, false, $handling, $freeOver, $seller?->shipping_note);
    }

    /**
     * The cheapest option, which is what a listing card advertises.
     *
     * Sorted by zone for display, so this sorts by price itself rather
     * than trusting the first row.
     */
    public function cheapest(): ?ShippingRate
    {
        return $this->options->sortBy('price_cents')->first();
    }

    /**
     * Options grouped by destination, so the page can label each zone.
     *
     * @return Collection<string, Collection<int, ShippingRate>>
     */
    public function byDestination(): Collection
    {
        return $this->options->groupBy('destination');
    }

    /** Has the seller said anything at all about postage? */
    public function isConfigured(): bool
    {
        return $this->free || $this->flat !== null || $this->options->isNotEmpty();
    }

    /** A short line for a listing page: "Free postage" or "from $12". */
    public function summary(): string
    {
        if ($this->free) {
            return __('Free postage');
        }

        if ($this->flat !== null) {
            return __('Postage :price', ['price' => $this->flat->format()]);
        }

        $cheapest = $this->cheapest();

        if ($cheapest === null) {
            return __('Postage quoted by the seller');
        }

        return $cheapest->isFree()
            ? __('Free postage')
            : __('Postage from :price', ['price' => $cheapest->price()->format()]);
    }

    /** "Dispatched within 2 working days" — the seller's own promise. */
    public function handlingLabel(): string
    {
        if ($this->handlingDays <= 0) {
            return __('Dispatched the same working day');
        }

        return trans_choice(
            '{1} Dispatched within :n working day|[2,*] Dispatched within :n working days',
            $this->handlingDays,
            ['n' => $this->handlingDays],
        );
    }
}
