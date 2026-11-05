<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * One attempt to move money through PayPal.
 *
 * Written by the Actions\Payments classes only. A buyer who abandons
 * PayPal and tries again makes a second row, so an attempt is never
 * overwritten by a later one; amounts come from what PayPal confirmed.
 *
 * Premium payments have no order, so the relation can be empty.
 *
 * @property-read Order|null $order
 * @property-read Seller|null $seller
 * @property Carbon|null $completed_at
 * @property Carbon|null $disputed_at
 * @property Carbon|null $tracking_sent_at
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    public const PURPOSE_ORDER = 'order';

    public const PURPOSE_PREMIUM = 'premium';

    public const STATUS_CREATED = 'created';

    /** PayPal accepted it but has not released the money yet. */
    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';

    /** Taken back by PayPal, usually a chargeback. */
    public const STATUS_REVERSED = 'reversed';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (Payment $payment): void {
            $payment->uuid ??= (string) Str::uuid();
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'disputed_at' => 'datetime',
            'tracking_sent_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Seller, $this> */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    /** @return BelongsTo<Buyer, $this> */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function amount(): Money
    {
        return new Money((int) $this->amount_cents, $this->currency);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /** @return array<string, string> */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_CREATED => __('Started'),
            self::STATUS_PENDING => __('Clearing'),
            self::STATUS_COMPLETED => __('Paid'),
            self::STATUS_FAILED => __('Not paid'),
            self::STATUS_REFUNDED => __('Refunded'),
            self::STATUS_PARTIALLY_REFUNDED => __('Part refunded'),
            self::STATUS_REVERSED => __('Reversed'),
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    /** What reached the seller: the payment less the platform's cut. */
    public function net(): Money
    {
        return new Money(max(0, (int) $this->amount_cents - (int) $this->fee_cents), $this->currency);
    }

    public function fee(): Money
    {
        return new Money((int) $this->fee_cents, $this->currency);
    }

    /** Already settled one way or the other — nothing more to capture. */
    public function isSettled(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_REFUNDED,
            self::STATUS_PARTIALLY_REFUNDED,
            self::STATUS_REVERSED,
        ], true);
    }
}
