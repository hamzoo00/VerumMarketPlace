<?php

namespace App\Models;

use Database\Factories\SupportCaseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A dispute or an audit, awaiting an admin.
 *
 * Named SupportCase because `Case` is a reserved word in PHP; the table
 * is `cases`.
 *
 * @property Carbon|null $resolved_at
 */
class SupportCase extends Model
{
    /** @use HasFactory<SupportCaseFactory> */
    use HasFactory;

    protected $table = 'cases';

    public const KIND_DISPUTE = 'dispute';

    public const KIND_AUDIT = 'audit';

    public const STATUS_OPEN = 'open';

    public const STATUS_REVIEWING = 'reviewing';

    public const STATUS_RESOLVED = 'resolved';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    /** A human reference both parties can quote: AUD-2026-0042. */
    public static function newReference(string $kind): string
    {
        $prefix = $kind === self::KIND_AUDIT ? 'AUD' : 'DSP';

        return $prefix.'-'.now()->year.'-'.Str::upper(Str::random(6));
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

    /** @return BelongsTo<Admin, $this> */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'resolved_by');
    }

    /** @param  Builder<SupportCase>  $query */
    public function scopePending(Builder $query): void
    {
        $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_REVIEWING]);
    }

    public function isPending(): bool
    {
        return $this->status !== self::STATUS_RESOLVED;
    }

    public function isAudit(): bool
    {
        return $this->kind === self::KIND_AUDIT;
    }

    public function kindLabel(): string
    {
        return $this->isAudit() ? __('Audit') : __('Dispute');
    }

    public function reasonLabel(): string
    {
        $set = $this->isAudit() ? 'audits' : 'disputes';

        return config("verum_cases.{$set}.reasons.{$this->reason}", $this->reason);
    }

    public function resolutionLabel(): ?string
    {
        if ($this->resolution === null) {
            return null;
        }

        return config("verum_cases.resolutions.{$this->resolution}", $this->resolution);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => __('Open'),
            self::STATUS_REVIEWING => __('Being reviewed'),
            default => __('Resolved'),
        };
    }

    /** The seller this concerns — named directly, or via the listing. */
    public function subjectSeller(): ?Seller
    {
        return $this->seller ?? $this->listing?->seller;
    }
}
