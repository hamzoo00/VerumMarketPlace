<?php

namespace App\Actions\Chat;

use App\Actions\Notices\IssueNotice;
use App\Models\Admin;
use App\Models\Buyer;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\SellerAccount;
use App\Support\StaffBoard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * An admin's verdict on a flagged message.
 *
 * "Upheld" means the admin agrees it was a real attempt to take the deal
 * off the platform. Two people are told, differently:
 *
 *  - the sender gets a formal warning, which counts on their account;
 *  - the other party gets a safety notice — keep payment here, share no
 *    contact details — without being shown the flagged text.
 *
 * Only the first time a message is upheld: reviewing it again must not
 * warn the sender twice for one sentence.
 *
 * "Cleared" is a false positive. Nobody is told; the record says the rule
 * misfired, which is how a rule that misfires often gets narrowed.
 */
class ReviewFlag
{
    public function __construct(private readonly IssueNotice $notices) {}

    public function handle(Message $message, Admin $admin, string $verdict, ?string $note = null): Message
    {
        $previous = DB::transaction(function () use ($message, $admin, $verdict): ?string {
            $locked = Message::query()->lockForUpdate()->findOrFail($message->id);
            $previous = $locked->review_state;

            $locked->update([
                'review_state' => $verdict,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            return $previous;
        });

        $message->refresh();

        if ($verdict === Message::REVIEW_UPHELD && $previous !== Message::REVIEW_UPHELD) {
            $this->tellBothSides($message, $admin, $note);
        }

        StaffBoard::changed(StaffBoard::MODERATION, StaffBoard::SELLERS, StaffBoard::BUYERS);

        return $message;
    }

    private function tellBothSides(Message $message, Admin $admin, ?string $note): void
    {
        $conversation = $message->conversation()->with(['buyer', 'seller.account'])->first();

        if (! $conversation instanceof Conversation) {
            return;
        }

        $buyer = $conversation->buyer;
        $seller = $conversation->seller?->account;

        [$sender, $other, $senderSide, $otherSide] = match ($message->sender_type) {
            Message::FROM_BUYER => [$buyer, $seller, Message::FROM_BUYER, Message::FROM_SELLER],
            Message::FROM_SELLER => [$seller, $buyer, Message::FROM_SELLER, Message::FROM_BUYER],
            default => [null, null, null, null],
        };

        if ($sender === null && $other === null) {
            return;
        }

        $this->notices->send(
            $sender,
            'flag_warning',
            $note,
            $admin,
            $message,
            [
                'counterpart' => $conversation->counterpartName((string) $senderSide),
                'excerpt' => Str::limit(Str::squish((string) $message->body), 80),
            ],
            $this->threadLink($conversation, $senderSide, $sender),
        );

        // Never the admin's note: that was written to the sender.
        $this->notices->send(
            $other,
            'safety_notice',
            admin: $admin,
            about: $conversation,
            link: $this->threadLink($conversation, $otherSide, $other),
        );
    }

    private function threadLink(Conversation $conversation, ?string $side, Buyer|SellerAccount|null $account): ?string
    {
        if ($side === null || $account === null) {
            return null;
        }

        return route(Message::routesFor($side)['show'], $conversation);
    }
}
