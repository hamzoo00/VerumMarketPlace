<?php

use App\Actions\Chat\SendMessage;
use App\Actions\Chat\StartConversation;
use App\Models\Admin;
use App\Models\Buyer;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Message;
use App\Models\Seller;
use App\Models\SellerAccount;

/**
 * Which shell a thread is read in.
 *
 * Guards stack in one browser, so precedence alone decided this once —
 * and a seller clicking "Store messages" was handed the admin panel
 * wrapped around their own conversation, Moderation one click away.
 * The route names its audience instead.
 */
beforeEach(function (): void {
    $this->account = SellerAccount::factory()->create(['approved_at' => now()]);
    $this->seller = Seller::factory()->create(['seller_account_id' => $this->account->id]);
    $this->category = Category::factory()->create(['kind' => Category::KIND_STONE]);
    $this->buyer = Buyer::factory()->create();
    $this->admin = Admin::factory()->create();

    $this->listing = Listing::factory()->published()->for($this->seller)->for($this->category)->create();
    $this->conversation = app(StartConversation::class)->handle($this->buyer, $this->seller, $this->listing);

    app(SendMessage::class)->handle($this->conversation, Message::FROM_BUYER, $this->buyer->id, 'Is it available?');
});

it('keeps a seller in their own area even with an admin session alive', function (): void {
    $html = $this->actingAs($this->admin, 'admin')
        ->actingAs($this->account, 'seller')
        ->get(route('seller.messages'))
        ->assertOk()
        ->getContent();

    // The staff shell and its links must not appear.
    expect($html)->not->toContain('adminbar')
        ->and($html)->not->toContain('Moderation');
});

it('keeps a buyer in their own area even with an admin session alive', function (): void {
    $html = $this->actingAs($this->admin, 'admin')
        ->actingAs($this->buyer, 'buyer')
        ->get(route('buyer.messages'))
        ->assertOk()
        ->getContent();

    expect($html)->not->toContain('adminbar')
        ->and($html)->toContain('Saved stones');
});

it('still gives an admin the staff shell on the admin route', function (): void {
    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.messages'))
        ->assertOk()
        ->assertSee('adminbar', escape: false);
});

it('reads a thread in the area it was opened from', function (): void {
    $this->actingAs($this->admin, 'admin')
        ->actingAs($this->account, 'seller')
        ->get(route('seller.messages.show', $this->conversation))
        ->assertOk()
        // Every link stays in the seller's own area.
        ->assertSee(route('seller.messages'), escape: false)
        ->assertDontSee('adminbar', escape: false);
});

it('refuses an area a visitor does not hold', function (): void {
    // A buyer-only session cannot reach the seller or admin inbox.
    $this->actingAs($this->buyer, 'buyer')->get(route('seller.messages'))->assertRedirect();

    $this->actingAs($this->buyer, 'buyer')->get(route('admin.messages'))->assertRedirect();
});

it('still keeps an outsider out of a thread', function (): void {
    $outsider = Buyer::factory()->create();

    $this->actingAs($outsider, 'buyer')
        ->get(route('buyer.messages.show', $this->conversation))
        ->assertForbidden();
});

it('forwards the shared entry to the right area', function (): void {
    $this->actingAs($this->buyer, 'buyer')
        ->get(route('chat.index'))
        ->assertRedirect(route('buyer.messages'));

    auth()->guard('buyer')->logout();

    $this->actingAs($this->account, 'seller')
        ->get(route('chat.index'))
        ->assertRedirect(route('seller.messages'));
});

it('records a flag without a toast on top of the live hint', function (): void {
    $before = Message::query()->awaitingReview()->count();

    $this->actingAs($this->buyer, 'buyer')
        ->post(route('buyer.messages.store', $this->conversation), [
            'body' => 'whatsapp me on 03315084546',
        ])
        ->assertRedirect()
        // The composer warns live and the bubble carries the flag; a
        // toast would be the third telling, over the page header.
        ->assertSessionMissing('warning');

    // The record is what matters, and it still reaches the queue.
    expect(Message::query()->awaitingReview()->count())->toBe($before + 1);
});
