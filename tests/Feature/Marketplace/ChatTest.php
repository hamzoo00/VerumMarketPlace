<?php

use App\Actions\Chat\SendMessage;
use App\Actions\Chat\StartConversation;
use App\Models\Admin;
use App\Models\Buyer;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Message;
use App\Models\Seller;
use App\Models\SellerAccount;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Messaging between a buyer, a seller and an admin.
 *
 * A thread is private to its two parties, so the ways in are pinned as
 * carefully as the ways through.
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

it('reuses the thread for the same buyer, seller and listing', function (): void {
    $again = app(StartConversation::class)->handle($this->buyer, $this->seller, $this->listing);

    expect($again->id)->toBe($this->conversation->id)
        ->and(Conversation::count())->toBe(1);
});

it('keeps the inbox readable without reading every message', function (): void {
    $this->send->handle($this->conversation, Message::FROM_BUYER, $this->buyer->id, 'Is it still available?');

    $fresh = $this->conversation->fresh();

    expect($fresh->last_message_preview)->toBe('Is it still available?')
        ->and($fresh->seller_unread)->toBe(1)
        ->and($fresh->buyer_unread)->toBe(0);
});

it('delivers a flagged message rather than blocking it', function (): void {
    $message = $this->send->handle(
        $this->conversation,
        Message::FROM_BUYER,
        $this->buyer->id,
        'add me on whatsapp +92 300 1234567 and we deal direct',
    );

    // Flagging is a record, not a block: the message exists and the
    // thread is marked for an admin to read.
    expect($message->is_flagged)->toBeTrue()
        ->and($message->exists)->toBeTrue()
        ->and($this->conversation->fresh()->is_flagged)->toBeTrue();
});

it('never flags an admin', function (): void {
    $message = $this->send->handle(
        $this->conversation,
        Message::FROM_ADMIN,
        Admin::factory()->create()->id,
        'Please send us the courier number on +44 20 7946 0000.',
    );

    expect($message->is_flagged)->toBeFalse();
});

it('lets both parties and an admin read the thread', function (): void {
    $this->actingAs($this->buyer, 'buyer')->get(route('chat.show', $this->conversation))->assertOk();
    $this->actingAs($this->account, 'seller')->get(route('chat.show', $this->conversation))->assertOk();
    $this->actingAs(Admin::factory()->create(), 'admin')->get(route('chat.show', $this->conversation))->assertOk();
});

it('keeps an outsider out of a thread', function (): void {
    $outsider = Buyer::factory()->create();

    $this->actingAs($outsider, 'buyer')
        ->get(route('chat.show', $this->conversation))
        ->assertForbidden();

    $this->actingAs($outsider, 'buyer')
        ->post(route('chat.store', $this->conversation), ['body' => 'let me in'])
        ->assertForbidden();

    expect($this->conversation->messages()->count())->toBe(0);
});

it('keeps another seller out of a thread', function (): void {
    $other = SellerAccount::factory()->create(['approved_at' => now()]);
    Seller::factory()->create(['seller_account_id' => $other->id]);

    $this->actingAs($other, 'seller')
        ->get(route('chat.show', $this->conversation))
        ->assertForbidden();
});

it('sends a guest to the buyer sign-in page', function (): void {
    $this->get(route('chat.index'))->assertRedirect(route('buyer.login'));
});

it('marks a thread read when its owner opens it', function (): void {
    $this->send->handle($this->conversation, Message::FROM_SELLER, $this->seller->id, 'Yes, still here.');

    $this->actingAs($this->buyer, 'buyer')->get(route('chat.show', $this->conversation));

    expect($this->conversation->fresh()->buyer_unread)->toBe(0);
});

it('accepts an image and refuses anything else', function (): void {
    Storage::fake(config('verum_media.disk'));

    $this->actingAs($this->buyer, 'buyer')
        ->post(route('chat.store', $this->conversation), [
            'body' => 'Here it is in daylight',
            'attachments' => [UploadedFile::fake()->image('stone.jpg', 800, 600)],
        ])
        ->assertRedirect();

    expect(Message::latest('id')->first()->attachments())->toHaveCount(1);

    $this->actingAs($this->buyer, 'buyer')
        ->post(route('chat.store', $this->conversation), [
            'body' => 'And the paperwork',
            'attachments' => [UploadedFile::fake()->create('report.pdf', 40, 'application/pdf')],
        ])
        ->assertSessionHasErrors('attachments.0');
});

it('requires something to send', function (): void {
    $this->actingAs($this->buyer, 'buyer')
        ->post(route('chat.store', $this->conversation), ['body' => ''])
        ->assertSessionHasErrors('body');
});
