<?php

use App\Models\AccountNotice;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Seller;
use App\Models\SellerAccount;
use App\Notifications\AccountNoticeSent;
use App\Support\NoticeCatalogue;
use App\Support\NoticeTemplates;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;

/**
 * Rewording the standard messages from Settings.
 *
 * Pinned here: an edit is what the next notice sends, placeholders and
 * all; a reset goes back to the wording in code; a placeholder the
 * message cannot fill is refused rather than mailed as written; and
 * nothing already sent is rewritten.
 */
beforeEach(function (): void {
    NoticeTemplates::flush();

    $this->admin = Admin::factory()->create();
    $this->account = SellerAccount::factory()->create(['approved_at' => now()]);
    $this->seller = Seller::factory()->create(['seller_account_id' => $this->account->id]);
});

afterEach(fn () => NoticeTemplates::flush());

function reword(string $kind, string $subject, string $body): TestResponse
{
    return test()->actingAs(test()->admin, 'admin')
        ->put(route('admin.notices.update', $kind), ['subject' => $subject, 'body' => $body]);
}

it('sends the admin’s wording from the next message on', function (): void {
    Notification::fake();

    reword('warning', 'Heads up from :app', 'Please read the seller rules again.')->assertRedirect();

    $this->actingAs($this->admin, 'admin')
        ->put(route('admin.sellers.update', $this->account), ['action' => 'warn'])
        ->assertRedirect();

    expect(AccountNotice::query()->for($this->account)->value('body'))->toBe('Please read the seller rules again.');

    Notification::assertSentTo(
        $this->account,
        AccountNoticeSent::class,
        fn (AccountNoticeSent $sent): bool => $sent->toMail($this->account)->subject === 'Heads up from '.config('app.name'),
    );
});

it('fills the placeholders in an edited message', function (): void {
    $listing = Listing::factory()->for($this->seller)
        ->for(Category::factory()->create(['kind' => Category::KIND_STONE]))
        ->create(['title' => 'Padparadscha sapphire']);

    reword('listing_rejected', 'About “:listing”', 'Please retake the photos of “:listing” in daylight.');

    $this->actingAs($this->admin, 'admin')
        ->put(route('admin.listings.review', $listing), ['action' => 'reject'])
        ->assertRedirect();

    expect($listing->fresh()->approval_note)->toBe('Please retake the photos of “Padparadscha sapphire” in daylight.');
});

it('goes back to the standard wording when reset', function (): void {
    reword('warning', 'Heads up', 'Custom words.');

    $this->actingAs($this->admin, 'admin')
        ->delete(route('admin.notices.reset', 'warning'))
        ->assertRedirect();

    expect(NoticeCatalogue::isEdited('warning'))->toBeFalse()
        ->and(NoticeCatalogue::defaultBody('warning'))->toContain('formal warning');
});

it('refuses a placeholder the message cannot fill', function (): void {
    reword('warning', 'Heads up', 'About your listing :listing')
        ->assertSessionHasErrors(['body' => ':listing cannot be filled in for this message. You can use :app.']);

    expect(NoticeCatalogue::isEdited('warning'))->toBeFalse();
});

it('stores an edited message as plain text', function (): void {
    reword('warning', '<b>Heads</b> up', 'Read this <script>alert(1)</script>')->assertRedirect();

    expect(NoticeCatalogue::subjectTemplate('warning'))->toBe('Heads up')
        ->and(NoticeCatalogue::bodyTemplate('warning'))->not->toContain('<script>');
});

it('refuses a message that is empty once markup is removed', function (): void {
    reword('warning', 'Heads up', '<p> </p>')->assertSessionHasErrors('body');
});

it('does not rewrite a message already sent', function (): void {
    Notification::fake();

    $this->actingAs($this->admin, 'admin')
        ->put(route('admin.sellers.update', $this->account), ['action' => 'warn']);

    reword('warning', 'Heads up', 'New words.');

    expect(AccountNotice::query()->for($this->account)->value('body'))->toContain('formal warning');
});

it('returns 404 for a message that does not exist', function (): void {
    reword('nonsense', 'Subject', 'Body')->assertNotFound();
});

it('lets only an admin reword a message', function (): void {
    $this->actingAs($this->account, 'seller')
        ->put(route('admin.notices.update', 'warning'), ['subject' => 'Hijacked', 'body' => 'Hijacked'])
        ->assertRedirect(route('admin.login'));

    expect(NoticeCatalogue::isEdited('warning'))->toBeFalse();
});

it('shows an edited message as edited, with who changed it and a way back', function (): void {
    reword('suspended', 'Paused', 'Your shop is paused while we check something.');

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.settings', ['section' => 'accounts']))
        ->assertOk()
        ->assertSee('Your shop is paused while we check something.')
        ->assertSee('by '.$this->admin->username)
        ->assertSee('Reset to standard wording')
        ->assertSee('Edit message');
});

it('answers a live save with JSON', function (): void {
    $this->actingAs($this->admin, 'admin')
        ->putJson(route('admin.notices.update', 'warning'), ['subject' => 'Heads up', 'body' => 'Words.'])
        ->assertOk()
        ->assertJsonPath('status', 'success');
});
