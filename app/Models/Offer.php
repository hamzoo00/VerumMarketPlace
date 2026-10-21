<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One round of a price negotiation.
 *
 * Append-only: a counter is a new row in the same thread, never an edit.
 * The thread's highest round is the live offer; everything before it is
 * history.
 *
 * @property Carbon $expires_at
 * @property Carbon|null $responded_at
 */
class Offer extends Model
{
    public const FROM_BUYER = 'buyer';

    public const FROM_SELLER = 'seller';

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_COUNTERED = 'countered';

    public const STATUS_WITHDRAWN = 'withdrawn';

    public const STATUS_EXPIRED = 'expired';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Listing, $this> */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
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

    /**
     * Still awaiting an answer.
     *
     * Expiry is checked in SQL rather than by a scheduled sweep, so an
     * offer stops counting the moment it lapses even if nothing has run.
     *
     * @param  Builder<Offer>  $query
     */
    public function scopeLive(Builder $query): void
    {
        $query->where('status', self::STATUS_PENDING)
            ->where('expires_at', '>', now());
    }

    /** @param  Builder<Offer>  $query */
    public function scopeAwaiting(Builder $query, string $side): void
    {
        // An offer from the buyer is the seller's to answer, and vice versa.
        $query->live()->where('from', $side === self::FROM_SELLER
            ? self::FROM_BUYER
            : self::FROM_SELLER);
    }

    public function amount(): Money
    {
        return new Money($this->amount_cents, $this->currency);
    }

    public function isLive(): bool
    {
        return $this->status === self::STATUS_PENDING && $this->expires_at->isFuture();
    }

    /** The side that must answer this offer. */
    public function awaiting(): string
    {
        return $this->from === self::FROM_BUYER ? self::FROM_SELLER : self::FROM_BUYER;
    }

    public function statusLabel(): string
    {
        if ($this->status === self::STATUS_PENDING && $this->expires_at->isPast()) {
            return __('Expired');
        }

        return match ($this->status) {
            self::STATUS_PENDING => __('Awaiting reply'),
            self::STATUS_ACCEPTED => __('Accepted'),
            self::STATUS_DECLINED => __('Declined'),
            self::STATUS_COUNTERED => __('Countered'),
            self::STATUS_WITHDRAWN => __('Withdrawn'),
            default => __('Expired'),
        };
    }
}
