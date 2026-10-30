<?php

use App\Actions\Chat\SendMessage;
use App\Actions\Chat\StartConversation;
use App\Models\AccountNotice;
use App\Models\Admin;
use App\Models\Buyer;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Message;
use App\Models\Seller;
use App\Models\SellerAccount;
use App\Notifications\AccountNoticeSent;
use App\Notifications\StaffAlert;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;

/**
 * What an admin's verdict on a flagged message does.
 *
 * Upheld: the sender is warned (and it counts), the other party gets a
 * safety notice that does not show them the flagged text. Cleared:
 * nobody hears anything. And a flag reaches every active admin's bell
 * the moment it is raised.
 */
beforeEach(function (): void {
    $this->admin = Admin::factory()->create();
    $this->account = SellerAccount::factory()->create(['approved_at' => now()]);
    $this->seller = Seller::factory()->create(['seller_account_id' => $this->account->id]);
    $this->buyer = Buyer::factory()->create();

    $listing = Listing::factory()->published()
        ->for($this->seller)
        ->for(Category::factory()->create(['kind' => Category::KIND_STONE]))
        ->create();

    $this->conversation = app(StartConversation::class)->handle($this->buyer, $this->seller, $listing);
});

function sendFlagged(string $from = Message::FROM_BUYER): Message
{
    $senderId = $from === Message::FROM_BUYER ? test()->buyer->id : test()->seller->id;

    return app(SendMessage::class)->handle(test()->conversation, $from, $senderId, 'Call me on 03315084546');
}

function ruleOnFlag(Message $message, string $verdict, ?string $note = null): TestResponse
{
    return test()->actingAs(test()->admin, 'admin')
        ->post(route('admin.moderation.review', $message), array_filter([
            'verdict' => $verdict,
            'message' => $note,
        ]));
}

it('alerts every active admin when a message is flagged', function (): void {
    $away = Admin::factory()->create(['is_active' => false]);
    Notification::fake();

    sendFlagged();

    Notification::assertSentTo($this->admin, StaffAlert::class);
    Notification::assertNotSentTo($away, StaffAlert::class);
});

it('warns the sender and tells the other party how to stay safe', function (): void {
    $message = sendFlagged();
    Notification::fake();

    ruleOnFlag($message, Message::REVIEW_UPHELD)->assertRedirect();

    $warning = AccountNotice::query()->for($this->buyer)->firstOrFail();
    $safety = AccountNotice::query()->for($this->account)->firstOrFail();

    expect($warning->kind)->toBe('flag_warning')
        ->and($warning->body)->toContain('03315084546')
        ->and(AccountNotice::warningCountFor($this->buyer))->toBe(1)
        ->and($safety->kind)->toBe('safety_notice')
        // The other side is protected, not handed the accusation.
        ->and($safety->body)->not->toContain('03315084546')
        ->and(AccountNotice::warningCountFor($this->account))->toBe(0);

    Notification::assertSentTo($this->buyer, AccountNoticeSent::class);
    Notification::assertSentTo($this->account, AccountNoticeSent::class);
});

it('warns the seller when the seller sent it', function (): void {
    $message = sendFlagged(Message::FROM_SELLER);
    Notification::fake();

    ruleOnFlag($message, Message::REVIEW_UPHELD)->assertRedirect();

    expect(AccountNotice::warningCountFor($this->account))->toBe(1)
        ->and(AccountNotice::query()->for($this->buyer)->value('kind'))->toBe('safety_notice');
});

it('sends the admin’s note to the sender only', function (): void {
    $message = sendFlagged();
    Notification::fake();

    ruleOnFlag($message, Message::REVIEW_UPHELD, 'Third time this week — next one is a suspension.')->assertRedirect();

    expect(AccountNotice::query()->for($this->buyer)->value('body'))->toContain('Third time this week')
        ->and(AccountNotice::query()->for($this->account)->value('body'))->not->toContain('Third time this week');
});

it('does not warn twice for the same message', function (): void {
    $message = sendFlagged();
    Notification::fake();

    ruleOnFlag($message, Message::REVIEW_UPHELD);
    ruleOnFlag($message, Message::REVIEW_UPHELD);

    expect(AccountNotice::warningCountFor($this->buyer))->toBe(1);
});

it('tells nobody about a false positive', function (): void {
    $message = sendFlagged();
    Notification::fake();

    ruleOnFlag($message, Message::REVIEW_CLEARED)->assertRedirect();

    expect($message->fresh()->review_state)->toBe(Message::REVIEW_CLEARED)
        ->and(AccountNotice::query()->count())->toBe(0);

    Notification::assertNothingSentTo($this->buyer);
});

it('shows a buyer’s warnings on the admin buyers list', function (): void {
    $message = sendFlagged();
    Notification::fake();
    ruleOnFlag($message, Message::REVIEW_UPHELD);

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.buyers'))
        ->assertOk()
        ->assertSeeInOrder([$this->buyer->email, '1']);
});

it('renders the queue with the uphold dialog showing the flagged words', function (): void {
    sendFlagged();

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.moderation'))
        ->assertOk()
        ->assertSee('data-live-region="moderation"', false)
        ->assertSee('Uphold this flag')
        ->assertSee('“Call me on 03315084546”', false);
});
