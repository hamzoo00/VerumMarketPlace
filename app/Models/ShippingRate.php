<?php

namespace App\Models;

use Database\Factories\ShippingRateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One postage option a seller offers: a service, a destination zone, a
 * price and the window the parcel should arrive in.
 *
 * Carriers are not integrated. A seller posting a small parcel quotes a
 * flat price and a transit window, which is how this trade actually works.
 *
 * @property int $price_cents
 * @property int|null $extra_item_cents
 */
class ShippingRate extends Model
{
    /** @use HasFactory<ShippingRateFactory> */
    use HasFactory;

    public const DESTINATION_DOMESTIC = 'domestic';

    public const DESTINATION_INTERNATIONAL = 'international';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'tracked' => 'boolean',
            'insured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Seller, $this> */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    /** @param  Builder<ShippingRate>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param  Builder<ShippingRate>  $query */
    public function scopeTo(Builder $query, string $destination): void
    {
        $query->where('destination', $destination);
    }

    public function price(string $currency = 'USD'): Money
    {
        return new Money($this->price_cents, $currency);
    }

    public function isFree(): bool
    {
        return $this->price_cents === 0;
    }

    public function serviceLabel(): string
    {
        return config("verum_shipping.services.{$this->service}.label", $this->service);
    }

    public function destinationLabel(): string
    {
        return config("verum_shipping.destinations.{$this->destination}", $this->destination);
    }

    /** "7–21 days", or "Same day" for a pickup. */
    public function transitLabel(): string
    {
        if ($this->transit_max_days === 0) {
            return __('Same day');
        }

        if ($this->transit_min_days === $this->transit_max_days) {
            return trans_choice('{1} :n day|[2,*] :n days', $this->transit_max_days, ['n' => $this->transit_max_days]);
        }

        return __(':min–:max days', [
            'min' => $this->transit_min_days,
            'max' => $this->transit_max_days,
        ]);
    }
}
