<x-layouts::account-area
    :title="__('My bids')"
    :heading="__('Your bids')"
    :lede="__('Every lot you have bid on, and where it stands.')"
    :nav="$buyerNav"
    :account-name="$buyer->name"
    :account-initials="$buyer->initials()"
    :account-meta="__('Buyer account')"
    logout-route="buyer.logout"
>
    <ul class="astats">
        <x-verum.account.stat :label="__('Live bids')" :value="$stats->activeBids" />
        <x-verum.account.stat :label="__('Leading')" :value="$stats->leadingBids" />
    </ul>

    @if ($bids->isEmpty())
        <x-verum.account.panel :title="__('No bids yet')" icon="gavel">
            <x-verum.account.empty
                icon="gavel"
                :title="__('Nothing bid on')"
                :body="__('Live lots close on a timer — a bid in the last two minutes extends it.')"
            />
            <a class="btn btn--ruby btn--sm" href="{{ route('auctions') }}" wire:navigate>
                {{ __('See live auctions') }}
            </a>
        </x-verum.account.panel>
    @else
        <div class="tablewrap">
            <table class="atable">
                <thead>
                    <tr>
                        <th scope="col">{{ __('Lot') }}</th>
                        <th scope="col">{{ __('Your bid') }}</th>
                        <th scope="col">{{ __('Standing') }}</th>
                        <th scope="col">{{ __('Placed') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bids as $bid)
                        <tr>
                            <th scope="row" data-label="{{ __('Lot') }}">
                                @if ($bid->listing)
                                    <a class="link" href="{{ route('listings.show', $bid->listing) }}" wire:navigate>
                                        <span class="atable__strong">{{ $bid->listing->title }}</span>
                                    </a>
                                    <small>{{ $bid->listing->seller?->name }}</small>
                                @else
                                    <span class="mute">{{ __('Lot removed') }}</span>
                                @endif
                            </th>
                            <td data-label="{{ __('Your bid') }}" class="mono">{{ $bid->amount()->withLocal() }}</td>
                            <td data-label="{{ __('Standing') }}">
                                <span @class(['chip', 'chip--live' => $bid->outbid_at === null])>
                                    {{ $bid->outbid_at === null ? __('Leading') : __('Outbid') }}
                                </span>
                            </td>
                            <td data-label="{{ __('Placed') }}">{{ $bid->created_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pager">{{ $bids->onEachSide(1)->links() }}</div>
    @endif
</x-layouts::account-area>
