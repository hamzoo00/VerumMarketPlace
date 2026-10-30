@props(['settings'])

<x-verum.account.panel :title="__('Commission')" icon="scale" :hint="__('A percentage of each sale, recorded on the order as it is placed.')">
    <p class="mute">
        {{ __('Postage is excluded — the seller pays the carrier out of it. Changing the rate never rewrites an order already placed.') }}
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
            min="0"
            max="50"
            prefix="%"
            required
            :hint="__('Between 0 and 50. A rate of zero with the toggle on charges nothing.')"
        />
    </div>
</x-verum.account.panel>
