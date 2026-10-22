<?php

use App\Models\Category;
use App\Models\Listing;
use App\Models\Money;
use App\Models\Seller;
use App\Support\DisplayCurrency;
use App\Support\ExchangeRates;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Prices quoted in a visitor's own currency.
 *
 * Every sale is charged in USD; the second figure is a courtesy. What is
 * pinned here is that the USD figure never changes, and that a conversion
 * is shown only when it can be trusted.
 */
beforeEach(function (): void {
    ExchangeRates::forget();
    DisplayCurrency::flush();
    Cache::flush();

    /*
     * No real provider call from a test, and no default stub either: a
     * second Http::fake() for the same URL does not replace the first,
     * so each test states the provider behaviour it needs.
     */
    Http::preventStrayRequests();

    $this->cookieName = DisplayCurrency::cookieName();
});

it('shows USD alone when no country is chosen', function (): void {
    providerReturns(['USD' => 1, 'LKR' => 300.0]);

    expect((new Money(68000))->withLocal())->toBe('$680');
});

it('adds the local amount in brackets once a country is chosen', function (): void {
    Cache::put('verum:rates:current', [
        'rates' => ['LKR' => 300.0],
        'fetched_at' => now()->toIso8601String(),
    ], now()->addHour());

    // The charged figure leads, the conversion follows in brackets.
    expect(withCountry('LK', fn (): string => (new Money(68000))->withLocal()))
        ->toBe('$680 ('.nbsp('LKR 204,000').')');
});

it('converts from cents and rounds to a whole unit', function (): void {
    Cache::put('verum:rates:current', [
        'rates' => ['LKR' => 300.5],
        'fetched_at' => now()->toIso8601String(),
    ], now()->addHour());

    // A conversion is an estimate, so no decimals are claimed.
    $local = withCountry('LK', fn (): ?Money => (new Money(68050))->local());

    expect($local)->not->toBeNull()
        ->and($local->currency)->toBe('LKR')
        ->and($local->cents % 100)->toBe(0)
        ->and($local->cents)->toBe((int) round((68050 * 300.5) / 100) * 100);
});

it('never converts a country that already quotes in the base currency', function (): void {
    providerReturns(['USD' => 1, 'LKR' => 300.0]);

    // Ecuador uses the US dollar outright: a bracketed "($680)" would be
    // noise.
    expect(withCountry('EC', fn (): string => (new Money(68000))->withLocal()))->toBe('$680');
    expect(withCountry('US', fn (): string => (new Money(68000))->withLocal()))->toBe('$680');
});

it('falls back to USD alone when no rate is available', function (): void {
    Cache::put('verum:rates:current', [
        'rates' => ['EUR' => 0.9],
        'fetched_at' => now()->toIso8601String(),
    ], now()->addHour());

    // LKR is missing from this set, so there is nothing honest to show.
    expect(withCountry('LK', fn (): string => (new Money(68000))->withLocal()))->toBe('$680');
});

it('refuses rates that are too old to trust', function (): void {
    Cache::put('verum:rates:last-good', [
        'rates' => ['LKR' => 300.0],
        'fetched_at' => now()->subDays(30)->toIso8601String(),
    ]);

    /*
     * A second Http::fake() for the same URL does not replace the stub
     * from beforeEach — the first match wins — so the outage is set up
     * by faking afresh.
     */
    providerIsDown();

    // A month-old rate is worse than none: it can be badly wrong and
    // would still print as though it were today's.
    expect(withCountry('LK', fn (): string => (new Money(68000))->withLocal()))->toBe('$680');
});

it('uses the last good set when the provider is down', function (): void {
    Cache::put('verum:rates:last-good', [
        'rates' => ['LKR' => 300.0],
        'fetched_at' => now()->subHours(6)->toIso8601String(),
    ]);

    providerIsDown();

    $local = withCountry('LK', fn (): ?Money => (new Money(68000))->local());

    expect($local?->cents)->toBe(20400000);
});

it('keeps the page rendering when the provider fails outright', function (): void {
    Http::fake(['open.er-api.com/*' => fn () => throw new RuntimeException('network down')]);

    // A missing courtesy conversion must never take the shop down.
    $this->withUnencryptedCookie($this->cookieName, 'LK')
        ->get(route('shop'))
        ->assertOk();
});

it('stores the chosen country as a cookie', function (): void {
    providerReturns(['USD' => 1, 'LKR' => 300.0]);

    // The plain assertion: this cookie is deliberately not encrypted, and
    // assertCookie() would try to decrypt it.
    $this->post(route('currency.switch'), ['country' => 'LK'])
        ->assertRedirect()
        ->assertPlainCookie($this->cookieName, 'LK');
});

it('rejects a country it cannot quote', function (): void {
    providerReturns(['USD' => 1, 'LKR' => 300.0]);

    $this->from(route('shop'))
        ->post(route('currency.switch'), ['country' => 'ZZ'])
        ->assertSessionHasErrors('country');

    $this->from(route('shop'))
        ->post(route('currency.switch'), ['country' => '<script>'])
        ->assertSessionHasErrors('country');
});

it('prints both figures on a real listing page', function (): void {
    providerReturns(['USD' => 1, 'LKR' => 300.0]);

    $seller = Seller::factory()->create();
    $category = Category::factory()->create(['kind' => Category::KIND_STONE]);

    $listing = Listing::factory()->published()->for($seller)->for($category)->create([
        'listing_type' => Listing::TYPE_FIXED,
        'price_cents' => 68000,
        'currency' => 'USD',
    ]);

    $this->withUnencryptedCookie($this->cookieName, 'LK')
        ->get(route('listings.show', $listing))
        ->assertOk()
        // The charged figure leads; the conversion is bracketed.
        ->assertSee('$680')
        ->assertSee(nbsp('LKR 204,000'));
});

it('offers every country it lists a currency for', function (): void {
    providerReturns(['USD' => 1, 'LKR' => 300.0]);

    $options = DisplayCurrency::options();

    expect($options)->toHaveCount(105)
        ->and($options['LK']['currency'])->toBe('LKR')
        ->and($options['GB']['currency'])->toBe('GBP');
});

/** Stub a provider that answers with today's rates. */
function providerReturns(array $rates): void
{
    Http::fake(['open.er-api.com/*' => Http::response([
        'result' => 'success',
        'base_code' => 'USD',
        'time_last_update_utc' => now()->toRfc7231String(),
        'rates' => $rates,
    ])]);
}

/** Stub a provider that is down. */
function providerIsDown(): void
{
    Http::fake(['open.er-api.com/*' => Http::response(null, 503)]);
}

/**
 * ICU separates a currency code from its amount with a non-breaking
 * space, which is correct typography — so an expected string has to use
 * one too, and a plain space would fail on an invisible difference.
 */
function nbsp(string $text): string
{
    return str_replace(' ', "\u{00A0}", $text);
}

/**
 * Resolve a value as though the visitor had picked this country.
 *
 * Sets the cookie on the current request, because that is where
 * DisplayCurrency reads it from — the Cookie facade is the outgoing jar
 * and has no get() at all.
 */
function withCountry(string $iso, Closure $callback): mixed
{
    request()->cookies->set(DisplayCurrency::cookieName(), $iso);

    DisplayCurrency::flush();
    ExchangeRates::flush();

    try {
        return $callback();
    } finally {
        request()->cookies->remove(DisplayCurrency::cookieName());
        DisplayCurrency::flush();
        ExchangeRates::flush();
    }
}
