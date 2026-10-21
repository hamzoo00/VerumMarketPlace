<?php

namespace App\Http\Controllers\Chat;

use App\Actions\Chat\SendMessage;
use App\Actions\Chat\StartConversation;
use App\Events\MessagesRead;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\MessageRequest;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Message;
use App\Support\MessageModeration;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * One inbox, three audiences.
 *
 * Buyers, sellers and admins all read and write the same threads, so one
 * controller serves all three and resolves who is asking from the
 * session — never from the request. Splitting it per guard would mean
 * three copies of the membership check, and three chances to get it wrong.
 */
class ConversationController extends Controller
{
    public function __construct(
        private readonly SendMessage $sendMessage,
        private readonly StartConversation $startConversation,
    ) {}

    public function index(Request $request): View
    {
        [$type, $id] = $this->party($request);

        return view('pages.chat.index', [
            'conversations' => Conversation::query()
                ->forParty($type, $id)
                ->with(['buyer', 'seller', 'listing'])
                // An admin sees flagged threads first; that is their queue.
                ->when($type === Message::FROM_ADMIN, fn ($query) => $query->orderByDesc('is_flagged'))
                ->orderByDesc('last_message_at')
                ->paginate(20),
            'viewer' => $type,
            'account' => $request->user($type === Message::FROM_ADMIN ? 'admin' : $type),
        ]);
    }

    public function show(Request $request, Conversation $conversation): View
    {
        [$type, $id] = $this->party($request);

        $this->authoriseAccess($conversation, $type, $id);
        $this->markRead($conversation, $type);

        return view('pages.chat.show', [
            'conversation' => $conversation->load(['buyer', 'seller', 'listing']),
            'messages' => $conversation->messages()->with('media')->oldest()->paginate(50),
            'viewer' => $type,
            'account' => $request->user($type === Message::FROM_ADMIN ? 'admin' : $type),
        ]);
    }

    public function store(MessageRequest $request, Conversation $conversation): RedirectResponse
    {
        [$type, $id] = $this->party($request);

        $this->authoriseAccess($conversation, $type, $id);

        $body = (string) $request->string('body');

        $message = $this->sendMessage->handle(
            $conversation,
            $type,
            $id,
            $body,
            array_values($request->file('attachments') ?? []),
        );

        // The sender is told what was noticed, and why. The message still
        // went, so this is a nudge rather than a punishment.
        $warning = MessageModeration::inspect($body)->warning();

        if ($message->is_flagged || $warning !== null) {
            return back()->with('warning', $warning);
        }

        return back()->with('status', __('Sent.'));
    }

    /** Opens (or finds) the thread for a listing and goes to it. */
    public function start(Request $request, Listing $listing): RedirectResponse
    {
        $buyer = $request->user('buyer');

        abort_if($buyer === null, Response::HTTP_FORBIDDEN);
        abort_if($listing->seller === null, Response::HTTP_NOT_FOUND);

        return redirect()->route(
            'chat.show',
            $this->startConversation->handle($buyer, $listing->seller, $listing),
        );
    }

    /**
     * Who is asking, from the session only.
     *
     * @return array{0: string, 1: int}
     */
    private function party(Request $request): array
    {
        $admin = $request->user('admin');

        if ($admin !== null) {
            return [Message::FROM_ADMIN, (int) $admin->id];
        }

        $seller = $request->user('seller');

        if ($seller !== null) {
            $storefront = $seller->storefront;

            abort_if($storefront === null, Response::HTTP_FORBIDDEN, __('Your storefront is not set up yet.'));

            return [Message::FROM_SELLER, (int) $storefront->id];
        }

        $buyer = $request->user('buyer');

        abort_if($buyer === null, Response::HTTP_FORBIDDEN);

        return [Message::FROM_BUYER, (int) $buyer->id];
    }

    private function authoriseAccess(Conversation $conversation, string $type, int $id): void
    {
        abort_unless($conversation->isParticipant($type, $id), Response::HTTP_FORBIDDEN);
    }

    /**
     * Stamp what this party has now seen.
     *
     * `read_at` is a receipt shown to the sender and quoted in disputes,
     * so only the *other* side's messages are stamped — marking your own
     * as read would make every message read the moment you sent it.
     *
     * An admin reading a thread stamps nothing: they are not a party,
     * and a buyer must not be told "read" because staff looked.
     */
    private function markRead(Conversation $conversation, string $type): void
    {
        $column = match ($type) {
            Message::FROM_BUYER => 'buyer_unread',
            Message::FROM_SELLER => 'seller_unread',
            default => null,
        };

        if ($column === null) {
            return;
        }

        // Not gated on the unread counter: a thread opened in two tabs
        // zeroes it on the first, and the second would then leave later
        // messages unstamped for good.
        $stamped = $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_type', '!=', $type)
            ->update(['read_at' => now()]);

        if ($conversation->{$column} > 0) {
            $conversation->update([$column => 0]);
        }

        // Tell the sender, so their open thread shows the receipt without
        // a reload. Only when something actually changed.
        if ($stamped > 0) {
            event(MessagesRead::for($conversation, $type));
        }
    }
}
