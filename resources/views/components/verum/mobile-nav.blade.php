@props(['navigation'])

@php
    use App\Support\DisplayCurrency;

    $countries = DisplayCurrency::options();
    $chosenCountry = DisplayCurrency::country();
@endphp

<div class="mnav" id="mnav" aria-hidden="true">
    <div class="mnav__bg" aria-hidden="true"></div>
    <div class="mnav__inner wrap">
        <nav class="mnav__links" aria-label="{{ __('Mobile') }}">
            @foreach ($navigation as $index => $item)
                <a href="{{ $item['url'] }}" data-nav="{{ $item['key'] }}" wire:navigate>
                    <span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>{{ $item['label'] }}
                </a>
            @endforeach
        </nav>
        {{-- The header's picker is an icon button, hidden below 640px, so
             the choice lives here too. A native select is the right
             control on a phone and needs no JS at all. --}}
        <form class="mnav__cur" method="POST" action="{{ route('currency.switch') }}">
            @csrf
            <label class="lbl" for="mnavCountry">{{ __('Show prices in') }}</label>

            <div class="mnav__currow">
                <select class="mnav__select" id="mnavCountry" name="country">
                    @foreach ($countries as $iso => $option)
                        <option value="{{ $iso }}" @selected($chosenCountry === $iso)>
                            {{ $option['name'] }} · {{ $option['currency'] }}
                        </option>
                    @endforeach
                </select>

                <button class="btn btn--ghost btn--sm" type="submit">{{ __('Apply') }}</button>
            </div>
        </form>

        <div class="mnav__foot">
            <a href="{{ route('verify') }}" wire:navigate>
                <x-verum.icon name="shield" />{{ __('Verify a report') }}
            </a>
            <a href="{{ route('sell') }}" wire:navigate>
                <x-verum.icon name="gem" />{{ __('Sell on :app', ['app' => config('app.name')]) }}
            </a>
            <a href="mailto:{{ config('verum.contact.email') }}">
                <x-verum.icon name="mail" />{{ config('verum.contact.email') }}
            </a>
        </div>
    </div>
</div>
