<?php

namespace App\Support;

use App\Models\Order;

/**
 * The parcel-status vocabulary in config/verum_tracking.php.
 *
 * One reader, so the seller's form, the timeline, the order chip and the
 * notifications cannot disagree about what a status is called or who is
 * told about it.
 */
final class ShipmentStatuses
{
    public const BEFORE_DISPATCH = 'before';

    public const AFTER_DISPATCH = 'after';

    public static function has(?string $status): bool
    {
        return $status !== null && isset(self::all()[$status]);
    }

    public static function label(?string $status): string
    {
        $label = self::all()[$status ?? '']['label'] ?? null;

        return $label === null ? (string) $status : __($label);
    }

    public static function headline(string $status, string $reference): string
    {
        return __(self::all()[$status]['headline'] ?? ':reference was updated', ['reference' => $reference]);
    }

    public static function tone(?string $status): ?string
    {
        return self::all()[$status ?? '']['tone'] ?? null;
    }

    /** Where the parcel is, rather than a remark about it. */
    public static function isState(string $status): bool
    {
        return (bool) (self::all()[$status]['state'] ?? false);
    }

    /**
     * How a party hears of this status: "mail", "bell" or null.
     *
     * The seller is told only of a courier scan; their own update needs
     * no announcing to them.
     */
    public static function channelFor(string $status, string $party): ?string
    {
        $channel = self::all()[$status][$party] ?? null;

        return in_array($channel, ['mail', 'bell'], true) ? $channel : null;
    }

    /**
     * Every status that says where the parcel is.
     *
     * @return list<string>
     */
    public static function states(): array
    {
        return array_keys(array_filter(self::all(), fn (array $status): bool => (bool) $status['state']));
    }

    /**
     * What the seller may record on this order now: before posting, a
     * note on preparation; once posted, where it has got to; after
     * delivery or cancellation, nothing.
     *
     * @return array<string, string>
     */
    public static function pickableFor(Order $order): array
    {
        return match ($order->status) {
            Order::STATUS_PAID => self::pickable(self::BEFORE_DISPATCH),
            Order::STATUS_DISPATCHED => self::pickable(self::AFTER_DISPATCH),
            default => [],
        };
    }

    /**
     * What a seller may record at this stage, for a select.
     *
     * @return array<string, string>
     */
    public static function pickable(string $stage): array
    {
        return collect(self::all())
            ->filter(fn (array $status): bool => in_array($status['pick'] ?? null, [$stage, 'any'], true))
            ->map(fn (array $status): string => __($status['label']))
            ->all();
    }

    /** @return array<string, array{label: string, headline: string, pick: string|null, state: bool, buyer: string|null, seller: string|null, tone: string|null}> */
    private static function all(): array
    {
        /** @var array<string, array{label: string, headline: string, pick: string|null, state: bool, buyer: string|null, seller: string|null, tone: string|null}> */
        return config('verum_tracking.statuses', []);
    }
}
