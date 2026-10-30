@php
    $filters = ['all' => __('All'), ...\App\Models\SellerAccount::standingLabels()];
@endphp

<x-layouts::admin
    :title="__('Seller accounts')"
    :heading="__('Sellers')"
    :lede="__('Every seller account, where it stands, and how often it has been warned.')"
>
    <nav class="adminfilters" aria-label="{{ __('Filter by standing') }}">
        @foreach ($filters as $value => $label)
            <a
                href="{{ route('admin.sellers', array_filter(['standing' => $value, 'q' => $term])) }}"
                @class(['chip', 'is-on' => $standing === $value])
                @if ($standing === $value) aria-current="page" @endif
                wire:navigate
            >{{ $label }}</a>
        @endforeach
    </nav>

    <form method="GET" action="{{ route('admin.sellers') }}" class="adminsearch">
        <input type="hidden" name="standing" value="{{ $standing }}">
        <label class="sr-only" for="sellerSearch">{{ __('Search sellers') }}</label>
        <input id="sellerSearch" type="search" name="q" value="{{ $term }}" placeholder="{{ __('Search by name or email…') }}">
        <button class="btn btn--ghost btn--sm" type="submit">{{ __('Search') }}</button>
    </form>

    {{-- Re-read whenever an admin anywhere changes a seller. --}}
    <div data-live-region="sellers" data-staff-area="sellers">
        <div class="tablewrap">
            <table class="atable">
                <thead>
                    <tr>
                        <th scope="col">{{ __('Seller') }}</th>
                        <th scope="col">{{ __('Storefront') }}</th>
                        <th scope="col">{{ __('Standing') }}</th>
                        <th scope="col">{{ __('Warnings') }}</th>
                        <th scope="col">{{ __('Flags upheld') }}</th>
                        <th scope="col">{{ __('Joined') }}</th>
                        <th scope="col"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accounts as $account)
                        @php
                            $warned = $warnings[$account->id] ?? 0;
                            $upheld = $flags[$account->storefront?->id] ?? 0;
                        @endphp
                        <tr>
                            <th scope="row">
                                <span class="atable__strong">{{ $account->name }}</span>
                                <small>{{ $account->email }}</small>
                            </th>
                            <td data-label="{{ __('Storefront') }}">
                                @if ($account->storefront)
                                    <a class="link" href="{{ route('sellers.show', $account->storefront) }}" target="_blank" rel="noopener">
                                        {{ $account->storefront->name }}
                                    </a>
                                @elseif ($account->application)
                                    <x-verum.admin.status-pill :status="$account->application->status" />
                                @else
                                    <span class="mute">—</span>
                                @endif
                            </td>
                            <td data-label="{{ __('Standing') }}">
                                <x-verum.admin.standing-pill :$account />
                            </td>
                            <td data-label="{{ __('Warnings') }}">
                                @if ($warned > 0)
                                    <span class="pill pill--hold">{{ $warned }}</span>
                                @else
                                    <span class="mute">—</span>
                                @endif
                            </td>
                            <td data-label="{{ __('Flags upheld') }}">
                                @if ($upheld > 0)
                                    {{-- Upheld only: pending flags include the
                                         false positives this system tolerates. --}}
                                    <a class="pill pill--bad" href="{{ route('admin.moderation', ['state' => 'upheld']) }}" wire:navigate>{{ $upheld }}</a>
                                @else
                                    <span class="mute">—</span>
                                @endif
                            </td>
                            <td data-label="{{ __('Joined') }}">
                                <time datetime="{{ $account->created_at?->toIso8601String() }}">{{ $account->created_at?->format('j M Y') }}</time>
                            </td>
                            <td class="atable__actions">
                                <x-verum.admin.seller-actions :$account :warnings="$warned" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">{{ __('No seller accounts match.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pager">{{ $accounts->onEachSide(1)->links() }}</div>
    </div>
</x-layouts::admin>
