<?php

namespace App\Providers;

use App\View\Composers\AccountNavComposer;
use App\View\Composers\NavigationComposer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
        // The chat shell picks a layout per audience, so it needs the
        // buyer nav too — the inbox is not under pages.buyer.
        View::composer('components.verum.chat.shell', AccountNavComposer::class);
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
