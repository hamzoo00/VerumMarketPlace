@php
    $filters = ['all' => __('All')] + \App\Models\Listing::statusLabels();
@endphp

<x-layouts::seller
    :title="__('Manage inventory')"
    :heading="__('Inventory')"
    :lede="__('Everything you have listed, live or draft.')"
    :account="$account"
>
    <div class="invbar">
        <nav class="adminfilters" aria-label="{{ __('Filter by status') }}">
            @foreach ($filters as $value => $label)
                <a
                    href="{{ route('seller.listings', ['status' => $value]) }}"
                    @class(['chip', 'is-on' => $status === $value])
                    @if ($status === $value) aria-current="page" @endif
                    wire:navigate
                >{{ $label }}</a>
            @endforeach
        </nav>

        <a class="btn btn--ruby btn--sm" href="{{ route('seller.listings.create') }}" wire:navigate>
            <x-verum.icon name="plus" />{{ __('Sell an item') }}
        </a>
    </div>

    {{--
        Wrapped so live-seller.js can swap the whole region when this
        seller's inventory changes elsewhere — another tab, or a delete.
    --}}
    <div data-inventory @if ($storefront) data-seller-live="{{ $storefront->id }}" @endif>

    @if ($listings->isEmpty())
        <x-verum.account.panel :title="__('Nothing here yet')" icon="gem">
            <x-verum.account.empty
                icon="gem"
                :title="__('No listings')"
                :body="__('List your first stone and it will appear here.')"
            />
            <a class="btn btn--ruby btn--sm" href="{{ route('seller.listings.create') }}" wire:navigate>
                {{ __('Sell an item') }}
            </a>
        </x-verum.account.panel>
    @else
        <div class="tablewrap">
            <table class="atable">
                <thead>
                    <tr>
                        <th scope="col">{{ __('Item') }}</th>
                        <th scope="col">{{ __('Category') }}</th>
                        <th scope="col">{{ __('Type') }}</th>
                        <th scope="col">{{ __('Price') }}</th>
                        <th scope="col">{{ __('Status') }}</th>
                        <th scope="col"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($listings as $listing)
                        <tr>
                            <th scope="row" data-label="{{ __('Item') }}">
                                <a class="link" href="{{ route('seller.listings.edit', $listing) }}" wire:navigate>
                                    <span class="atable__strong">{{ $listing->title }}</span>
                                </a>
                                @if ($listing->sku)
                                    <small>{{ $listing->sku }}</small>
                                @endif
                            </th>
                            <td data-label="{{ __('Category') }}">{{ $listing->category?->name }}</td>
                            <td data-label="{{ __('Type') }}">
                                {{ \App\Models\Listing::typeLabels()[$listing->listing_type] ?? $listing->listing_type }}
                            </td>
                            <td data-label="{{ __('Price') }}">{{ $listing->price()?->format() ?? '—' }}</td>
                            <td data-label="{{ __('Status') }}">
                                <x-verum.seller.listing-status :status="$listing->status" />

                                {{-- A seller must know why their stone is
                                     not public yet, and what to fix. --}}
                                @if ($listing->isAwaitingApproval())
                                    <span class="chip chip--warn">{{ __('Awaiting approval') }}</span>
                                @elseif ($listing->wasRejected())
                                    <span class="chip chip--bad">{{ __('Rejected') }}</span>
                                    @if ($listing->approval_note)
                                        <small class="mute">{{ $listing->approval_note }}</small>
                                    @endif
                                @endif

                                @if ($listing->isFeatured())
                                    <span class="chip chip--gold">{{ __('Featured') }}</span>
                                @endif
                            </td>
                            <td class="atable__actions">
                                <x-verum.seller.listing-actions :$listing />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pager">{{ $listings->onEachSide(1)->links() }}</div>
    @endif

    </div>
</x-layouts::seller>
