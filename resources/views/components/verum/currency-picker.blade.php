@php
    use App\Support\DisplayCurrency;

    $options = DisplayCurrency::options();
    $chosen = DisplayCurrency::country();
    $currency = DisplayCurrency::currency();
    $asOf = DisplayCurrency::ratesDate();
@endphp

{{--
    Picks the currency prices are *also* quoted in. The site charges in
    USD; this only adds a bracketed figure, which is why the panel says so
    rather than leaving a visitor to assume otherwise.

    Rendered server-side in full: the list is crawlable and the form works
    without JS, where the filter box is simply never shown.
--}}
<div class="cursel" data-cursel>
    <button
        class="ibtn ibtn--hide-sm cursel__btn"
        type="button"
        data-cursel-btn
        aria-expanded="false"
        aria-controls="curselPop"
        aria-label="{{ __('Choose your country') }}"
        data-tip="{{ $chosen ? __('Prices in :cur', ['cur' => $currency ?? config('verum_currency.base')]) : __('Choose your country') }}"
    >
        <x-verum.icon name="globe" />
        @if ($currency)
            <span class="cursel__code">{{ $currency }}</span>
        @endif
    </button>

    <div class="cursel__pop" id="curselPop" hidden>
        <p class="cursel__hi">{{ __('Show prices in') }}</p>
        <p class="cursel__note">
            {{ __('Every sale is charged in :base. Your currency is shown alongside as a guide.', [
                'base' => config('verum_currency.base'),
            ]) }}
        </p>

        <label class="sr-only" for="curselFilter">{{ __('Search countries') }}</label>
        <input
            class="cursel__filter"
            id="curselFilter"
            type="search"
            placeholder="{{ __('Search countries…') }}"
            autocomplete="off"
            data-cursel-filter
            hidden
        >

        <form method="POST" action="{{ route('currency.switch') }}" data-cursel-form>
            @csrf

            <ul class="cursel__list" data-cursel-list>
                @foreach ($options as $iso => $option)
                    <li data-cursel-row data-label="{{ Str::lower($option['name'].' '.$option['currency'].' '.$iso) }}">
                        <button
                            type="submit"
                            name="country"
                            value="{{ $iso }}"
                            @class(['cursel__row', 'is-on' => $chosen === $iso])
                            @if ($chosen === $iso) aria-current="true" @endif
                        >
                            <span class="cursel__name">{{ $option['name'] }}</span>
                            <span class="cursel__cur">{{ $option['currency'] }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>

            <p class="cursel__empty" data-cursel-empty hidden>{{ __('No country matches that.') }}</p>
        </form>

        @if ($asOf && $currency)
            {{-- The date the rate was published, never hidden: a
                 conversion without one can pass as today's. --}}
            <p class="cursel__rate">
                {{ __('Rates as of :date', ['date' => $asOf->format('j M Y')]) }}
            </p>
        @endif
    </div>
</div>
