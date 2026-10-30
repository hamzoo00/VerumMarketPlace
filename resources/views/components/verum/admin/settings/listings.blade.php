@props(['settings'])

<x-verum.account.panel :title="__('Listing approval')" icon="check" :hint="__('Independent of commission: a platform that takes no cut may still want to see what goes up.')">
    <label class="chk">
        <input type="checkbox" name="listing_approval_required" value="1" @checked($settings['listing_approval_required'])>
        <span>{{ __('Every new listing needs approving before it is public') }}</span>
    </label>

    <p class="mute">
        {{ __('Listings already live stay live — switching this on queues what comes next, not the catalogue.') }}
    </p>
</x-verum.account.panel>

<x-verum.account.panel :title="__('Featured listings')" icon="spark" :hint="__('Premium sellers ask for a slot; you grant it.')">
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
            required
        />
    </div>
</x-verum.account.panel>
