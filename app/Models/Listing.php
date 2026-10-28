<?php

namespace App\Models;

use App\Observers\ListingObserver;
use App\Support\ShippingQuote;
use Database\Factories\ListingFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A seller-created listing.
 *
 * Sold either at a fixed price or at auction, never both — `listing_type`
 * decides which price columns apply.
 *
 * @property Carbon|null $auction_ends_at
 * @property Carbon|null $published_at
 * @property Carbon|null $approval_requested_at
 * @property Carbon|null $approved_at
 * @property Carbon|null $feature_requested_at
 * @property Carbon|null $featured_at
 * @property Carbon|null $featured_until
 */
#[ObservedBy(ListingObserver::class)]
class Listing extends Model implements HasMedia
{
    /** @use HasFactory<ListingFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use SoftDeletes;

    public const TYPE_FIXED = 'fixed';

    public const TYPE_AUCTION = 'auction';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SOLD = 'sold';

    public const STATUS_ENDED = 'ended';

    public const STATUS_PAUSED = 'paused';

    /** Postage: follow the seller's rates, free, or one flat price. */
    /*
     * Approval and featuring are each a small state machine an admin
     * drives, rather than a boolean — "who approved this, and when" is
     * a question a seller asks, and a flag cannot answer it.
     */
    public const APPROVAL_PENDING = 'pending';

    public const APPROVAL_APPROVED = 'approved';

    public const APPROVAL_REJECTED = 'rejected';

    public const FEATURE_REQUESTED = 'requested';

    public const FEATURE_ACTIVE = 'active';

    public const FEATURE_DECLINED = 'declined';

    public const SHIPPING_INHERIT = 'inherit';

    public const SHIPPING_FREE = 'free';

    public const SHIPPING_CUSTOM = 'custom';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'carat' => 'float',
            'untreated' => 'boolean',
            'report_date' => 'date',
            'auction_ends_at' => 'datetime',
            'published_at' => 'datetime',
            'approval_requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'feature_requested_at' => 'datetime',
            'featured_at' => 'datetime',
            'featured_until' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Media collections.
     *
     * Files live on the configured disk (S3 in production); only the
     * reference is stored in the database.
     */
    public function registerMediaCollections(): void
    {
        $images = config('verum_media.images');
        $videos = config('verum_media.videos');

        $this->addMediaCollection($images['collection'])
            ->acceptsMimeTypes($images['mimes'])
            ->useDisk(config('verum_media.disk'));

        $this->addMediaCollection($videos['collection'])
            ->acceptsMimeTypes($videos['mimes'])
            ->useDisk(config('verum_media.disk'));
    }

    /** Thumbnails are generated for images only; video needs ffmpeg. */
    public function registerMediaConversions(?Media $media = null): void
    {
        // Conversion's own methods must come before the image ones:
        // width() returns an ImageDriver and ends the Conversion chain.
        // Generated inline, because the upload response hands the browser
        // the thumb URL immediately.
        $this->addMediaConversion('thumb')
            ->performOnCollections(config('verum_media.images.collection'))
            ->nonQueued()
            ->width(400)
            ->height(400);
    }

    /** @return Collection<int, Media> */
    public function images(): Collection
    {
        return $this->getMedia(config('verum_media.images.collection'));
    }

    /** @return Collection<int, Media> */
    public function videos(): Collection
    {
        return $this->getMedia(config('verum_media.videos.collection'));
    }

    /** The image a card shows, or null to fall back to family artwork. */
    public function coverUrl(string $conversion = 'thumb'): ?string
    {
        $first = $this->images()->first();

        if ($first === null) {
            return null;
        }

        return $first->hasGeneratedConversion($conversion)
            ? $first->getUrl($conversion)
            : $first->getUrl();
    }

    /**
     * Every uploaded photograph, at one conversion, in upload order.
     *
     * Cards rotate through these and the detail gallery pages them, so
     * both read the same list rather than each deciding what "first" means.
     *
     * @return list<string>
     */
    public function imageUrls(string $conversion = 'thumb'): array
    {
        return array_values(
            $this->images()
                ->map(fn (Media $media): string => $media->hasGeneratedConversion($conversion)
                    ? $media->getUrl($conversion)
                    : $media->getUrl())
                ->all()
        );
    }

    /**
     * What posting this item would cost a buyer.
     *
     * The precedence between a listing's own setting and the seller's
     * rates lives in ShippingQuote, so nothing else has to know it.
     */
    /** @param  string|null  $destination  Null shows every zone the seller posts to. */
    public function shipping(?string $destination = null): ShippingQuote
    {
        return ShippingQuote::for($this, $destination);
    }

