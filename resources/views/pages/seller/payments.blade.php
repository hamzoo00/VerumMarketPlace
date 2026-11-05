@php
    use App\Models\Payment;

    $simulated = config('verum_payments.driver') !== 'paypal';
    $subscribed = $storefront->hasPremiumSubscription();
    $byTeam = $isPremium && $storefront->premium_until === null && ! $subscribed;
@endphp

<x-layouts::seller
    :account="$account"
    :title="__('Payments & premium')"
    :heading="__('Payments & premium')"
    :lede="__('Buyers pay you through PayPal, straight into your own account.')"
>
    @if ($simulated)
        {{-- Said plainly, so nobody mistakes a test click for a real payment. --}}
        <div class="notice" role="status">
            <x-verum.icon name="info" />
            <div>
                <b>{{ __('Test mode') }}</b>
                <p>{{ __('PayPal is simulated on this site. No real PayPal account or money is involved.') }}</p>
            </div>
        </div>
    @endif

    <div class="paygrid">
        <x-verum.account.panel :title="__('Getting paid')" icon="card"
            :hint="__('Every sale is paid into your PayPal. PayPal handles buyer protection and any dispute.')">
            @if ($storefront->canReceivePayments())
                <p class="paystate is-on">
                    <x-verum.icon name="check" />
                    {{ __('PayPal connected') }}
                    <span class="mono">…{{ substr((string) $storefront->paypal_merchant_id, -4) }}</span>
                </p>

                <p class="mute">
                    @if ($commission > 0)
                        {{ __('PayPal sends :rate% of each sale (after any discount, not postage) to :app as commission, and the rest to you. You never pay it separately.', [
                            'rate' => rtrim(rtrim(number_format($commission, 2), '0'), '.'),
                            'app' => config('app.name'),
                        ]) }}
                    @else
                        {{ __('No commission is taken at the moment: the whole sale reaches you.') }}
                    @endif
                </p>

                <form method="POST" action="{{ route('seller.payments.disconnect') }}"
                    data-confirm="{{ __('Disconnect PayPal? Buyers cannot pay you until you connect an account again.') }}">
                    @csrf
                    @method('DELETE')
                    <button class="link" type="submit">{{ __('Disconnect this account') }}</button>
                </form>
            @else
                <p class="paystate">
                    <x-verum.icon name="alert" />
                    {{ __('Not connected — buyers cannot pay you yet') }}
                </p>

                <p class="mute">
                    {{ __('You need a PayPal business account in a country PayPal serves. Connecting takes a minute; you come straight back here.') }}
                </p>

                <form method="POST" action="{{ route('seller.payments.connect') }}">
                    @csrf
                    <button class="btn btn--ruby" type="submit">
                        <x-verum.icon name="card" />{{ __('Connect PayPal') }}
                    </button>
                </form>
            @endif
        </x-verum.account.panel>

        <x-verum.account.panel :title="__('Premium')" icon="badge"
            :hint="__(':price a month through PayPal. Ask for your listings to be featured on the home page.', ['price' => $premiumPrice->format()])">
            @if ($byTeam)
                <p class="paystate is-on">
                    <x-verum.icon name="check" />{{ __('Premium, granted by our team') }}
                </p>
            @elseif ($subscribed)
                <p class="paystate is-on">
                    <x-verum.icon name="check" />
                    {{ __('Premium · renews monthly') }}
                </p>

                @if ($storefront->premium_until)
                    <p class="mute">{{ __('Paid through :date.', ['date' => $storefront->premium_until->format('j M Y')]) }}</p>
                @endif

                <form method="POST" action="{{ route('seller.payments.unsubscribe') }}"
                    data-confirm="{{ __('Cancel premium? It stays on until the end of the month you have paid for.') }}">
                    @csrf
                    @method('DELETE')
                    <button class="link" type="submit">{{ __('Cancel subscription') }}</button>
                </form>
            @else
                @if ($isPremium && $storefront->premium_until)
                    <p class="paystate is-on">
                        <x-verum.icon name="clock" />
                        {{ __('Premium until :date — not renewing', ['date' => $storefront->premium_until->format('j M Y')]) }}
                    </p>
                @elseif ($storefront->premium_subscription_status === 'approval_pending')
                    <p class="mute">{{ __('A subscription was started but not approved in PayPal. You can start again below.') }}</p>
                @endif

                <form method="POST" action="{{ route('seller.payments.subscribe') }}">
                    @csrf
                    <button class="btn btn--ruby" type="submit">
                        <x-verum.icon name="badge" />
                        {{ __('Subscribe for :price / month', ['price' => $premiumPrice->format()]) }}
                    </button>
                </form>
            @endif
        </x-verum.account.panel>
    </div>

    <x-verum.account.panel :title="__('Recent payments')" icon="scale">
        @if ($payments->isEmpty())
            <x-verum.account.empty
                icon="card"
                :title="__('Nothing yet')"
                :body="__('Payments for your sales and premium appear here as PayPal confirms them.')"
            />
        @else
            <div class="tablewrap">
                <table class="atable">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('Date') }}</th>
                            <th scope="col">{{ __('For') }}</th>
                            <th scope="col">{{ __('Paid') }}</th>
                            <th scope="col">{{ __('Commission') }}</th>
                            <th scope="col">{{ __('You received') }}</th>
                            <th scope="col">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr>
                                <th scope="row" data-label="{{ __('Date') }}">
                                    {{ ($payment->completed_at ?? $payment->created_at)?->format('j M Y') }}
                                </th>
                                <td data-label="{{ __('For') }}">
                                    @if ($payment->order)
                                        <a class="link mono" href="{{ route('seller.orders.show', $payment->order) }}" wire:navigate>
                                            {{ $payment->order->reference }}
                                        </a>
                                    @else
                                        {{ __('Premium, one month') }}
                                    @endif
                                </td>
                                <td data-label="{{ __('Paid') }}">{{ $payment->amount()->format() }}</td>
                                <td data-label="{{ __('Commission') }}">
                                    {{ $payment->purpose === Payment::PURPOSE_ORDER ? $payment->fee()->format() : '—' }}
                                </td>
                                <td data-label="{{ __('You received') }}">
                                    {{ $payment->purpose === Payment::PURPOSE_ORDER ? $payment->net()->format() : '—' }}
                                </td>
                                <td data-label="{{ __('Status') }}">
                                    <span @class(['chip', 'chip--live' => $payment->isCompleted()])>{{ $payment->statusLabel() }}</span>
                                    @if ($payment->disputed_at)
                                        <span class="chip">{{ __('PayPal case') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-verum.account.panel>
</x-layouts::seller>
