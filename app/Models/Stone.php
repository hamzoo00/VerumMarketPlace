<?php

namespace App\Models;

use Database\Factories\StoneFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $report_date
 */
class Stone extends Model
{
    /** @use HasFactory<StoneFactory> */
    use HasFactory;

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'carat' => 'float',
            'untreated' => 'boolean',
            'report_date' => 'date',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<GemType, $this> */
    public function gemType(): BelongsTo
    {
        return $this->belongsTo(GemType::class);
    }

    /** @return BelongsTo<Seller, $this> */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    /** @return HasOne<AuctionLot, $this> */
    public function auctionLot(): HasOne
    {
        return $this->hasOne(AuctionLot::class);
    }

    /**
     * Fixed-price listings only (anything without an auction lot).
     *
     * @param  Builder<self>  $query
     */
    public function scopeForSale(Builder $query): void
    {
        $query->whereDoesntHave('auctionLot');
    }

    /** @param  Builder<self>  $query */
    public function scopeInAuction(Builder $query): void
    {
        $query->whereHas('auctionLot');
    }

    public function price(): ?Money
    {
        return $this->price_cents === null
            ? null
            : new Money($this->price_cents, $this->currency);
    }

    public function isLot(): bool
    {
        return $this->auctionLot !== null;
    }

    /** Photo files are named "<key>-<size>.webp"; some stones reuse another's. */
    public function photoKey(): string
    {
        return $this->photo ?? $this->gemType->slug;
    }

    public function imageUrl(string $size = 'card'): string
    {
        return asset("images/gems/{$this->photoKey()}-{$size}.webp");
    }

    /** Ruby has a larger master file used on detail pages. */
    public function largeImageUrl(): string
    {
        return $this->photoKey() === 'ruby'
            ? asset('images/gems/ruby-1024.webp')
            : $this->imageUrl('card');
    }

    public function caratLabel(): string
    {
        return number_format($this->carat, 2).' ct';
    }

    /** Short treatment wording for cards, where space is tight. */
    public function treatmentShort(): string
    {
        if ($this->untreated) {
            return __('Untreated');
        }

        return str_contains(mb_strtolower($this->treatment), 'heat')
            ? __('Heated')
            : __('Enhanced');
    }

    public function originLabel(): string
    {
        return "{$this->origin}, {$this->country}";
    }
}
