<?php

namespace App\Support;

/**
 * Reads a message for attempts to take the deal off the platform.
 *
 * Pure decisions, like AuctionRules and OfferRules — scoring only, no
 * persistence and no side effects, so the composer's live warning and
 * the stored verdict come from exactly the same code.
 *
 * **It flags; it does not block.** A regex cannot tell a phone number
 * from a carat weight with certainty, and eBay's filter is known for
 * refusing legitimate offers because it read a price as a number to
 * ring. A false positive that kills a real sale costs more than a
 * missed one an admin reviews afterwards. The message is delivered, the
 * sender is warned, and the attempt is on the record.
 */
final readonly class MessageModeration
{
    private function __construct(
        public int $score,
        /** @var list<string> rule keys that matched */
        public array $flags,
        /** @var list<string> human reasons, for the sender and the admin */
        public array $reasons,
    ) {}

    public static function inspect(string $body): self
    {
        $score = 0;
        $flags = [];
        $reasons = [];

        foreach (config('verum_chat.moderation.rules') as $key => $rule) {
            if (preg_match($rule['pattern'], $body) !== 1) {
                continue;
            }

            $score += (int) $rule['weight'];
            $flags[] = $key;
            $reasons[] = __($rule['label'], ['app' => config('app.name')]);
        }

        return new self($score, $flags, $reasons);
    }

    /** Worth telling the sender about. */
    public function shouldWarn(): bool
    {
        return $this->score >= (int) config('verum_chat.moderation.warn_at');
    }

    /** Worth an admin's attention. */
    public function shouldFlag(): bool
    {
        return $this->score >= (int) config('verum_chat.moderation.flag_at');
    }

    /**
     * What the sender is shown before the message goes.
     *
     * Phrased as a reminder of why the rule exists rather than an
     * accusation: most people doing this are not trying to cheat, they
     * just want to get paid faster.
     */
    public function warning(): ?string
    {
        if (! $this->shouldWarn()) {
            return null;
        }

        return __('This looks like it contains :reasons. Keep the conversation on :app — off-platform deals are not covered by our protection, and repeated attempts are reviewed.', [
            'reasons' => $this->reasonList(),
            'app' => config('app.name'),
        ]);
    }

    private function reasonList(): string
    {
        $reasons = array_map('mb_strtolower', $this->reasons);

        if (count($reasons) === 1) {
            return $reasons[0];
        }

        $last = array_pop($reasons);

        return implode(', ', $reasons).' '.__('and').' '.$last;
    }
}
