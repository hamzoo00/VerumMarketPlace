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
    'a lot reference' => 'Lot 0145 closing soon, reserve 12-000-444 met.',
    'a date-ish serial' => 'Serial 2026-10-21-0098 is printed on the cert.',
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
]);

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
