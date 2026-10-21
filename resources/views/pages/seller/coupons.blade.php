@php
    $money = fn (?int $cents) => $cents === null ? null : number_format($cents / 100, 2, '.', '');
@endphp

<x-layouts::seller
    :account="$account"
    :title="__('Coupon campaigns')"
    :heading="__('Coupons')"
    :lede="__('Discount codes buyers can apply to your items.')"
>
    <x-verum.account.panel :title="__('New code')" icon="spark"
        :hint="__('Letters and numbers only, so it is easy to read out and type.')">
        <form method="POST" action="{{ route('seller.coupons.store') }}" class="cform couponform" data-coupon-form>
            @csrf

            <div class="cform__grid">
                <x-verum.form.field
                    name="code"
                    :label="__('Code')"
                    :maxlength="config('verum_offers.coupons.code_max')"
                    :hint="__('e.g. WELCOME10')"
                    required
                />

                <x-verum.form.select
                    name="type"
                    :label="__('Discount')"
                    :options="$types"
                    :selected="old('type', \App\Models\Coupon::TYPE_PERCENT)"
                    required
                />

                <x-verum.form.field
                    name="percent"
                    :label="__('Percent off')"
                    type="number"
                    min="1"
                    :max="config('verum_offers.coupons.max_percent')"
                    :value="old('percent')"
                    data-coupon-when="percent"
                />

                <x-verum.form.field
                    name="amount"
                    :label="__('Amount off')"
                    prefix="$"
                    type="number"
                    step="0.01"
                    min="0.01"
                    :value="old('amount')"
                    data-coupon-when="fixed"
                />

                <x-verum.form.field
                    name="min_spend"
                    :label="__('Minimum spend')"
                    prefix="$"
                    type="number"
                    step="0.01"
                    min="0"
                    :value="old('min_spend')"
                    :hint="__('Optional, but stops a big code landing on a small order.')"
                />

                <x-verum.form.field
                    name="max_redemptions"
                    :label="__('Total uses')"
                    type="number"
                    min="1"
                    :value="old('max_redemptions')"
                    :hint="__('Leave empty for unlimited.')"
                />

                <x-verum.form.field
                    name="per_buyer_limit"
                    :label="__('Uses per buyer')"
                    type="number"
                    min="1"
                    :value="old('per_buyer_limit', 1)"
                    required
                />

                <x-verum.form.field
                    name="starts_at"
                    :label="__('Starts')"
                    type="date"
                    :value="old('starts_at')"
                />

                <x-verum.form.field
                    name="ends_at"
                    :label="__('Ends')"
                    type="date"
                    :value="old('ends_at')"
                />
            </div>

            <div class="cform__actions">
                <button class="btn btn--ruby" type="submit">{{ __('Create code') }}</button>
            </div>
        </form>
    </x-verum.account.panel>

    <x-verum.account.panel :title="__('Your codes')" icon="list">
        @if ($coupons->isEmpty())
            <x-verum.account.empty
                icon="spark"
                :title="__('No codes yet')"
                :body="__('A code above gives buyers a reason to finish checking out.')"
            />
        @else
            <div class="tablewrap">
                <table class="atable">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('Code') }}</th>
                            <th scope="col">{{ __('Discount') }}</th>
                            <th scope="col">{{ __('Minimum') }}</th>
                            <th scope="col">{{ __('Used') }}</th>
                            <th scope="col">{{ __('Status') }}</th>
                            <th scope="col"><span class="sr-only">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($coupons as $coupon)
                            <tr>
                                <th scope="row" data-label="{{ __('Code') }}">
                                    <span class="atable__strong mono">{{ $coupon->code }}</span>
                                </th>
                                <td data-label="{{ __('Discount') }}">{{ $coupon->valueLabel() }}</td>
                                <td data-label="{{ __('Minimum') }}">
                                    {{ $coupon->min_spend_cents ? '$'.$money($coupon->min_spend_cents) : '—' }}
                                </td>
                                <td data-label="{{ __('Used') }}">
                                    {{ $coupon->used_count }}{{ $coupon->max_redemptions ? ' / '.$coupon->max_redemptions : '' }}
                                </td>
                                <td data-label="{{ __('Status') }}">
                                    <span @class(['chip', 'chip--live' => $coupon->isLive()])>
                                        {{ $coupon->isLive() ? __('Live') : __('Off') }}
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('seller.coupons.destroy', $coupon) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="ibtn" type="submit" aria-label="{{ __('Remove :code', ['code' => $coupon->code]) }}">
                                            <x-verum.icon name="trash" />
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-verum.account.panel>
</x-layouts::seller>
