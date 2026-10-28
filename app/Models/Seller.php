<?php

namespace App\Models;

use Database\Factories\SellerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property Carbon|null $verified_on
 * @property Carbon|null $premium_since
 * @property Carbon|null $premium_until
 * @property Carbon|null $certified_at
 * @property list<string>|null $specialties
 */
class Seller extends Model implements HasMedia
{
    /** @use HasFactory<SellerFactory> */
    use HasFactory;

    use InteractsWithMedia;

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'specialties' => 'array',
            'verified_on' => 'date',
            'rating' => 'float',
            'premium_since' => 'datetime',
            'premium_until' => 'datetime',
            'certified_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Admin, $this> */
    public function certifier(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'certified_by');
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

    /**
     * A seller's own branding.
     *
     * Single-file collections: uploading a new avatar replaces the old
     * one rather than stacking, which is what a seller expects.
     */
    public function registerMediaCollections(): void
    {
        foreach (['avatar', 'banner'] as $kind) {
            $config = config("verum_media.{$kind}");

            $this->addMediaCollection($config['collection'])
                ->acceptsMimeTypes($config['mimes'])
                ->useDisk(config('verum_media.disk'))
                ->singleFile();
        }
    }

    /** Conversions run inline so the upload response can show the result. */
    public function registerMediaConversions(?Media $media = null): void
    {
        // Conversion methods must precede the image ones: width() returns
        // an ImageDriver and ends the Conversion chain.
        $this->addMediaConversion('thumb')
            ->performOnCollections(config('verum_media.avatar.collection'))
            ->nonQueued()
            ->width(240)
            ->height(240);

        $this->addMediaConversion('wide')
            ->performOnCollections(config('verum_media.banner.collection'))
            ->nonQueued()
            ->width(1600)
            ->height(500);
    }

    public function avatarUrl(): ?string
    {
        return $this->brandingUrl('avatar', 'thumb');
    }

    public function bannerUrl(): ?string
    {
        return $this->brandingUrl('banner', 'wide');
    }

    private function brandingUrl(string $kind, string $conversion): ?string
    {
        $media = $this->getFirstMedia(config("verum_media.{$kind}.collection"));

        if ($media === null) {
            return null;
        }

        return $media->hasGeneratedConversion($conversion)
            ? $media->getUrl($conversion)
            : $media->getUrl();
    }

    /** @return HasMany<Listing, $this> */
    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    /** @return HasMany<Coupon, $this> */
    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    /** @return HasMany<Offer, $this> */
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    /** @return HasMany<ShippingRate, $this> */
    public function shippingRates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }

    /** Does this seller post anywhere outside their own country? */
    public function shipsInternationally(): bool
    {
        return $this->shippingRates()
            ->active()
            ->to(ShippingRate::DESTINATION_INTERNATIONAL)
            ->exists();
    }
}
