<x-layouts::seller
    :account="$account"
    :title="__('Offers')"
    :heading="__('Offers')"
    :lede="__('Buyers negotiating on your fixed-price items.')"
>
    {{-- Re-read over the socket when either side moves; see live-offers.js. --}}
    <div data-offers-live="seller.{{ $storefront->id }}.offers">
        @if ($offers->isEmpty())
            <x-verum.account.panel :title="__('No offers yet')" icon="scale">
                <x-verum.account.empty
                    icon="scale"
                    :title="__('Nothing to answer')"
                    :body="__('Turn on “Accept offers” when you list an item and buyers can negotiate.')"
                />
            </x-verum.account.panel>
        @else
            <div class="offerlist">
                @foreach ($offers as $offer)
                    <x-verum.offer.row :$offer side="seller">
                        <x-slot:actions>
                            <form method="POST" action="{{ route('seller.offers.respond', $offer) }}" class="offeract">
                                @csrf
                                <input type="hidden" name="decision" value="accept">
                                <button class="btn btn--ruby btn--sm" type="submit">{{ __('Accept') }}</button>
                            </form>

                            <form method="POST" action="{{ route('seller.offers.respond', $offer) }}" class="offeract">
                                @csrf
                                <input type="hidden" name="decision" value="decline">
                                <button class="btn btn--ghost btn--sm" type="submit">{{ __('Decline') }}</button>
                            </form>

                            <form method="POST" action="{{ route('seller.offers.respond', $offer) }}" class="offeract offeract--counter">
                                @csrf
                                <input type="hidden" name="decision" value="counter">
                                <label class="sr-only" for="c-{{ $offer->id }}">{{ __('Counter amount') }}</label>
                                <input
                                    id="c-{{ $offer->id }}"
                                    class="offeract__input"
                                    type="number"
                                    name="amount"
                                    step="0.01"
                                    min="0.01"
                                    placeholder="{{ __('Counter') }}"
                                    required
                                >
                                <button class="btn btn--ghost btn--sm" type="submit">{{ __('Send') }}</button>
                            </form>
                        </x-slot:actions>
                    </x-verum.offer.row>
                @endforeach
            </div>

            <div class="pager">{{ $offers->onEachSide(1)->links() }}</div>
        @endif
    </div>
</x-layouts::seller>
