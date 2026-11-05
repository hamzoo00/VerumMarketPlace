{{--
    Stands in for a courier while TRACKING_DRIVER=fake. Each button is one
    scan, handed to exactly the code a real carrier's webhook reaches —
    the buyer's email, the bell and the order page all react as they will
    in production.
--}}
<x-layouts::account
    bare
    :title="__('Courier simulator')"
    :kicker="__('Test mode · no real courier')"
    :heading="__('Scan :reference', ['reference' => $order->reference])"
    :lede="__('Press what the carrier would report next. The order updates live for the buyer and the seller.')"
>
    <div class="paysim">
        <dl class="paysim__facts">
            <div>
                <dt>{{ __('Carrier') }}</dt>
                <dd>{{ \App\Support\Carriers::name($order->tracking_carrier) }}</dd>
            </div>
            <div>
                <dt>{{ __('Tracking number') }}</dt>
                <dd class="mono">{{ $order->tracking_number }}</dd>
            </div>
            <div>
                <dt>{{ __('Order') }}</dt>
                <dd>{{ $order->statusLabel() }}</dd>
            </div>
        </dl>

        <form method="POST" action="{{ $action }}" class="couriersim">
            @csrf

            <x-verum.form.field
                name="location"
                :label="__('Where the scan happened (optional)')"
                full
                :maxlength="config('verum_tracking.limits.location')"
            />

            <div class="couriersim__scans">
                @foreach ($scans as $status => $label)
                    <button @class(['btn', 'btn--sm', 'btn--ruby' => $status === 'delivered', 'btn--ghost' => $status !== 'delivered'])
                        type="submit" name="status" value="{{ $status }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </form>

        <x-verum.order.timeline :$order :empty="__('Nothing scanned yet.')" />
    </div>
</x-layouts::account>
