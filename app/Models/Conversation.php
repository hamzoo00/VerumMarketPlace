<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A thread between one buyer and one seller.
 *
 * An admin can read and post in any thread but is never a member, so
 * membership stays a simple two-column check.
 *
 * @property Carbon|null $last_message_at
 */
class Conversation extends Model
{
    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'buyer_archived_at' => 'datetime',
            'seller_archived_at' => 'datetime',
            'is_flagged' => 'boolean',
        ];
    }

    /** @return BelongsTo<Buyer, $this> */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    /** @return BelongsTo<Seller, $this> */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    /** @return BelongsTo<Listing, $this> */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /** @return HasMany<Message, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * May this party read the thread?
     *
     * The only place membership is decided — every controller and the
     * broadcast channel defer to it, so none of them can be laxer than
     * the others.
     */
    public function isParticipant(string $type, int $id): bool
    {
        return match ($type) {
            Message::FROM_BUYER => (int) $this->buyer_id === $id,
            Message::FROM_SELLER => (int) $this->seller_id === $id,
            // An admin may read any thread; that is the job.
            Message::FROM_ADMIN => true,
            default => false,
        };
    }

    /** @param  Builder<Conversation>  $query */
    public function scopeForParty(Builder $query, string $type, int $id): void
    {
        match ($type) {
            Message::FROM_BUYER => $query->where('buyer_id', $id)->whereNull('buyer_archived_at'),
            Message::FROM_SELLER => $query->where('seller_id', $id)->whereNull('seller_archived_at'),
            default => $query,
        };
    }

    public function unreadFor(string $type): int
    {
        return match ($type) {
            Message::FROM_BUYER => (int) $this->buyer_unread,
            Message::FROM_SELLER => (int) $this->seller_unread,
            default => 0,
        };
    }

    /** What the other side is called, from this side's point of view. */
    public function counterpartName(string $viewer): string
    {
        return $viewer === Message::FROM_BUYER
            ? ($this->seller->name ?? __('Seller'))
            : ($this->buyer->name ?? __('Buyer'));
    }
}
