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
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
            'conversations' => $this->threadsFor($type, $id),
            'viewer' => $type,
            'account' => $request->user($type === Message::FROM_ADMIN ? 'admin' : $type),
            // Links stay inside the reader's own area; see Message::routesFor().
            'routes' => Message::routesFor($type),
            'active' => null,
        ]);
    }

    /**
     * The inbox, newest first.
     *
     * Shared by the list and the thread page, which renders both panes:
     * one query definition means the sidebar beside an open conversation
     * is ordered and filtered exactly like the list it came from.
     *
     * @return LengthAwarePaginator<int, Conversation>
     */
    private function threadsFor(string $type, int $id): LengthAwarePaginator
    {
        return Conversation::query()
            ->forParty($type, $id)
            ->with(['buyer', 'seller', 'listing'])
            // An admin sees flagged threads first; that is their queue.
            ->when($type === Message::FROM_ADMIN, fn ($query) => $query->orderByDesc('is_flagged'))
            ->orderByDesc('last_message_at')
            ->paginate(20);
    }

    public function show(Request $request, Conversation $conversation): View
    {
        [$type, $id] = $this->party($request);

        $this->authoriseAccess($conversation, $type, $id);
        $this->markRead($conversation, $type);

        return view('pages.chat.show', [
            'conversation' => $conversation->load(['buyer', 'seller', 'listing']),
            'messages' => $conversation->messages()->with('media')->oldest()->paginate(50),
            // The list travels with the thread, so the page is one
            // messenger rather than two screens and a back link.
            'conversations' => $this->threadsFor($type, $id),
            'viewer' => $type,
            'account' => $request->user($type === Message::FROM_ADMIN ? 'admin' : $type),
            'routes' => Message::routesFor($type),
            'active' => $conversation->id,
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

        /*
         * No toast either way.
         *
         * The composer already warns live as the sender types, and the
         * bubble itself carries the flag once it lands — a toast on top
         * of both told the same person the same thing a third time, over
         * the page header. The flag is recorded regardless; it reaches
         * the admin queue, which is what it is for.
         */
        return back();
    }

    /** Opens (or finds) the thread for a listing and goes to it. */
    public function start(Request $request, Listing $listing): RedirectResponse
    {
        $buyer = $request->user('buyer');

        abort_if($buyer === null, Response::HTTP_FORBIDDEN);
        abort_if($listing->seller === null, Response::HTTP_NOT_FOUND);

        return redirect()->route(
            'buyer.messages.show',
            $this->startConversation->handle($buyer, $listing->seller, $listing),
        );
    }

    /**
     * Who is asking, from the session only.
     *
     * The **route** names the audience where it knows it — `/seller/...`
     * is the seller's inbox even for someone who also holds an admin
     * session in the same browser. Without that, precedence alone
     * decided, and a seller clicking "Store messages" was shown the
     * admin shell: their own thread wrapped in staff navigation, with
     * Moderation one click away.
     *
     * Falling back to precedence keeps the shared `/messages` entry
     * working for whoever is signed in.
     *
     * @return array{0: string, 1: int}
     */
    private function party(Request $request): array
    {
        $declared = $request->route()?->defaults['audience'] ?? null;

        return match ($declared) {
            Message::FROM_ADMIN => $this->asAdmin($request),
            Message::FROM_SELLER => $this->asSeller($request),
            Message::FROM_BUYER => $this->asBuyer($request),
            default => $this->byPrecedence($request),
        };
    }

    /** @return array{0: string, 1: int} */
    private function byPrecedence(Request $request): array
    {
        if ($request->user('admin') !== null) {
            return $this->asAdmin($request);
        }

        if ($request->user('seller') !== null) {
            return $this->asSeller($request);
        }

        return $this->asBuyer($request);
    }

    /** @return array{0: string, 1: int} */
    private function asAdmin(Request $request): array
    {
        $admin = $request->user('admin');

        abort_if($admin === null, Response::HTTP_FORBIDDEN);

        return [Message::FROM_ADMIN, (int) $admin->id];
    }

    /** @return array{0: string, 1: int} */
    private function asSeller(Request $request): array
    {
        $seller = $request->user('seller');

        abort_if($seller === null, Response::HTTP_FORBIDDEN);

        $storefront = $seller->storefront;

        abort_if($storefront === null, Response::HTTP_FORBIDDEN, __('Your storefront is not set up yet.'));

        return [Message::FROM_SELLER, (int) $storefront->id];
    }

    /** @return array{0: string, 1: int} */
    private function asBuyer(Request $request): array
    {
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