    /** @return BelongsTo<Seller, $this> */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    /** @return HasMany<SellerReview, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(SellerReview::class);
    }

    /** @return HasMany<Offer, $this> */
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    /** @return HasMany<Bid, $this> */
    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class)->latest('amount_cents');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Publicly visible: active, published, and not awaiting approval.
     *
     * The approval check lives *here* rather than in each page, because
     * every public query already goes through this scope — adding it per
     * controller would leave whichever one was forgotten leaking drafts.
     *
     * `approval_state` null means the listing predates the rule, so it
     * stays visible: switching approval on must not blank the catalogue.
     *
     * @param  Builder<self>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::STATUS_ACTIVE)
            ->whereNotNull('published_at')
            ->where(fn (Builder $inner) => $inner
                ->whereNull('approval_state')
                ->orWhere('approval_state', self::APPROVAL_APPROVED));
    }

    /**
     * Featured, and still within its run.
     *
     * An expiry in the past ends the slot with no sweep, like a lapsed
     * offer.
     *
     * @param  Builder<self>  $query
     */
    public function scopeFeatured(Builder $query): void
    {
        $query->published()
            ->where('feature_state', self::FEATURE_ACTIVE)
            ->whereNotNull('featured_at')
            ->where(fn (Builder $inner) => $inner
                ->whereNull('featured_until')
                ->orWhere('featured_until', '>', now()));
    }

    /**
     * Waiting on an admin.
     *
     * @param  Builder<self>  $query
     */
    public function scopeAwaitingApproval(Builder $query): void
    {
        $query->where('approval_state', self::APPROVAL_PENDING);
    }

    /** @param  Builder<self>  $query */
    public function scopeAwaitingFeature(Builder $query): void
    {
        $query->where('feature_state', self::FEATURE_REQUESTED);
    }

    public function isAwaitingApproval(): bool
    {
        return $this->approval_state === self::APPROVAL_PENDING;
    }

    public function wasRejected(): bool
    {
        return $this->approval_state === self::APPROVAL_REJECTED;
    }

    /** Live in a feature slot right now. */
    public function isFeatured(): bool
    {
        return $this->feature_state === self::FEATURE_ACTIVE
            && $this->featured_at !== null
            && ($this->featured_until === null || $this->featured_until->isFuture());
    }

    public function hasRequestedFeature(): bool
    {
        return $this->feature_state === self::FEATURE_REQUESTED;
    }

    /** @param  Builder<self>  $query */
    public function scopeDrafts(Builder $query): void
    {
        $query->where('status', self::STATUS_DRAFT);
    }

    /** @param  Builder<self>  $query */
    public function scopeAuctions(Builder $query): void
    {
        $query->where('listing_type', self::TYPE_AUCTION);
    }

    public function isAuction(): bool
    {
        return $this->listing_type === self::TYPE_AUCTION;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->published_at !== null;
    }

    public function hasEnded(): bool
    {
        return $this->isAuction()
            && $this->auction_ends_at !== null
            && $this->auction_ends_at->isPast();
    }

    /** The leading bid, or the opening price before any bid. */
    public function currentBid(): ?Money
    {
        $cents = $this->current_bid_cents ?? $this->start_price_cents;

        return $cents === null ? null : new Money($cents, $this->currency);
    }

    /** What a buyer pays, or the current bid for an auction. */
    public function price(): ?Money
    {
        $cents = $this->isAuction()
            ? ($this->current_bid_cents ?? $this->start_price_cents)
            : $this->price_cents;

        return $cents === null ? null : new Money($cents, $this->currency);
    }

    public function reservePrice(): ?Money
    {
        return $this->reserve_price_cents === null
            ? null
            : new Money($this->reserve_price_cents, $this->currency);
    }

    public function caratLabel(): ?string
    {
        return $this->carat === null ? null : number_format($this->carat, 2).' ct';
    }

    /** @return array<string, string> */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => __('Draft'),
            self::STATUS_ACTIVE => __('Live'),
            self::STATUS_PAUSED => __('Paused'),
            self::STATUS_SOLD => __('Sold'),
            self::STATUS_ENDED => __('Ended'),
        ];
    }

    /** @return array<string, string> */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_FIXED => __('Fixed price'),
            self::TYPE_AUCTION => __('Auction'),
        ];
    }
}
