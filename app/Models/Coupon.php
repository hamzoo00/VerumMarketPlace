<?php

namespace App\Models;

use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A seller's discount code.
 *
 * Scoped to one seller, so two sellers may both run "WELCOME10".
 *
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 */
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    public const TYPE_PERCENT = 'percent';

    public const TYPE_FIXED = 'fixed';

    public const TYPE_FREE_SHIPPING = 'free_shipping';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Seller, $this> */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    /** @return HasMany<CouponRedemption, $this> */
    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    /**
     * Codes a buyer could actually use right now.
     *
     * The window is checked in SQL so an expired code is never a
     * candidate, whatever else has or has not run.
     *
     * @param  Builder<Coupon>  $query
     */
    public function scopeUsable(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn ($inner) => $inner->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($inner) => $inner->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    /** Codes are stored and compared upper-cased. */
    public function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = mb_strtoupper(trim($value));
    }

    public function typeLabel(): string
    {
        return config("verum_offers.coupons.types.{$this->type}", $this->type);
    }

    /** "15% off", "$20 off" or "Free postage". */
    public function valueLabel(string $currency = 'USD'): string
    {
        return match ($this->type) {
            self::TYPE_PERCENT => __(':n% off', ['n' => $this->percent]),
            self::TYPE_FIXED => __(':amount off', [
                'amount' => (new Money((int) $this->amount_cents, $currency))->format(),
            ]),
            default => __('Free postage'),
        };
    }

    public function isExhausted(): bool
    {
        return $this->max_redemptions !== null
            && $this->used_count >= $this->max_redemptions;
    }

    public function isLive(): bool
    {
        if (! $this->is_active || $this->isExhausted()) {
            return false;
        }

        if ($this->starts_at !== null && $this->starts_at->isFuture()) {
            return false;
        }

        return $this->ends_at === null || $this->ends_at->isFuture();
    }
}
