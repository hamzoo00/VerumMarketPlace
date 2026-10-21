<x-layouts::account-area
    :title="__('Buyer dashboard')"
    :heading="__('Welcome back, :name', ['name' => $buyer->name])"
    :lede="__('Your bids, offers and anything waiting on you.')"
    :nav="$buyerNav"
    :account-name="$buyer->name"
    :account-initials="$buyer->initials()"
    :account-meta="__('Buyer account')"
    logout-route="buyer.logout"
>
    <ul class="astats">
        <x-verum.account.stat :label="__('Live bids')" :value="$stats->activeBids" />
        <x-verum.account.stat :label="__('Leading')" :value="$stats->leadingBids" />
        <x-verum.account.stat :label="__('Open offers')" :value="$stats->openOffers" />
        <x-verum.account.stat :label="__('Open cases')" :value="$stats->openCases" />
    </ul>

    <div class="apanels">
        @if ($toReview->isNotEmpty())
            <x-verum.account.panel :title="__('Leave a review')" icon="star"
                :hint="__('You bought these — tell other buyers how it went.')">
                <ul class="minilist">
                    @foreach ($toReview as $listing)
                        <li>
                            <a href="{{ route('listings.show', $listing) }}" wire:navigate>{{ $listing->title }}</a>
                            <span>{{ $listing->seller?->name }}</span>
                        </li>
                    @endforeach
                </ul>
            </x-verum.account.panel>
        @endif

        <x-verum.account.panel :title="__('Your bids')" icon="gavel" :hint="__('Lots you are in on')">
            @if ($recentBids->isEmpty())
                <x-verum.account.empty
                    icon="gavel"
                    :title="__('No bids yet')"
                    :body="__('Find a lot in the live auctions and name your price.')"
                />
                <a class="btn btn--ruby btn--sm" href="{{ route('auctions') }}" wire:navigate>
                    {{ __('See live auctions') }}
                </a>
            @else
                <ul class="minilist">
                    @foreach ($recentBids as $bid)
                        <li>
                            <a href="{{ route('listings.show', $bid->listing) }}" wire:navigate>{{ $bid->listing?->title }}</a>
                            <span>{{ $bid->amount()->format() }}</span>
                            <span @class(['chip', 'chip--live' => $bid->outbid_at === null])>
                                {{ $bid->outbid_at === null ? __('Leading') : __('Outbid') }}
                            </span>
                        </li>
                    @endforeach
                </ul>

                <a class="btn btn--ghost btn--sm" href="{{ route('buyer.bids') }}" wire:navigate>
                    {{ __('All bids') }}
                </a>
            @endif
        </x-verum.account.panel>

        <x-verum.account.panel :title="__('Your account')" icon="user">
            <p class="mute">{{ $buyer->email }}</p>
            <a class="btn btn--ghost btn--sm" href="{{ route('buyer.settings') }}" wire:navigate>
                {{ __('Settings') }}
            </a>
        </x-verum.account.panel>
    </div>
</x-layouts::account-area>
