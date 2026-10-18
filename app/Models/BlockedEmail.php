<?php

namespace App\Models;

use Database\Factories\BlockedEmailFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An email barred from a given signup flow.
 *
 * @property string $email
 * @property string $scope
 */
class BlockedEmail extends Model
{
    /** @use HasFactory<BlockedEmailFactory> */
    use HasFactory;

    public const SCOPE_SELLER = 'seller';

    protected $guarded = [];

    public static function isBlocked(string $email, string $scope = self::SCOPE_SELLER): bool
    {
        return static::query()
            ->whereRaw('lower(email) = ?', [mb_strtolower(trim($email))])
            ->where('scope', $scope)
            ->exists();
    }

    public static function block(
        string $email,
        string $scope = self::SCOPE_SELLER,
        ?string $reason = null,
        ?string $by = null,
    ): self {
        return static::updateOrCreate(
            ['email' => mb_strtolower(trim($email))],
            ['scope' => $scope, 'reason' => $reason, 'blocked_by' => $by],
        );
    }

    public static function unblock(string $email, string $scope = self::SCOPE_SELLER): void
    {
        static::query()
            ->whereRaw('lower(email) = ?', [mb_strtolower(trim($email))])
            ->where('scope', $scope)
            ->delete();
    }
}
