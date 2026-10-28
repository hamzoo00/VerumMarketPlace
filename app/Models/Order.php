<?php

namespace App\Models;

use App\Support\Countries;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * One sale, from one seller to one buyer.
 *
 * Every figure here was copied at the moment of sale and is never read
 * back through the listing: a seller may reprice or delete tomorrow, and
 * what the buyer agreed to has to survive that. This row is what a
 * dispute is argued over.
 *
 * @property Carbon|null $paid_at
 * @property Carbon|null $dispatched_at
 */
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    public const ORIGIN_CHECKOUT = 'checkout';

    public const ORIGIN_AUCTION = 'auction';

    public const ORIGIN_OFFER = 'offer';

    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';

    public const STATUS_PAID = 'paid';

    public const STATUS_DISPATCHED = 'dispatched';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELLED = 'cancelled';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Buyer, $this> */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    /** @return BelongsTo<Seller, $this> */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return BelongsTo<Coupon, $this> */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function subtotal(): Money
    {
        return new Money((int) $this->subtotal_cents, $this->currency);
    }

    public function shipping(): Money
    {
        return new Money((int) $this->shipping_cents, $this->currency);
    }

    public function discount(): Money
    {
        return new Money((int) $this->discount_cents, $this->currency);
    }

    public function total(): Money
    {
        return new Money((int) $this->total_cents, $this->currency);
    }

    /** The platform's cut, as recorded when the sale happened. */
    public function commission(): Money
    {
        return new Money((int) $this->commission_cents, $this->currency);
    }

    /**
     * What reaches the seller: the total less the platform's cut.
     *
     * Derived rather than stored, because it is exactly those two
     * figures and a third column could only ever disagree with them.
     */
    public function sellerReceives(): Money
    {
        return new Money(
            max(0, (int) $this->total_cents - (int) $this->commission_cents),
            $this->currency,
        );
    }

    /**
     * Statuses a seller may still act on.
     *
     * @param  Builder<Order>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', [
            self::STATUS_AWAITING_PAYMENT,
            self::STATUS_PAID,
            self::STATUS_DISPATCHED,
        ]);
    }

    /**
     * Paid but not yet posted — the seller's actual to-do list.
     *
     * @param  Builder<Order>  $query
     */
    public function scopeToShip(Builder $query): void
    {
        $query->where('status', self::STATUS_PAID);
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * May the seller still cancel this?
     *
     * Once it is in the post the answer is no: a cancellation after
     * dispatch is a return, which is a different conversation and a
     * different record.
     */
    public function isCancellable(): bool
    {
        return in_array($this->status, [
            self::STATUS_AWAITING_PAYMENT,
            self::STATUS_PAID,
        ], true);
    }

    /** The address as a parcel label reads it. */
    public function addressLines(): string
    {
        return collect([
            $this->ship_to_name,
            $this->ship_to_line1,
            $this->ship_to_line2,
            $this->ship_to_city,
            $this->ship_to_region,
            $this->ship_to_postcode,
            Countries::name($this->ship_to_country) ?? $this->ship_to_country,
        ])->filter()->implode("\n");
    }

    /** A human reference both sides can quote: VRM-2026-A1B2C3. */
    public static function newReference(): string
    {
        return 'VRM-'.now()->year.'-'.Str::upper(Str::random(6));
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    /** @return array<string, string> */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_AWAITING_PAYMENT => __('Awaiting payment'),
            self::STATUS_PAID => __('Paid'),
            self::STATUS_DISPATCHED => __('Dispatched'),
            self::STATUS_DELIVERED => __('Delivered'),
            self::STATUS_CANCELLED => __('Cancelled'),
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }
}
