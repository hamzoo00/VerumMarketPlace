<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A message landed in a thread.
 *
 * On a **private** channel for that one conversation: a chat between two
 * parties is nobody else's business, and the payload carries the body.
 *
 * Carries plain data, never the model — a queued job holding only an id
 * cannot rehydrate a deleted row, and Laravel discards it silently.
 */
class MessageSent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;

    /** @param  array<string, mixed>  $payload */
    private function __construct(
        public array $payload,
        public int $conversationId,
    ) {}

    public static function for(Message $message): self
    {
        return new self([
            'id' => $message->id,
            'from' => $message->sender_type,
            'sender_id' => $message->sender_id,
            // The recipient's page refetches to render it, so the body
            // is here only for an unread badge and a preview.
            'at' => $message->created_at?->toIso8601String(),
            'flagged' => (bool) $message->is_flagged,
        ], (int) $message->conversation_id);
    }

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('conversation.'.$this->conversationId)];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
