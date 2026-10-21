<?php

namespace App\Actions\Chat;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Support\MessageModeration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Posts one message into a thread.
 *
 * The only write path, so moderation, the unread counts and the
 * broadcast always happen together — a message stored without being
 * inspected would be a hole in the record.
 */
class SendMessage
{
    /**
     * @param  list<UploadedFile>  $attachments
     */
    public function handle(
        Conversation $conversation,
        string $senderType,
        int $senderId,
        string $body,
        array $attachments = [],
    ): Message {
        $verdict = MessageModeration::inspect($body);

        $message = DB::transaction(function () use ($conversation, $senderType, $senderId, $body, $verdict): Message {
            $message = $conversation->messages()->create([
                'sender_type' => $senderType,
                'sender_id' => $senderId,
                'body' => $body,
                'risk_score' => $verdict->score,
                'flags' => $verdict->flags === [] ? null : $verdict->flags,
                // An admin's own message is never flagged: they are the
                // ones who would review it.
                'is_flagged' => $senderType !== Message::FROM_ADMIN && $verdict->shouldFlag(),
            ]);

            $this->touchConversation($conversation, $message, $body);

            return $message;
        });

        $this->attach($message, $attachments);

        // After commit, so the row is readable when the socket arrives.
        event(MessageSent::for($message->fresh(['conversation'])));

        return $message;
    }

    /**
     * Keep the inbox readable without touching `messages`.
     *
     * The preview is plain text and truncated: an inbox row must never
     * become a place to smuggle markup.
     */
    private function touchConversation(Conversation $conversation, Message $message, string $body): void
    {
        $counts = match ($message->sender_type) {
            Message::FROM_BUYER => ['seller_unread' => $conversation->seller_unread + 1],
            Message::FROM_SELLER => ['buyer_unread' => $conversation->buyer_unread + 1],
            // An admin stepping in is unread for both sides.
            default => [
                'buyer_unread' => $conversation->buyer_unread + 1,
                'seller_unread' => $conversation->seller_unread + 1,
            ],
        };

        $conversation->update([
            ...$counts,
            'last_message_at' => $message->created_at,
            'last_message_preview' => Str::limit(strip_tags($body), 150),
            // Once flagged, a thread stays flagged: the admin needs the
            // history, not just the latest state.
            'is_flagged' => $conversation->is_flagged || $message->is_flagged,
        ]);
    }

    /** @param  list<UploadedFile>  $attachments */
    private function attach(Message $message, array $attachments): void
    {
        $config = config('verum_chat.attachments');

        foreach (array_slice($attachments, 0, (int) $config['max_per_message']) as $file) {
            $message->addMedia($file)->toMediaCollection(
                $config['collection'],
                config('verum_media.disk'),
            );
        }
    }
}
