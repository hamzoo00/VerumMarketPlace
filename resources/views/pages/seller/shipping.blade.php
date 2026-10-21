@php
    $money = fn (?int $cents) => $cents === null ? null : number_format($cents / 100, 2, '.', '');
    // One blank row when nothing is configured, so the page is usable on
    // first visit without hunting for an "add" button.
    $rows = old('rates', $rates->isEmpty() ? [null] : $rates->all());
@endphp

<x-layouts::seller
    :account="$account"
    :title="__('Shipping')"
    :heading="__('Shipping')"
    :lede="__('Set what you charge to post an item and how long it takes. A listing can override this.')"
>
    <form method="POST" action="{{ route('seller.shipping.update') }}" class="cform shipform">
        @csrf
        @method('PUT')

        <x-verum.account.panel :title="__('Dispatch')" icon="clock"
            :hint="__('The part you control: how quickly you hand the parcel over after payment.')">
            <div class="cform__grid">
                <x-verum.form.field
                    name="handling_days"
                    :label="__('Dispatch within (working days)')"
                    type="number"
                    min="0"
                    :max="config('verum_shipping.handling.max_days')"
                    :value="$seller->handling_days"
                    :hint="__('0 means the same working day.')"
                    required
                />

                <x-verum.form.field
                    name="free_shipping_over"
                    :label="__('Free postage over')"
                    prefix="$"
                    type="number"
                    step="0.01"
                    min="0"
                    :value="$money($seller->free_shipping_over_cents)"
                    :hint="__('Leave empty for no free-postage threshold.')"
                />
            </div>

            <x-verum.form.textarea
                name="shipping_note"
                :label="__('Note for buyers')"
                :value="$seller->shipping_note"
                maxlength="500"
                :hint="__('Customs, packaging, anything a buyer should know before paying.')"
            />
        </x-verum.account.panel>

        <x-verum.account.panel :title="__('Postage options')" icon="globe"
            :hint="__('Each option is one service to one destination. Buyers pick from these at checkout.')">

            <div class="srates" data-rates
                data-max="{{ config('verum_shipping.limits.max_rates_per_seller') }}">
                @foreach ($rows as $index => $rate)
                    <x-verum.shipping.rate-row
                        :index="$index"
                        :rate="is_object($rate) ? $rate : null"
                        :services="$services"
                        :destinations="$destinations"
                    />
                @endforeach
            </div>

            <p class="srates__empty" data-rates-empty @if (count($rows) > 0) hidden @endif>
                {{ __('No postage options yet. Add one so buyers know what delivery costs.') }}
            </p>

            <button class="btn btn--ghost btn--sm" type="button" data-add-rate>
                <x-verum.icon name="plus" />{{ __('Add an option') }}
            </button>

            {{--
                The template the Add button clones. Kept in the markup rather
                than built in JS so the fields stay in one place.
            --}}
            <template data-rate-template>
                <x-verum.shipping.rate-row
                    index="__INDEX__"
                    :services="$services"
                    :destinations="$destinations"
                />
            </template>
        </x-verum.account.panel>

        <div class="cform__actions">
            <button class="btn btn--ruby" type="submit">{{ __('Save shipping') }}</button>
        </div>
    </form>
</x-layouts::seller>
