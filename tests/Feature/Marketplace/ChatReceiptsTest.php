<?php

use App\Actions\Chat\SendMessage;
use App\Actions\Chat\StartConversation;
use App\Events\MessagesRead;
use App\Models\Admin;
use App\Models\Buyer;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Message;
use App\Models\Seller;
use App\Models\SellerAccount;
use App\Support\MessageOffences;
use Illuminate\Support\Facades\Event;

/**
 * Read receipts and repeat-offender counts.
 *
 * Both read columns that were already being written, so what is pinned
 * here is who they are true *about*: a receipt must mean the other party
 * opened the thread, and a flag count must belong to the sender.
 */
beforeEach(function (): void {
    $this->account = SellerAccount::factory()->create(['approved_at' => now()]);
    $this->seller = Seller::factory()->create(['seller_account_id' => $this->account->id]);
    $this->category = Category::factory()->create(['kind' => Category::KIND_STONE]);
    $this->buyer = Buyer::factory()->create();

    $this->listing = Listing::factory()->published()->for($this->seller)->for($this->category)->create();

    $this->conversation = app(StartConversation::class)->handle($this->buyer, $this->seller, $this->listing);
    $this->send = app(SendMessage::class);
});

it('stamps the other side messages as read, never your own', function (): void {
    $fromBuyer = $this->send->handle($this->conversation, Message::FROM_BUYER, $this->buyer->id, 'Is it available?');

    // The buyer opening their own thread must not mark their own message
    // read, or every message would read as read the moment it was sent.
    $this->actingAs($this->buyer, 'buyer')->get(route('buyer.messages.show', $this->conversation))->assertOk();

    expect($fromBuyer->fresh()->read_at)->toBeNull();

    $this->actingAs($this->account, 'seller')->get(route('seller.messages.show', $this->conversation))->assertOk();

    expect($fromBuyer->fresh()->read_at)->not->toBeNull();
});

it('does not stamp read when an admin reads the thread', function (): void {
    $fromBuyer = $this->send->handle($this->conversation, Message::FROM_BUYER, $this->buyer->id, 'Is it available?');

    $this->actingAs(Admin::factory()->create(), 'admin')
        ->get(route('admin.messages.show', $this->conversation))
        ->assertOk();

    // A buyer must not be told "read" because staff looked.
    expect($fromBuyer->fresh()->read_at)->toBeNull();
});

it('stamps later messages even once the unread count is zero', function (): void {
    $this->send->handle($this->conversation, Message::FROM_BUYER, $this->buyer->id, 'First');

    $this->actingAs($this->account, 'seller')->get(route('seller.messages.show', $this->conversation));

    // Zero the counter by hand, as a second tab would.
    $this->conversation->fresh()->update(['seller_unread' => 0]);

    $later = $this->send->handle($this->conversation, Message::FROM_BUYER, $this->buyer->id, 'Still there?');
    $this->conversation->fresh()->update(['seller_unread' => 0]);

    $this->actingAs($this->account, 'seller')->get(route('seller.messages.show', $this->conversation));

    expect($later->fresh()->read_at)->not->toBeNull();
});

it('tells the sender over the socket that the thread was read', function (): void {
    Event::fake([MessagesRead::class]);

    $this->send->handle($this->conversation, Message::FROM_BUYER, $this->buyer->id, 'Is it available?');

    $this->actingAs($this->account, 'seller')->get(route('seller.messages.show', $this->conversation));

    Event::assertDispatched(MessagesRead::class);
});

it('does not announce a read when nothing changed', function (): void {
    $this->send->handle($this->conversation, Message::FROM_BUYER, $this->buyer->id, 'Is it available?');

    $this->actingAs($this->account, 'seller')->get(route('seller.messages.show', $this->conversation));

    Event::fake([MessagesRead::class]);

    // Opening it again reads nothing new, so the sender hears nothing.
    $this->actingAs($this->account, 'seller')->get(route('seller.messages.show', $this->conversation));

    Event::assertNotDispatched(MessagesRead::class);
});

