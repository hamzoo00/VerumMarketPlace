<?php

namespace App\Support;

use App\Models\Message;
use Illuminate\Support\Collection;

/**
 * How often an account's flags have been upheld.
 *
 * One flagged message is usually carelessness; the same sender flagged
 * again and again, across different threads, is someone working to move
 * your customers off the platform. The moderation queue judges one
 * sentence at a time, so without this the two read identically.
 *
 * Counts **upheld verdicts only**. Pending flags include the false
 * positives this system deliberately tolerates, and a counter that
 * inflates on unreviewed noise teaches an admin to ignore it — which is
 * the opposite of the point.
 *
 * Keyed on the message's own sender, never on the thread's seller: a
 * buyer's flags belong to that buyer, not to whoever they wrote to.
 */
final readonly class MessageOffences
{
    private function __construct(
        /** @var array<string, int> keyed "type:id" */
        private array $counts,
    ) {}

    /**
     * Upheld counts for every sender in a set of messages.
     *
     * One grouped query for the whole page rather than one per row —
     * the queue paginates at 25.
     *
     * @param  Collection<int, Message>  $messages
     */
    public static function forMessages(Collection $messages): self
    {
        $senders = $messages
            ->map(fn (Message $message): array => [
                'type' => (string) $message->sender_type,
                'id' => (int) $message->sender_id,
            ])
            ->unique(fn (array $sender): string => self::key($sender['type'], $sender['id']))
            ->values();

        if ($senders->isEmpty()) {
            return new self([]);
        }

        $rows = Message::query()
            ->toBase()
            ->where('review_state', Message::REVIEW_UPHELD)
            ->where(function ($query) use ($senders): void {
                foreach ($senders as $sender) {
                    $query->orWhere(fn ($inner) => $inner
                        ->where('sender_type', $sender['type'])
                        ->where('sender_id', $sender['id']));
                }
            })
            ->selectRaw('sender_type, sender_id, count(*) as total')
            ->groupBy('sender_type', 'sender_id')
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            $counts[self::key((string) $row->sender_type, (int) $row->sender_id)] = (int) $row->total;
        }

        return new self($counts);
    }

    /**
     * Upheld counts for a set of sellers, for the admin seller list.
     *
     * @param  array<int, int>  $sellerIds  storefront ids, as `messages.sender_id` holds
     * @return array<int, int>
     */
    public static function forSellers(array $sellerIds): array
    {
        if ($sellerIds === []) {
            return [];
        }

        return Message::query()
            ->where('sender_type', Message::FROM_SELLER)
            ->where('review_state', Message::REVIEW_UPHELD)
            ->whereIn('sender_id', $sellerIds)
            ->selectRaw('sender_id, count(*) as total')
            ->groupBy('sender_id')
            ->pluck('total', 'sender_id')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }

    /**
     * Upheld flags for this message's sender, *other than this one*.
     *
     * A message already upheld is in the count, so it subtracts itself:
     * on the Upheld tab a first offence must not read "1 before".
     */
    public function for(Message $message): int
    {
        $total = $this->counts[self::key((string) $message->sender_type, (int) $message->sender_id)] ?? 0;

        if ($message->review_state === Message::REVIEW_UPHELD) {
            $total--;
        }

        return max(0, $total);
    }

    /**
     * Is this sender a repeat offender?
     *
     * Two upheld verdicts is the line: one is a single bad message, two
     * is a habit.
     */
    public function isRepeat(Message $message): bool
    {
        return $this->for($message) >= 2;
    }

    private static function key(string $type, int $id): string
    {
        return $type.':'.$id;
    }
}
