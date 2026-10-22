<?php

use App\Support\MessageModeration;

/**
 * Off-platform detection.
 *
 * Both halves matter equally. A missed attempt costs a fee; a false
 * positive on a certificate number or a price would flag honest traders
 * on every listing — eBay's filter is known for exactly that mistake, so
 * the clean cases are pinned as carefully as the dirty ones.
 */
it('leaves an ordinary gem conversation alone', function (string $body): void {
    expect(MessageModeration::inspect($body)->shouldFlag())->toBeFalse();
})->with([
    'a question' => 'Is the 2.05 ct ruby still available?',
    'a price' => 'I can do 1500 for it, final offer.',
    'specs' => 'Weight is 3.42 ct, dimensions 9.1 x 7.3 mm.',
    // The one that bit eBay: a reference that looks like a number.
    'a certificate number' => 'Report number VR-2026-002448 confirms the origin.',
    'a lot reference' => 'Lot 0145 closing soon, reserve met.',
    'a date-ish serial' => 'Serial 2026-10-21-0098 is printed on the cert.',
    // A lab number is ten digits — the same shape as a mobile. It is
    // blanked as a reference before the phone rule ever sees it.
    'a GIA number' => 'GIA 2185647321 lists it as unheated.',
    'a GIA number with a word' => 'GIA report 2185647321 is attached.',
    'a hashed lab number' => 'IGI #425192837 came back clean.',
    'an invoice' => 'Invoice 100245 was settled last week.',
    // Honest disclosure. A seller must be able to say a stone is
    // uncertified without being warned for it.
    'no certificate, stated plainly' => 'This stone has no certificate, priced accordingly.',
    'a pending report' => 'The lab report is pending, I will share it.',
    // Ordinary English that collides with app names.
    'signal as a verb' => 'Please signal your intent before the lot closes.',
    'line as a noun' => 'Drop me a line when the new line of stones lands.',
    'a rival named in passing' => 'It is cheaper than eBay, honestly.',
    'a place name' => 'An Amazon rainforest sapphire, untreated.',
]);

it('flags a phone number written without spaces', function (string $body): void {
    // The common case, and the one that was missed: a local mobile
    // typed as one unbroken run passed straight through while the
    // pattern was narrowed around certificate numbers.
    $verdict = MessageModeration::inspect($body);

    expect($verdict->shouldFlag())->toBeTrue()
        ->and($verdict->flags)->toContain('phone');
})->with([
    'bare run' => '03315084546',
    'in a sentence' => 'my number is 03315084546 call me',
    'hyphen groups' => 'reach me 0300-1234567',
    'no space after the code' => '+923315084546',
    'bracketed' => '(0331) 508 4546',
    'dashed with country code' => '92-331-5084546',
]);

it('flags an attempt to move the deal off the platform', function (string $body, string $rule): void {
    $verdict = MessageModeration::inspect($body);

    expect($verdict->shouldFlag())->toBeTrue()
        ->and($verdict->flags)->toContain($rule);
})->with([
    'plain email' => ['write to zarar@example.com', 'email'],
    'obfuscated email' => ['email me at zarar (at) gmail dot com', 'email'],
    'phone with country code' => ['call me on +92 300 1234567', 'phone'],
    'spaced phone' => ['ring 0300 123 4567 any time', 'phone'],
    'messaging app' => ['add me on whatsapp', 'messaging_app'],
    'off-platform payment' => ['pay by western union and save the fees', 'off_platform_payment'],
    'solicitation' => ['lets deal direct, contact me at my shop', 'solicitation'],
    // Rails that cannot be reversed once sent, which is why a scammer
    // asks for one.
    'crypto' => ['send usdt to my binance', 'off_platform_payment'],
    'a local wallet' => ['send it to my easypaisa account', 'off_platform_payment'],
    'bank details' => ['I will share my iban', 'off_platform_payment'],
    // The explicit pitch. Nobody says this by accident.
    'fee avoidance' => ['lets avoid the fee and do it direct', 'fee_avoidance'],
    'cheaper direct' => ['it is cheaper if you pay me direct', 'fee_avoidance'],
    // Undoing a real sale is worse than a contact swap: the record then
    // shows nothing happened and neither side is covered.
    'cancel and settle privately' => ['cancel the order and we settle privately', 'cancel_to_relist'],
    'abbreviated app' => ['my tg id is gemdealer', 'messaging_app'],
    'app with a number' => ['lets move to wa number 03001234567', 'messaging_app'],
]);

it('warns, without flagging, on a rival storefront', function (): void {
    // Weight 2 on its own: worth a nudge, not an admin's time, until
    // it lands beside something else.
    $verdict = MessageModeration::inspect('check my alibaba store for more');

    expect($verdict->flags)->toContain('social')
        ->and($verdict->shouldWarn())->toBeTrue()
        ->and($verdict->shouldFlag())->toBeFalse();
});

it('flags talking someone out of the paperwork, not out of having none', function (): void {
    /*
     * Gem-specific. An uncertified stone is ordinary and must be
     * disclosable; persuading a buyer to skip the lab is how a
     * synthetic gets sold as natural with no record of the claim.
     */
    expect(MessageModeration::inspect('no need for a certificate, trust me on this stone')->flags)
        ->toContain('authenticity_dodge');

    expect(MessageModeration::inspect('This stone has no certificate.')->flags)
        ->not->toContain('authenticity_dodge');
});

it('scores several signals together', function (): void {
    $verdict = MessageModeration::inspect('whatsapp me on +92 300 1234567, western union is fine');

    expect($verdict->score)->toBeGreaterThan(6)
        ->and($verdict->flags)->toHaveCount(3);
});

it('warns before it flags', function (): void {
    // A single weaker signal is worth a nudge, not an admin's time.
    $verdict = MessageModeration::inspect('my instagram is @gemdealer92');

    expect($verdict->shouldWarn())->toBeTrue()
        ->and($verdict->shouldFlag())->toBeFalse()
        ->and($verdict->warning())->toContain('social');
});

it('says nothing about a clean message', function (): void {
    expect(MessageModeration::inspect('Lovely stone, thank you.')->warning())->toBeNull();
});
