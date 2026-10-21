<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * One message, append-only.
 *
 * Never edited, never deleted: a thread produced as evidence in a
 * dispute has to be the thread that happened. An "unsend" would empty
 * the record at exactly the moment it is needed.
 *
 * @property Carbon|null $read_at
 * @property list<string>|null $flags
 */
class Message extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const FROM_BUYER = 'buyer';

    public const FROM_SELLER = 'seller';

    public const FROM_ADMIN = 'admin';

    public const REVIEW_PENDING = 'pending';

    public const REVIEW_CLEARED = 'cleared';

    public const REVIEW_UPHELD = 'upheld';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'flags' => 'array',
            'is_flagged' => 'boolean',
            'read_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $config = config('verum_chat.attachments');

        $this->addMediaCollection($config['collection'])
            ->acceptsMimeTypes($config['mimes'])
            ->useDisk(config('verum_media.disk'));
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Conversion methods precede the image ones: width() returns an
        // ImageDriver and ends the Conversion chain.
        $this->addMediaConversion('thumb')
            ->performOnCollections(config('verum_chat.attachments.collection'))
            ->nonQueued()
            ->width(480)
            ->height(480);
    }

    /** @return BelongsTo<Conversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /** @return BelongsTo<Admin, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    /**
     * Flagged and not yet judged — the admin queue.
     *
     * @param  Builder<Message>  $query
     */
    public function scopeAwaitingReview(Builder $query): void
    {
        $query->where('is_flagged', true)
            ->whereNull('review_state');
    }

    /** @return list<array{url: string, thumb: string}> */
    public function attachments(): array
    {
        return array_values(
            $this->getMedia(config('verum_chat.attachments.collection'))
                ->map(fn (Media $media): array => [
                    'url' => $media->getUrl(),
                    'thumb' => $media->hasGeneratedConversion('thumb')
                        ? $media->getUrl('thumb')
                        : $media->getUrl(),
                ])
                ->all()
        );
    }

    public function isFromAdmin(): bool
    {
        return $this->sender_type === self::FROM_ADMIN;
    }

    /** Reasons this was flagged, for the admin queue. */
    public function flagLabels(): string
    {
        $rules = config('verum_chat.moderation.rules');

        return collect($this->flags ?? [])
            ->map(fn (string $key): string => __(
                $rules[$key]['label'] ?? $key,
                ['app' => config('app.name')],
            ))
            ->implode(', ');
    }
}
