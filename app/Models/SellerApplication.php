<?php

namespace App\Models;

use App\Support\SellerCategories;
use Database\Factories\SellerApplicationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A seller's application to trade on Verum.
 *
 * @property Carbon|null $submitted_at
 * @property Carbon|null $reviewed_at
 */
class SellerApplication extends Model
{
    /** @use HasFactory<SellerApplicationFactory> */
    use HasFactory;

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    /** Still undecided, but waiting on the applicant for something. */
    public const STATUS_ON_HOLD = 'on_hold';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'archived_at' => 'datetime',
            'specialty_keys' => 'array',
        ];
    }

    /** @return BelongsTo<SellerAccount, $this> */
    public function sellerAccount(): BelongsTo
    {
        return $this->belongsTo(SellerAccount::class);
    }

    /**
     * Undecided and still in the queue.
     *
     * @param  Builder<self>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->whereIn('status', [self::STATUS_SUBMITTED, self::STATUS_IN_REVIEW, self::STATUS_ON_HOLD])
            ->whereNull('archived_at');
    }

    /** @param  Builder<self>  $query */
    public function scopeArchived(Builder $query): void
    {
        $query->whereNotNull('archived_at');
    }

    public function isPending(): bool
    {
        return in_array(
            $this->status,
            [self::STATUS_SUBMITTED, self::STATUS_IN_REVIEW, self::STATUS_ON_HOLD],
            true,
        );
    }

    public function isOnHold(): bool
    {
        return $this->status === self::STATUS_ON_HOLD;
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * Readable specialty labels, from the stored keys.
     *
     * @return list<string>
     */
    public function specialtyLabels(): array
    {
        return SellerCategories::labelsFor((array) ($this->specialty_keys ?? []));
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Progress stages for the tracker the applicant sees.
     *
     * @return list<array{label: string, hint: string, state: string}>
     */
    public function timeline(): array
    {
        $decided = $this->isApproved() || $this->isRejected();

        return [
            [
                'label' => __('Submitted'),
                'hint' => $this->submitted_at?->diffForHumans() ?? __('Not yet submitted'),
                'state' => 'done',
            ],
            [
                'label' => $this->isOnHold() ? __('On hold') : __('Admin review'),
                'hint' => match (true) {
                    $this->isOnHold() => $this->decision_note ?? __('We need a little more from you'),
                    $decided => $this->reviewed_at?->diffForHumans() ?? __('Reviewed'),
                    default => __('Within 3 business days'),
                },
                'state' => $decided ? 'done' : 'now',
            ],
            [
                'label' => $this->isRejected() ? __('Not approved') : __('Storefront goes live'),
                'hint' => match (true) {
                    $this->isApproved() => __('Approved'),
                    $this->isRejected() => $this->decision_note ?? __('See the note above'),
                    default => __('After approval'),
                },
                'state' => $this->isApproved() ? 'done' : 'todo',
            ],
        ];
    }

    /** Human-readable reference shown to the applicant, e.g. APP-2026-4821. */
    public static function generateReference(): string
    {
        return sprintf('APP-%s-%04d', now()->year, random_int(1000, 9999));
    }
}
