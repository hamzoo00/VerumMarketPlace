<?php

namespace App\Models;

use Database\Factories\BidFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A bid on an auction listing.
 *
 * Append-only: never edited or deleted, so a lot's history always
 * reconstructs. `outbid_at` is stamped when a later bid overtakes it.
 *
 * @property Carbon|null $outbid_at
 */
class Bid extends Model
{
    /** @use HasFactory<BidFactory> */
    use HasFactory;

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'outbid_at' => 'datetime',
            'extended_auction' => 'boolean',
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

    /** @param  Builder<self>  $query */
    public function scopeLeading(Builder $query): void
    {
        $query->whereNull('outbid_at');
    }

    public function amount(): Money
    {
        return new Money($this->amount_cents, $this->currency);
    }

    public function isLeading(): bool
    {
        return $this->outbid_at === null;
    }
}
