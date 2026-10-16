<?php

namespace App\Models;

use Database\Factories\SellerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $verified_on
 * @property list<string>|null $specialties
 */
class Seller extends Model
{
    /** @use HasFactory<SellerFactory> */
    use HasFactory;

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'specialties' => 'array',
            'verified_on' => 'date',
            'rating' => 'float',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * The login account that owns this storefront, once approved.
     *
     * @return BelongsTo<SellerAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(SellerAccount::class, 'seller_account_id');
    }

    /** @return HasMany<Stone, $this> */
    public function stones(): HasMany
    {
        return $this->hasMany(Stone::class);
    }

    /** @return HasMany<SellerReview, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(SellerReview::class);
    }
}
