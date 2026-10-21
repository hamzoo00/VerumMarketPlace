<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One use of a coupon by one buyer.
 *
 * Kept as rows rather than a counter alone, so a per-buyer limit can be
 * enforced and a campaign's take can be reconstructed.
 */
class CouponRedemption extends Model
{
    protected $guarded = [];

    /** @return BelongsTo<Coupon, $this> */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /** @return BelongsTo<Buyer, $this> */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }
}
