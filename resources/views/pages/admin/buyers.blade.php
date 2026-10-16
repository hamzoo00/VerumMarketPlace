<x-layouts::admin
    :title="__('Buyer accounts')"
    :heading="__('Buyers')"
    :lede="__('Search by name or email.')"
>
    <form method="GET" action="{{ route('admin.buyers') }}" class="cform adminsearch">
        <x-verum.form.field name="q" :label="__('Search buyers')" :value="$term" full />
        <button class="btn btn--ruby btn--sm" type="submit">{{ __('Search') }}</button>
    </form>

    <div class="tablewrap">
        <table class="otable">
            <thead>
                <tr>
                    <th scope="col">{{ __('Name') }}</th>
                    <th scope="col">{{ __('Email') }}</th>
                    <th scope="col">{{ __('Country') }}</th>
                    <th scope="col">{{ __('Verified') }}</th>
                    <th scope="col">{{ __('Joined') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($buyers as $buyer)
                    <tr>
                        <th scope="row">{{ $buyer->name }}</th>
                        <td>{{ $buyer->email }}</td>
                        <td>{{ $buyer->country ?? '—' }}</td>
                        <td>{{ $buyer->hasVerifiedEmail() ? __('Yes') : __('No') }}</td>
                        <td>{{ $buyer->created_at?->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">{{ __('No buyers found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pager">{{ $buyers->onEachSide(1)->links() }}</div>
</x-layouts::admin>
