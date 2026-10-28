<x-layouts::admin
    :title="__('Platform settings')"
    :heading="__('Settings')"
    :lede="__('What the marketplace charges, and what it checks before a stone goes live.')"
>
    <form method="POST" action="{{ route('admin.settings.update') }}" class="cform setform">
        @csrf
        @method('PUT')

        <x-verum.form.errors />

        <x-verum.account.panel :title="__('Commission')" icon="scale">
            <p class="mute">
                {{ __('A percentage of each sale, recorded on the order as it is placed. Postage is excluded — the seller pays the carrier out of it.') }}
            </p>

            <label class="chk">
                <input type="checkbox" name="commission_enabled" value="1" @checked($settings['commission_enabled'])>
                <span>{{ __('Charge commission on every sale') }}</span>
            </label>

            <div class="fgrid">
                <x-verum.form.field
                    name="commission_percent"
                    type="number"
                    :label="__('Rate')"
                    :value="$settings['commission_percent']"
                    step="0.1"
                    prefix="%"
                    :hint="__('Between 0 and 50. A rate of zero with the toggle on charges nothing.')"
                />
            </div>
        </x-verum.account.panel>

        <x-verum.account.panel :title="__('Listing approval')" icon="check">
            <p class="mute">
                {{ __('Independent of commission: a platform that takes no cut may still want to see what goes up.') }}
            </p>

            <label class="chk">
                <input type="checkbox" name="listing_approval_required" value="1" @checked($settings['listing_approval_required'])>
                <span>{{ __('Every new listing needs approving before it is public') }}</span>
            </label>

            <p class="mute">
                {{ __('Listings already live stay live — switching this on queues what comes next, not the catalogue.') }}
            </p>
        </x-verum.account.panel>

        <x-verum.account.panel :title="__('Featured listings')" icon="spark">
            <p class="mute">
                {{ __('Premium sellers ask for a slot; you grant it. This only decides whether the queue is open.') }}
            </p>

            <label class="chk">
                <input type="checkbox" name="featuring_enabled" value="1" @checked($settings['featuring_enabled'])>
                <span>{{ __('Accept feature requests from premium sellers') }}</span>
            </label>

            <div class="fgrid">
                <x-verum.form.field
                    name="featured_slots"
                    type="number"
                    :label="__('Slots on the storefront')"
                    :value="$settings['featured_slots']"
                    min="1"
                    max="24"
                />
            </div>
        </x-verum.account.panel>

        <button class="btn btn--ruby" type="submit">{{ __('Save settings') }}</button>
    </form>
</x-layouts::admin>
