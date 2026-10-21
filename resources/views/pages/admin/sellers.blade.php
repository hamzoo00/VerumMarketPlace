<x-layouts::admin
    :title="__('Seller accounts')"
    :heading="__('Sellers')"
    :lede="__('Every seller account and where it stands.')"
>
    <div class="tablewrap">
        <table class="otable">
            <thead>
                <tr>
                    <th scope="col">{{ __('Name') }}</th>
                    <th scope="col">{{ __('Email') }}</th>
                    <th scope="col">{{ __('Application') }}</th>
                    <th scope="col">{{ __('Storefront') }}</th>
                    <th scope="col">{{ __('Flags') }}</th>
                    <th scope="col">{{ __('Joined') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($accounts as $account)
                    <tr>
                        <th scope="row">{{ $account->name }}</th>
                        <td>{{ $account->email }}</td>
                        <td>
                            @if ($account->application)
                                <x-verum.admin.status-pill :status="$account->application->status" />
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if ($account->storefront)
                                <a class="link" href="{{ route('sellers.show', $account->storefront) }}">{{ $account->storefront->slug }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @php($upheld = $flags[$account->storefront?->id] ?? 0)

                            @if ($upheld > 0)
                                {{-- Upheld only: pending flags include the
                                     false positives this system tolerates. --}}
                                <a
                                    class="chip chip--warn"
                                    href="{{ route('admin.moderation', ['state' => 'upheld']) }}"
                                    wire:navigate
                                >{{ $upheld }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $account->created_at?->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">{{ __('No seller accounts yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pager">{{ $accounts->onEachSide(1)->links() }}</div>
</x-layouts::admin>
