<?php

use App\Actions\Chat\SendMessage;
use App\Actions\Chat\StartConversation;
use App\Models\Buyer;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Message;
use App\Models\Seller;
use App\Models\SellerAccount;

it('groups a run of messages from one sender', function (): void {
    $account = SellerAccount::factory()->create(['approved_at' => now()]);
    $seller = Seller::factory()->create(['seller_account_id' => $account->id]);
    $category = Category::factory()->create(['kind' => Category::KIND_STONE]);
    $buyer = Buyer::factory()->create();
    $listing = Listing::factory()->published()->for($seller)->for($category)->create();

    $conversation = app(StartConversation::class)->handle($buyer, $seller, $listing);
    $send = app(SendMessage::class);

    $send->handle($conversation, Message::FROM_BUYER, $buyer->id, 'hi');
    $send->handle($conversation, Message::FROM_BUYER, $buyer->id, 'its me');
    $send->handle($conversation, Message::FROM_SELLER, $seller->id, 'hello');

    $html = $this->actingAs($buyer, 'buyer')
        ->get(route('buyer.messages.show', $conversation))
        ->assertOk()
        ->getContent();

    // The second of the run is grouped; the speaker change is not.
    expect(substr_count($html, 'bubble--grouped'))->toBe(1)
        // Two panes: the inbox travels with the open conversation, so
        // choosing a thread changes a pane rather than a page.
        ->and($html)->toContain('class="chat"')
        ->and($html)->toContain('data-thread-list');
});

it('does not group across a speaker change or a long gap', function (): void {
    $account = SellerAccount::factory()->create(['approved_at' => now()]);
    $seller = Seller::factory()->create(['seller_account_id' => $account->id]);
    $category = Category::factory()->create(['kind' => Category::KIND_STONE]);
    $buyer = Buyer::factory()->create();
    $listing = Listing::factory()->published()->for($seller)->for($category)->create();

    $conversation = app(StartConversation::class)->handle($buyer, $seller, $listing);
    $send = app(SendMessage::class);

    $first = $send->handle($conversation, Message::FROM_BUYER, $buyer->id, 'morning');

    // An hour later is a new utterance, not a continuation.
    $second = $send->handle($conversation, Message::FROM_BUYER, $buyer->id, 'still there?');
    $second->forceFill(['created_at' => $first->created_at->addHour()])->save();

    expect($second->fresh()->followsSameSpeakerAs($first))->toBeFalse();
});
