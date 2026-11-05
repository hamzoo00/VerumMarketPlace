<?php

namespace App\Providers;

use App\Contracts\ParcelTracker;
use App\Contracts\PaymentGateway;
use App\Support\Payments\FakePaymentGateway;
use App\Support\Payments\PayPalGateway;
use App\Support\Tracking\FakeParcelTracker;
use App\Support\Tracking\NullParcelTracker;
use App\Support\Tracking\SeventeenTrack;
use App\View\Composers\AccountNavComposer;
use App\View\Composers\NavigationComposer;
use App\View\Composers\NotificationBellComposer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // PAYMENTS_DRIVER picks who answers. The simulator moves no money,
        // so production refuses it outright rather than quietly faking
        // every checkout.
        $this->app->bind(PaymentGateway::class, function (): PaymentGateway {
            if (config('verum_payments.driver') === 'paypal') {
                return $this->app->make(PayPalGateway::class);
            }

            if ($this->app->isProduction()) {
                throw new RuntimeException('PAYMENTS_DRIVER must be "paypal" in production.');
            }

            return $this->app->make(FakePaymentGateway::class);
        });

        // TRACKING_DRIVER, the same way. The courier simulator would let a
        // seller "deliver" their own parcel under a carrier's name, so it
        // never runs in production; "off" is the honest alternative.
        $this->app->bind(ParcelTracker::class, function (): ParcelTracker {
            return match (config('verum_tracking.driver')) {
                '17track' => $this->app->make(SeventeenTrack::class),
                'off' => $this->app->make(NullParcelTracker::class),
                default => $this->app->isProduction()
                    ? throw new RuntimeException('TRACKING_DRIVER must be "17track" or "off" in production.')
                    : $this->app->make(FakeParcelTracker::class),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        // Site chrome needs nav state on every public page.
        View::composer(
            ['components.verum.header', 'components.verum.footer'],
            NavigationComposer::class,
        );

        // Private buyer/seller areas get their own navigation.
        View::composer('pages.buyer.*', AccountNavComposer::class);
        View::composer('pages.seller.*', AccountNavComposer::class);
        // The account shell picks a layout per audience, so it needs the
        // buyer nav too — the inbox and notifications are not under
        // pages.buyer.
        View::composer('components.verum.account.shell', AccountNavComposer::class);

        // The bell resolves its own audience wherever it is placed.
        View::composer('components.verum.notifications.bell', NotificationBellComposer::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        // One strong rule for every account type, in every environment.
        // Mirrored client-side by resources/js/verum/validate.js — change both.
        Password::defaults(fn (): Password => Password::min(config('verum_auth.password.min'))
            ->mixedCase()
            ->letters()
            ->numbers()
            ->symbols(),
        );
    }
}
