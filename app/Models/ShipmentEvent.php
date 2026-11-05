<?php

namespace App\Models;

use App\Support\ShipmentStatuses;
use Database\Factories\ShipmentEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One step of a parcel's journey: a courier's scan or a seller's update.
 *
 * Append-only, like Bid and Message — written once by
 * App\Actions\Shipping\TrackShipment and never edited, because this is
 * the record a "never arrived" dispute is decided on.
 *
 * @property Carbon $occurred_at
 * @property Carbon|null $created_at
 */
class ShipmentEvent extends Model
{
    /** @use HasFactory<ShipmentEventFactory> */
    use HasFactory;

    public const SOURCE_COURIER = 'courier';

    public const SOURCE_SELLER = 'seller';

    public const UPDATED_AT = null;

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function fromCourier(): bool
    {
        return $this->source === self::SOURCE_COURIER;
    }

    public function statusLabel(): string
    {
        return ShipmentStatuses::label($this->status);
    }

    public function tone(): ?string
    {
        return ShipmentStatuses::tone($this->status);
    }
}
