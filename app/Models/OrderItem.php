<?php

namespace App\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of an order.
 *
 * Title and price are copied here, so the line still reads correctly
 * after the listing is edited or deleted — which is why `listing_id` is
 * nullable rather than cascading.
 */
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    protected $guarded = [];

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Listing, $this> */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function unitPrice(): Money
    {
        return new Money((int) $this->unit_price_cents, $this->order->currency);
    }

    public function lineTotal(): Money
    {
        return new Money((int) $this->line_total_cents, $this->order->currency);
    }
}
