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

    /**
     * Where each audience's inbox lives.
     *
     * A thread is read inside the reader's own area, so every link must
     * stay there — a seller following a link into `/admin/...` would be
     * shown the staff shell around their own conversation.
     *
     * @return array{index: string, show: string, store: string}
     */
    public static function routesFor(string $viewer): array
    {
        $prefix = match ($viewer) {
            self::FROM_ADMIN => 'admin.messages',
            self::FROM_SELLER => 'seller.messages',
            default => 'buyer.messages',
        };

        return [
            'index' => $prefix,
            'show' => $prefix.'.show',
            'store' => $prefix.'.store',
        ];
    }

    /** A run from one sender inside this many minutes is grouped. */
    public const GROUP_WINDOW_MINUTES = 5;

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

    /**
     * Does this message open a new day?
     *
     * A long thread read back needs a date to anchor it; without one
     * "09:14" could be this morning or last March.
     */
    public function opensNewDay(?self $previous): bool
    {
        if ($this->created_at === null) {
            return false;
        }

        return $previous?->created_at === null
            || ! $previous->created_at->isSameDay($this->created_at);
    }

    /** "Today", "Yesterday", or the date. */
    public function dayLabel(): string
    {
        $at = $this->created_at;

        if ($at === null) {
            return '';
        }

        return match (true) {
            $at->isToday() => __('Today'),
            $at->isYesterday() => __('Yesterday'),
            $at->isCurrentYear() => $at->format('j M'),
            default => $at->format('j M Y'),
        };
    }

    public function isFromAdmin(): bool
    {
        return $this->sender_type === self::FROM_ADMIN;
    }

    /**
     * Is this a continuation of the previous message?
     *
     * A run from one person within a few minutes is one utterance, so
     * only the first of the run carries a name and a time — repeating
     * them on every line makes three short replies read as three
     * separate conversations.
     */
    public function followsSameSpeakerAs(?self $previous): bool
    {
        if ($previous === null || $this->created_at === null || $previous->created_at === null) {
            return false;
        }

        return $previous->sender_type === $this->sender_type
            && (int) $previous->sender_id === (int) $this->sender_id
            && $previous->created_at->diffInMinutes($this->created_at) < self::GROUP_WINDOW_MINUTES;
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
