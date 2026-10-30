@php
    $section = $sections[$current];
@endphp

<x-layouts::admin
    :title="__('Platform settings')"
    :heading="__('Settings')"
    :lede="__('How the marketplace runs, and what it says when you act without writing your own words.')"
>
    {{-- One category at a time; each saves only what it shows. --}}
    <nav class="settabs" aria-label="{{ __('Settings sections') }}">
        @foreach ($sections as $key => $tab)
            <a
                href="{{ route('admin.settings', ['section' => $key]) }}"
                @class(['settabs__tab', 'is-on' => $current === $key])
                @if ($current === $key) aria-current="page" @endif
                wire:navigate
            >
                <x-verum.icon :name="$tab['icon']" />{{ __($tab['label']) }}
            </a>
        @endforeach
    </nav>

    <div class="setpanel">
        @if ($section['settings'] !== [])
            {{-- Another admin's save moves these switches here too; one
                 with unsaved changes holds still until it is sent. --}}
            <div data-live-region="settings-form" data-staff-area="settings">
                <form method="POST" action="{{ route('admin.settings.update', $current) }}" class="cform setform" data-live-form data-validate>
                    @csrf
                    @method('PUT')

                    <x-dynamic-component :component="'verum.admin.settings.'.$current" :$settings />

                    <button class="btn btn--ruby" type="submit">
                        {{ __('Save :section settings', ['section' => mb_strtolower(__($section['label']))]) }}
                    </button>
                </form>
            </div>
        @endif

        @if ($current === 'accounts')
            <x-verum.account.panel :title="__('Closing an account')" icon="clock" :hint="__('What “Delete account” does on the Sellers page.')">
                <ul class="setfacts">
                    <li>{{ __('The storefront and every listing come off the site at once, and no new order, bid or offer is taken.') }}</li>
                    <li>{{ __('Orders not yet dispatched are cancelled, and each buyer is sent an apology.') }}</li>
                    <li>{{ __('Orders on their way keep the account open until they could arrive, plus :days days for the buyer to dispute.', ['days' => $disputeDays]) }}</li>
                    <li>{{ __('An open case holds it open too. After that the account closes for good and its email cannot apply to sell again.') }}</li>
                </ul>
            </x-verum.account.panel>
        @endif

        @if ($notices !== [])
            {{-- Re-read after a save here or another admin's edit. --}}
            <div data-live-region="notice-templates" data-staff-area="settings">
                <x-verum.admin.settings.notice-defaults :$notices />
            </div>
        @endif
    </div>
</x-layouts::admin>
