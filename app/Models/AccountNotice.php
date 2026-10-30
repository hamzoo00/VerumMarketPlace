<?php

namespace App\Models;

use App\Support\NoticeCatalogue;
use Database\Factories\AccountNoticeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * One message staff sent to an account, as it was sent.
 *
 * Append-only: there is no `updated_at`, and nothing edits a row. The
 * delivery — email, the bell — is the notification; this is the record
 * that says what someone was told and when, which is what an appeal or
 * a dispute is argued over.
 *
 * @property Carbon|null $created_at
 */
class AccountNotice extends Model
{
    /** @use HasFactory<AccountNoticeFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'used_default' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function recipient(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<Admin, $this> */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Notices that count against the account.
     *
     * @param  Builder<self>  $query
     */
    public function scopeWarnings(Builder $query): void
    {
        $query->whereIn('kind', NoticeCatalogue::warningKinds());
    }

    /** @param  Builder<self>  $query */
    public function scopeFor(Builder $query, Model $recipient): void
    {
        $query->where('recipient_type', $recipient->getMorphClass())
            ->where('recipient_id', $recipient->getKey());
    }

    public static function warningCountFor(Model $recipient): int
    {
        return self::query()->for($recipient)->warnings()->count();
    }

    /**
     * Warnings per account, for a whole page of them in one query.
     *
     * @param  class-string<Model>  $type
     * @param  array<int, int>  $ids
     * @return array<int, int>
     */
    public static function warningCounts(string $type, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return self::query()
            ->where('recipient_type', (new $type)->getMorphClass())
            ->whereIn('recipient_id', $ids)
            ->warnings()
            ->selectRaw('recipient_id, count(*) as total')
            ->groupBy('recipient_id')
            ->pluck('total', 'recipient_id')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }

    /** The latest notice of a kind, e.g. why a seller was suspended. */
    public static function latestFor(Model $recipient, string $kind): ?self
    {
        return self::query()->for($recipient)->where('kind', $kind)->latest('id')->first();
    }
}