it('shows a receipt to the sender once the other side has read', function (): void {
    $this->send->handle($this->conversation, Message::FROM_BUYER, $this->buyer->id, 'Is it available?');

    $this->actingAs($this->buyer, 'buyer')
        ->get(route('buyer.messages.show', $this->conversation))
        ->assertOk()
        ->assertSee('Sent')
        ->assertDontSee('Read ');

    /*
     * Each audience gets a clean session. `actingAs` adds a guard rather
     * than replacing one, and ConversationController::party() resolves
     * admin, then seller, then buyer — so a seller left signed in would
     * make the next request render the seller's view of the thread.
     */
    auth()->guard('buyer')->logout();

    $this->actingAs($this->account, 'seller')->get(route('seller.messages.show', $this->conversation));

    auth()->guard('seller')->logout();

    $this->actingAs($this->buyer, 'buyer')
        ->get(route('buyer.messages.show', $this->conversation))
        ->assertOk()
        ->assertSee('Read ');
});

it('counts upheld flags against the sender, not the thread seller', function (): void {
    $first = $this->send->handle(
        $this->conversation,
        Message::FROM_BUYER,
        $this->buyer->id,
        'whatsapp me on +92 300 1234567',
    );
    $second = $this->send->handle(
        $this->conversation,
        Message::FROM_BUYER,
        $this->buyer->id,
        'email me at buyer@example.com instead',
    );

    $first->update(['review_state' => Message::REVIEW_UPHELD]);

    $offences = MessageOffences::forMessages(Message::whereKey([$first->id, $second->id])->get());

    // The buyer wrote both, so neither count belongs to the seller.
    expect($offences->for($second))->toBe(1)
        // The upheld row subtracts itself: it is not "1 before" its own self.
        ->and($offences->for($first->fresh()))->toBe(0)
        ->and(MessageOffences::forSellers([$this->seller->id]))->toBe([]);
});

it('treats two upheld flags as a repeat offender', function (): void {
    $messages = collect(['whatsapp +92 300 1234567', 'telegram +92 300 7654321', 'signal +92 300 1112223'])
        ->map(fn (string $body): Message => $this->send->handle(
            $this->conversation,
            Message::FROM_SELLER,
            $this->seller->id,
            $body,
        ));

    $messages->take(2)->each(fn (Message $m) => $m->update(['review_state' => Message::REVIEW_UPHELD]));

    $latest = $messages->last();
    $offences = MessageOffences::forMessages(Message::whereKey($latest->id)->get());

    expect($offences->for($latest))->toBe(2)
        ->and($offences->isRepeat($latest))->toBeTrue()
        ->and(MessageOffences::forSellers([$this->seller->id]))->toBe([$this->seller->id => 2]);
});

it('ignores pending flags in the offence count', function (): void {
    $message = $this->send->handle(
        $this->conversation,
        Message::FROM_SELLER,
        $this->seller->id,
        'whatsapp +92 300 1234567',
    );

    // Pending flags include the false positives this system tolerates,
    // so an unreviewed flag must not count against anyone.
    $offences = MessageOffences::forMessages(Message::whereKey($message->id)->get());

    expect($offences->for($message))->toBe(0)
        ->and($offences->isRepeat($message))->toBeFalse();
});

it('renders the queue and the seller list with offence counts', function (): void {
    $first = $this->send->handle($this->conversation, Message::FROM_SELLER, $this->seller->id, 'whatsapp +92 300 1234567');
    $this->send->handle($this->conversation, Message::FROM_SELLER, $this->seller->id, 'telegram +92 300 7654321');

    $first->update(['review_state' => Message::REVIEW_UPHELD]);

    $admin = Admin::factory()->create();

    // Rendered, not just counted: a Blade slip here is invisible to a
    // unit assertion on the support class.
    $this->actingAs($admin, 'admin')
        ->get(route('admin.moderation'))
        ->assertOk()
        ->assertSee('1 upheld flag before');

    $this->actingAs($admin, 'admin')
        ->get(route('admin.sellers'))
        ->assertOk()
        ->assertSee('Flags');
});
