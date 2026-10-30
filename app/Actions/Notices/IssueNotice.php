<?php

namespace App\Actions\Notices;

use App\Models\AccountNotice;
use App\Models\Admin;
use App\Models\Buyer;
use App\Models\SellerAccount;
use App\Notifications\AccountNoticeSent;
use App\Support\NoticeCatalogue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Staff tell an account something: record it, then deliver it.
 *
 * The only way a notice is created, so every one is stored with the exact
 * words sent — the admin's own, or the catalogue's default when they left
 * the box empty — and every one reaches the person the same three ways:
 * email, the bell, and live over their private channel.
 *
 * A null recipient is allowed and does nothing: a seeded storefront may
 * have no login account, and the caller should not have to check.
 */
class IssueNotice
{
    /** Longest message an admin may write; the dialog says the same. */
    public const MAX_LENGTH = 2000;

    /**
     * @param  array<string, string|int>  $replace  placeholders for the default text
     */
    public function send(
        Buyer|SellerAccount|null $recipient,
        string $kind,
        ?string $message = null,
        ?Admin $admin = null,
        ?Model $about = null,
        array $replace = [],
        ?string $link = null,
    ): ?AccountNotice {
        $notice = $this->record($recipient, $kind, $message, $admin, $about, $replace);

        if ($notice === null || $recipient === null) {
            return null;
        }

        $recipient->notify(new AccountNoticeSent(
            $notice,
            NoticeCatalogue::subject($kind, $replace),
            $link,
            NoticeCatalogue::countsAsWarning($kind) ? AccountNotice::warningCountFor($recipient) : null,
        ));

        return $notice;
    }

    /**
     * Keep the record without sending anything.
     *
     * For a notice another message already delivers — a case resolved
     * with a warning has its own email, but the warning must still count.
     *
     * @param  array<string, string|int>  $replace
     */
    public function record(
        Buyer|SellerAccount|null $recipient,
        string $kind,
        ?string $message = null,
        ?Admin $admin = null,
        ?Model $about = null,
        array $replace = [],
    ): ?AccountNotice {
        if ($recipient === null) {
            return null;
        }

        $written = self::clean($message);

        return AccountNotice::create([
            'recipient_type' => $recipient->getMorphClass(),
            'recipient_id' => $recipient->getKey(),
            'kind' => $kind,
            'subject_type' => $about?->getMorphClass(),
            'subject_id' => $about?->getKey(),
            'body' => $written ?? NoticeCatalogue::defaultBody($kind, $replace),
            'used_default' => $written === null,
            'admin_id' => $admin?->id,
        ]);
    }

    /**
     * Plain text only, and nothing at all if it is only whitespace.
     *
     * Markup is stripped rather than escaped: this text goes into an
     * email and the bell, and neither is a place for HTML from a form.
     */
    public static function clean(?string $message): ?string
    {
        if ($message === null) {
            return null;
        }

        $text = trim(preg_replace("/\R{3,}/", "\n\n", strip_tags($message)) ?? '');

        return $text === '' ? null : Str::limit($text, self::MAX_LENGTH, '');
    }
}
