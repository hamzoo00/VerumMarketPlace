<x-layouts::admin
    :title="__('Buyer accounts')"
    :heading="__('Buyers')"
    :lede="__('Search by name or email.')"
>
    <form method="GET" action="{{ route('admin.buyers') }}" class="adminsearch">
        <label class="sr-only" for="buyerSearch">{{ __('Search buyers') }}</label>
        <input id="buyerSearch" type="search" name="q" value="{{ $term }}" placeholder="{{ __('Search by name or email…') }}">
        <button class="btn btn--ghost btn--sm" type="submit">{{ __('Search') }}</button>
    </form>

    {{-- Warnings move when a flag a buyer sent is upheld. --}}
    <div data-live-region="buyers" data-staff-area="buyers">
        <div class="tablewrap">
            <table class="atable">
                <thead>
                    <tr>
                        <th scope="col">{{ __('Buyer') }}</th>
                        <th scope="col">{{ __('Country') }}</th>
                        <th scope="col">{{ __('Verified') }}</th>
                        <th scope="col">{{ __('Warnings') }}</th>
                        <th scope="col">{{ __('Joined') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($buyers as $buyer)
                        <tr>
                            <th scope="row">
                                <span class="atable__strong">{{ $buyer->name }}</span>
                                <small>{{ $buyer->email }}</small>
                            </th>
                            <td data-label="{{ __('Country') }}">{{ $buyer->country ?? '—' }}</td>
                            <td data-label="{{ __('Verified') }}">
                                <span @class(['pill', 'pill--ok' => $buyer->hasVerifiedEmail(), 'pill--muted' => ! $buyer->hasVerifiedEmail()])>
                                    {{ $buyer->hasVerifiedEmail() ? __('Yes') : __('No') }}
                                </span>
                            </td>
                            <td data-label="{{ __('Warnings') }}">
                                @if (($warnings[$buyer->id] ?? 0) > 0)
                                    <span class="pill pill--hold">{{ $warnings[$buyer->id] }}</span>
                                @else
                                    <span class="mute">—</span>
                                @endif
                            </td>
                            <td data-label="{{ __('Joined') }}">
                                <time datetime="{{ $buyer->created_at?->toIso8601String() }}">{{ $buyer->created_at?->format('j M Y') }}</time>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">{{ __('No buyers found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pager">{{ $buyers->onEachSide(1)->links() }}</div>
    </div>
</x-layouts::admin>
