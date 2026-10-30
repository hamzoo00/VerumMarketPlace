<?php

namespace App\Support;

use App\Models\Admin;
use App\Models\Buyer;
use App\Models\SellerAccount;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

/**
 * What the bell shows, and the one way a stored notification is read.
 *
 * Every notification's data is treated as untrusted on the way out: a
 * row written by an older version, or by a bug, still renders — with a
 * fallback title — and its link is only followed if it stays on this
 * site, so a notification can never become an open redirect.
 */
final class NotificationFeed
{
    /** How many the bell's panel lists; the full page pages the rest. */
    public const BELL_SIZE = 8;

    /**
     * @return array{
     *     area: string,
     *     unread: int,
     *     items: list<array<string, mixed>>,
     *     routes: array{index: string, bell: string, readAll: string},
     *     channel: string
     * }
     */
    public static function bell(string $area, Buyer|SellerAccount|Admin $account): array
    {
        return [
            'area' => $area,
            'unread' => $account->unreadNotifications()->count(),
            'items' => array_values($account->notifications()
                ->latest()
                ->limit(self::BELL_SIZE)
                ->get()
                ->map(fn (DatabaseNotification $notification): array => self::present($notification, $area))
                ->all()),
            'routes' => self::routes($area),
            'channel' => $account->receivesBroadcastNotificationsOn(),
        ];
    }

    /** @return array{id: string, title: string, body: string, icon: string, read: bool, when: string, at: string, open: string} */
    public static function present(DatabaseNotification $notification, string $area): array
    {
        $data = $notification->data;

        return [
            'id' => (string) $notification->id,
            'title' => Str::limit((string) ($data['title'] ?? __('Notification')), 120),
            'body' => Str::limit((string) ($data['body'] ?? ''), 200),
            'icon' => (string) ($data['icon'] ?? 'info'),
            'read' => $notification->read_at !== null,
            'when' => $notification->created_at?->diffForHumans() ?? '',
            'at' => $notification->created_at?->toIso8601String() ?? '',
            'open' => route($area.'.notifications.read', $notification->id),
        ];
    }

    /** @return array{index: string, bell: string, readAll: string} */
    public static function routes(string $area): array
    {
        return [
            'index' => route($area.'.notifications'),
            'bell' => route($area.'.notifications.bell'),
            'readAll' => route($area.'.notifications.read-all'),
        ];
    }

    /**
     * The link if it stays on this site, otherwise null.
     *
     * Relative paths and this app's own host only — never "//evil.test",
     * never another scheme.
     */
    public static function safeUrl(mixed $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//') && ! str_starts_with($url, '/\\')) {
            return $url;
        }

        $target = parse_url($url);
        $home = parse_url((string) config('app.url'));

        $sameHost = isset($target['host'], $home['host'])
            && strcasecmp($target['host'], $home['host']) === 0
            && ($target['port'] ?? null) === ($home['port'] ?? null)
            && in_array($target['scheme'] ?? '', ['http', 'https'], true);

        return $sameHost ? $url : null;
    }
}
