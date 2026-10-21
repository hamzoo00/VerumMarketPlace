<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * One side read the thread.
 *
 * On the same private conversation channel as MessageSent, so the
 * sender's open page learns their message landed without a reload.
 *
 * Carries plain data, never the model — a queued job holding only an id
 * cannot rehydrate a deleted row, and Laravel discards it silently.
 */
class MessagesRead implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;

    /** @param  array<string, mixed>  $payload */
    private function __construct(
        public array $payload,
        public int $conversationId,
    ) {}

    /** @param  string  $reader  the party that read: buyer or seller */
    public static function for(Conversation $conversation, string $reader): self
    {
        return new self([
            'reader' => $reader,
            'at' => now()->toIso8601String(),
        ], (int) $conversation->id);
    }

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('conversation.'.$this->conversationId)];
    }

    public function broadcastAs(): string
    {
        return 'messages.read';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
