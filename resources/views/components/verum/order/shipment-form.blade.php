@props(['order'])

@php
    use App\Support\Carriers;
    use App\Support\ShipmentStatuses;

    $choices = ShipmentStatuses::pickableFor($order);
@endphp

{{--
    The seller's half of the timeline: where the parcel has got to, in
    the seller's words. Live, so the step appears without a reload; with
    scripting off it posts and comes back to the same page.
--}}
@if ($choices !== [])
    <form method="POST" action="{{ route('seller.orders.shipment.store', $order) }}" class="shipform cform" data-live-form data-validate>
        @csrf

        <div class="fgrid">
            <x-verum.form.select
                name="status"
                :label="__('What has happened')"
                :options="$choices"
                :placeholder="__('Choose…')"
                required
            />
            <x-verum.form.field
                name="location"
                :label="__('Where (optional)')"
                :maxlength="config('verum_tracking.limits.location')"
            />
            <x-verum.form.textarea
                name="description"
                :label="__('Note for the buyer')"
                rows="2"
                :maxlength="config('verum_tracking.limits.description')"
            />
        </div>

        <div class="shipform__foot">
            <small class="mute">{{ __('The buyer sees this on their order, marked as from you. Contact details are not allowed — use messages.') }}</small>
            <button class="btn btn--ghost" type="submit">
                <x-verum.icon name="plus" />{{ __('Add update') }}
            </button>
        </div>
    </form>
@endif

{{-- A mistyped number is common and otherwise permanent: the buyer's link
     and the carrier's scans would both follow the wrong parcel. --}}
@if ($order->isInTransit() && $order->tracking_number)
    <details class="shipfix">
        <summary>{{ __('Wrong tracking number?') }}</summary>

        <form method="POST" action="{{ route('seller.orders.shipment.update', $order) }}" class="cform" data-live-form data-validate>
            @csrf
            @method('PUT')

            <div class="fgrid">
                <x-verum.form.select
                    name="tracking_carrier"
                    :label="__('Carrier')"
                    :options="Carriers::options()"
                    :selected="Carriers::has($order->tracking_carrier) ? $order->tracking_carrier : null"
                    required
                />
                <x-verum.form.field
                    name="tracking_number"
                    :label="__('Correct tracking number')"
                    maxlength="40"
                    required
                    :pattern="Carriers::trackingPattern()"
                    :data-pattern-message="__('Letters, digits and dashes, as printed on the receipt.')"
                />
            </div>

            <button class="btn btn--ghost btn--sm" type="submit">{{ __('Correct it') }}</button>
            <small class="mute">{{ __('The buyer is told, and the carrier’s scans follow the new number.') }}</small>
        </form>
    </details>
@endif
