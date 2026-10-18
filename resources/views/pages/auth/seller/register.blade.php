<x-layouts::account
    :title="__('Apply to sell')"
    :kicker="__('For cutters, miners and dealers')"
    :heading="__('Apply to sell on :app', ['app' => config('app.name')])"
    :lede="__('Every application is reviewed by hand. You will hear back within 3 business days.')"
    wide
>
    <x-verum.auth.switcher active="seller" />

    <x-verum.auth.google-button area="seller" action="up" />

    <form method="POST" action="{{ route('seller.register') }}" class="cform" data-validate>
        @csrf

        <fieldset class="fgrp">
            <legend class="lbl">{{ __('Your account') }}</legend>
            <div class="cform__grid">
                <x-verum.form.field name="name" :label="__('Your name')" autocomplete="name" required />
                <x-verum.form.field name="email" type="email" :label="__('Business email')" autocomplete="email" required />
                <x-verum.form.field
                    name="password"
                    type="password"
                    :label="__('Password')"
                    autocomplete="new-password"
                    rule="password"
                    required
                />
                <x-verum.form.field
                    name="password_confirmation"
                    type="password"
                    :label="__('Confirm password')"
                    autocomplete="new-password"
                    confirms="password"
                    required
                />
            </div>
        </fieldset>

        <fieldset class="fgrp">
            <legend class="lbl">{{ __('Your business') }}</legend>
            <div class="cform__grid">
                <x-verum.form.field name="business_name" :label="__('Store name')" required full />

                <x-verum.form.country name="country" required />

                <x-verum.form.field name="trade_license_number" :label="__('Trade license number (optional)')" />

                <x-verum.form.phone name="phone" :label="__('Phone number')" required />

                <x-verum.form.select
                    name="category"
                    :label="__('What do you sell?')"
                    :options="\App\Support\SellerCategories::options()"
                    required
                    full
                />

                <x-verum.form.specialties category-name="category" name="specialties" />

                <x-verum.form.textarea name="about" :label="__('Tell us about your house')" :rows="5" />
            </div>
        </fieldset>

        <button class="btn btn--ruby btn--lg btn--block" type="submit">
            {{ __('Submit application') }}
        </button>

        <p class="cform__note">
            <x-verum.icon name="info" />
            {{ __('You can sign in straight away to follow your application, but listings only go live after approval.') }}
        </p>
    </form>

    <p class="authcard__alt">
        {{ __('Already applied?') }}
        <a class="link" href="{{ route('seller.login') }}" wire:navigate>{{ __('Sign in') }}</a>
    </p>

    <x-slot:aside>
        <h2 class="h3">{{ __('What we need') }}</h2>
        <ul class="checklist">
            <li><x-verum.icon name="file" />{{ __('A trade license in your name or your company name') }}</li>
            <li><x-verum.icon name="user" />{{ __('Government photo ID for the account holder') }}</li>
            <li><x-verum.icon name="gem" />{{ __('One sample listing with its lab report') }}</li>
            <li><x-verum.icon name="box" />{{ __('An insured courier that can collect from you') }}</li>
        </ul>
        <p class="authaside__note">
            {{ __('We will ask for documents during the verification call, not on this form.') }}
        </p>
    </x-slot:aside>
</x-layouts::account>
