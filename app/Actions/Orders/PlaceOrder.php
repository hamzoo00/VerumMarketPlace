<?php

namespace App\Actions\Orders;

use App\Models\Buyer;
use App\Models\Coupon;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Seller;
use App\Support\CouponQuote;
use App\Support\ShippingQuote;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Turns an agreed sale into a record.
 *
 * The only place an order is created, so checkout, a won auction and an
 * accepted offer cannot disagree about what a sale looks like. Three
 * things hold it together:
 *
 * - **Every figure is recomputed here**, from the listing row and the
 *   pure quote classes. Nothing the browser sends decides a price, so a
 *   crafted form cannot buy a stone for a dollar.
 * - **Everything is copied, not referenced.** Title, unit price,
 *   postage and the address are written into the order, because the
 *   listing may be edited or deleted and the buyer may move house.
 * - **The listing is locked and marked sold inside the transaction**,
 *   so two buyers racing for the last one cannot both win it.
 *
 * Payment is not taken: an order starts `awaiting_payment` and a human
 * marks it paid. That is honest about what the platform currently does.
 *
 * @phpstan-type Address array{name: string, line1: string, line2?: string|null, city: string, region?: string|null, postcode?: string|null, country: string, phone?: string|null}
 */
class PlaceOrder
{
    /**
     * @param  Address  $address
     */
    public function handle(
        Buyer $buyer,
        Listing $listing,
        array $address,
        string $origin = Order::ORIGIN_CHECKOUT,
        ?string $shippingService = null,
        ?string $couponCode = null,
        ?string $note = null,
        ?int $agreedPriceCents = null,
    ): Order {
        return DB::transaction(function () use (
            $buyer, $listing, $address, $origin, $shippingService, $couponCode, $note, $agreedPriceCents
        ): Order {
            // Locked for the rest of the transaction: two buyers racing
            // for the last stone must not both get it.
            $listing = Listing::query()->lockForUpdate()->findOrFail($listing->id);

            $seller = $listing->seller;

            if ($seller === null) {
                throw new RuntimeException('That listing has no storefront.');
            }

            $this->assertAvailable($listing, $origin);

            $currency = $listing->currency;
            $unit = $this->unitPrice($listing, $origin, $agreedPriceCents);

            $shipping = $this->shippingFor($listing, $shippingService);
            $coupon = $this->couponFor($seller, $buyer, $couponCode, $unit, $currency);

            // A free-postage code zeroes the carriage rather than the
            // item, so it has to land after the quote is computed.
            if ($coupon !== null && $coupon['free_shipping']) {
                $shipping['cents'] = 0;
            }

            $discount = $coupon === null ? 0 : $coupon['cents'];
            $total = max(0, $unit + $shipping['cents'] - $discount);

            $order = Order::create([
                'reference' => Order::newReference(),
                'buyer_id' => $buyer->id,
                'seller_id' => $seller->id,
                'origin' => $origin,
                'status' => Order::STATUS_AWAITING_PAYMENT,
                'currency' => $currency,
                'subtotal_cents' => $unit,
                'shipping_cents' => $shipping['cents'],
                'discount_cents' => $discount,
                'total_cents' => $total,
                'coupon_id' => $coupon['id'] ?? null,
                'coupon_code' => $coupon['code'] ?? null,
                'shipping_service' => $shipping['service'],
                'shipping_destination' => $shipping['destination'],
                'handling_days' => $shipping['handling'],
                'ship_to_name' => $address['name'],
                'ship_to_line1' => $address['line1'],
                'ship_to_line2' => $address['line2'] ?? null,
                'ship_to_city' => $address['city'],
                'ship_to_region' => $address['region'] ?? null,
                'ship_to_postcode' => $address['postcode'] ?? null,
                'ship_to_country' => strtoupper($address['country']),
                'ship_to_phone' => $address['phone'] ?? null,
                'buyer_note' => $note,
            ]);

            $order->items()->create([
                'listing_id' => $listing->id,
                'title' => $listing->title,
                'slug' => $listing->slug,
                'unit_price_cents' => $unit,
                'quantity' => 1,
                'line_total_cents' => $unit,
            ]);

            // Off the market in the same transaction, so the lock means
            // something. The observer broadcasts the change from here.
            $listing->update(['status' => Listing::STATUS_SOLD]);

            return $order->load('items');
        });
    }

