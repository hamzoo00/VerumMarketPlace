{{--
    Stands in for PayPal's approval page while PAYMENTS_DRIVER=fake. It
    says so at the top: nothing here is a real payment.
--}}
<x-layouts::account
    bare
    :title="__('PayPal simulator')"
    :kicker="__('Test mode · no real money')"
    :heading="$session['title']"
    :lede="__('This page stands in for PayPal while payments are simulated. Choose what PayPal would do.')"
>
    <div class="paysim">
        @if ($amount)
            <dl class="paysim__facts">
                <div>
                    <dt>{{ __('Amount') }}</dt>
                    <dd>{{ $amount->format() }}@isset($session['per']) / {{ $session['per'] }}@endisset</dd>
                </div>

                @isset($session['payee'])
                    <div>
                        <dt>{{ __('Paid to') }}</dt>
                        <dd>{{ __(':seller’s PayPal', ['seller' => $session['payee']]) }}</dd>
                    </div>
                @endisset

                @if ($fee)
                    <div>
                        <dt>{{ __('Commission to :app', ['app' => config('app.name')]) }}</dt>
                        <dd>{{ $fee->format() }}</dd>
                    </div>
                @endif
            </dl>
        @endif

        <form method="POST" action="{{ route('payments.simulator.decide', $id) }}" class="paysim__actions">
            @csrf

            <button class="btn btn--ruby btn--block" type="submit" name="decision" value="approve">
                <x-verum.icon name="check" />{{ __('Approve') }}
            </button>

            @if ($session['kind'] === 'checkout')
                {{-- A declined card still returns to the shop, where taking the money fails. --}}
                <button class="btn btn--ghost btn--block" type="submit" name="decision" value="decline">
                    <x-verum.icon name="alert" />{{ __('Decline the card') }}
                </button>
            @endif

            <button class="link" type="submit" name="decision" value="cancel">{{ __('Cancel and go back') }}</button>
        </form>
    </div>
</x-layouts::account>
