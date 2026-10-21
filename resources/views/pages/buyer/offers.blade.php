<x-layouts::account-area
    :title="__('Offers')"
    :heading="__('Your offers')"
    :lede="__('Prices you have proposed, and what sellers came back with.')"
    :nav="$buyerNav"
    :account-name="$buyer->name"
    :account-initials="$buyer->initials()"
    :account-meta="__('Buyer account')"
    logout-route="buyer.logout"
>
    <div data-offers-live="buyer.{{ $buyer->id }}.offers">
        @if ($offers->isEmpty())
            <x-verum.account.panel :title="__('No offers yet')" icon="scale">
                <x-verum.account.empty
                    icon="scale"
                    :title="__('You have not made an offer')"
                    :body="__('On an item that accepts them, name your price and the seller will reply.')"
                />
            </x-verum.account.panel>
        @else
            <div class="offerlist">
                @foreach ($offers as $offer)
                    <x-verum.offer.row :$offer side="buyer">
                        <x-slot:actions>
                            <form method="POST" action="{{ route('buyer.offers.respond', $offer) }}" class="offeract">
                                @csrf
                                <input type="hidden" name="decision" value="accept">
                                <button class="btn btn--ruby btn--sm" type="submit">{{ __('Accept') }}</button>
                            </form>

                            <form method="POST" action="{{ route('buyer.offers.respond', $offer) }}" class="offeract">
                                @csrf
                                <input type="hidden" name="decision" value="decline">
                                <button class="btn btn--ghost btn--sm" type="submit">{{ __('Decline') }}</button>
                            </form>
                        </x-slot:actions>
                    </x-verum.offer.row>
                @endforeach
            </div>

            <div class="pager">{{ $offers->onEachSide(1)->links() }}</div>
        @endif
    </div>
</x-layouts::account-area>
