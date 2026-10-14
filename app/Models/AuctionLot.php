<?php

namespace App\Models;

use Database\Factories\AuctionLotFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $ends_at
 */
class AuctionLot extends Model
{
    /** @use HasFactory<AuctionLotFactory> */
    use HasFactory;

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'ends_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Stone, $this> */
    public function stone(): BelongsTo
    {
        return $this->belongsTo(Stone::class);
    }

    /** @param  Builder<self>  $query */
    public function scopeLive(Builder $query): void
    {
        $query->where('ends_at', '>', now());
    }

    public function hasEnded(): bool
    {
        return $this->ends_at->isPast();
    }

    /** Lots inside the final hour are highlighted in the UI. */
    public function isClosingSoon(): bool
    {
        return ! $this->hasEnded() && $this->ends_at->diffInMinutes(now()) <= 60;
    }
}