    /**
     * Is this listing still buyable, by this route?
     *
     * An auction win and an accepted offer arrive *after* the lot has
     * closed, so they are allowed past the "still active" rule — the
     * right to buy was earned before this ran.
     */
    private function assertAvailable(Listing $listing, string $origin): void
    {
        if ($listing->status === Listing::STATUS_SOLD) {
            throw new RuntimeException('That item is already sold.');
        }

        if ($origin !== Order::ORIGIN_CHECKOUT) {
            return;
        }

        if ($listing->status !== Listing::STATUS_ACTIVE || $listing->published_at === null) {
            throw new RuntimeException('That listing is not on sale.');
        }

        if ($listing->isAuction()) {
            throw new RuntimeException('An auction lot is bought by bidding.');
        }
    }

    /**
     * What the buyer pays for the item itself.
     *
     * A hammer price or an accepted offer is the agreed figure and is
     * passed in; anything else is read from the listing, never from the
     * request.
     */
    private function unitPrice(Listing $listing, string $origin, ?int $agreed): int
    {
        if ($origin !== Order::ORIGIN_CHECKOUT && $agreed !== null) {
            return max(0, $agreed);
        }

        $price = (int) ($listing->price_cents ?? 0);

        if ($price <= 0) {
            throw new RuntimeException('That listing has no price.');
        }

        return $price;
    }

    /**
     * Postage, recomputed from the seller's own rates.
     *
     * @return array{cents: int, service: string|null, destination: string|null, handling: int}
     */
    private function shippingFor(Listing $listing, ?string $service): array
    {
        $quote = ShippingQuote::for($listing);
        $handling = $quote->handlingDays;

        if ($quote->free) {
            return ['cents' => 0, 'service' => null, 'destination' => null, 'handling' => $handling];
        }

        if ($quote->flat !== null) {
            return [
                'cents' => $quote->flat->cents,
                'service' => null,
                'destination' => null,
                'handling' => $handling,
            ];
        }

        // The chosen rate is looked up by name in the seller's own table,
        // so a tampered value finds nothing rather than setting a price.
        $rate = $service === null
            ? $quote->cheapest()
            : $quote->options->firstWhere('service', $service);

        if ($rate === null) {
            $rate = $quote->cheapest();
        }

        return [
            'cents' => $rate === null ? 0 : (int) $rate->price_cents,
            'service' => $rate?->service,
            'destination' => $rate?->destination,
            'handling' => $handling,
        ];
    }

    /**
     * The discount, always recomputed from the coupon row.
     *
     * Never taken from the request: a browser sends a code, and what
     * that code is worth is decided here against the seller's own row.
     *
     * @return array{id: int, code: string, cents: int, free_shipping: bool}|null
     */
    private function couponFor(
        Seller $seller,
        Buyer $buyer,
        ?string $code,
        int $subtotalCents,
        string $currency,
    ): ?array {
        if ($code === null || trim($code) === '') {
            return null;
        }

        $coupon = Coupon::query()
            ->where('seller_id', $seller->id)
            ->where('code', strtoupper(trim($code)))
            ->first();

        if ($coupon === null) {
            return null;
        }

        // The per-buyer limit is only meaningful against a real count of
        // what this buyer already redeemed.
        $used = Order::query()
            ->where('buyer_id', $buyer->id)
            ->where('coupon_id', $coupon->id)
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->count();

        $quote = CouponQuote::apply($coupon, $subtotalCents, $used);

        if (! $quote->isValid()) {
            return null;
        }

        return [
            'id' => (int) $coupon->id,
            'code' => $coupon->code,
            'cents' => $quote->discount($currency)->cents,
            'free_shipping' => $quote->freeShipping,
        ];
    }
}
