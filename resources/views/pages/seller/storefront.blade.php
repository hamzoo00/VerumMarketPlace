<x-layouts::seller
    :account="$account"
    :title="__('Storefront')"
    :heading="__('Your storefront')"
    :lede="__('How buyers see your house.')"
>
    <x-verum.account.panel :title="__('Public page')" icon="shield">
        @if ($storefront)
            <dl class="specs">
                <div><dt>{{ __('Name') }}</dt><dd>{{ $storefront->name }}</dd></div>
                <div><dt>{{ __('Location') }}</dt><dd>{{ $storefront->location }}</dd></div>
                <div><dt>{{ __('Listings') }}</dt><dd>{{ $storefront->stones()->count() }}</dd></div>
                <div>
                    <dt>{{ __('Rating') }}</dt>
                    <dd>{{ number_format($storefront->rating, 1) }} ★</dd>
                </div>
            </dl>
            <a class="btn btn--ghost btn--sm" href="{{ route('sellers.show', $storefront) }}" wire:navigate>
                {{ __('View public page') }}
            </a>
        @else
            <x-verum.account.empty
                icon="shield"
                :title="__('Storefront not set up')"
                :body="__('Your public page is created once your first listing goes live.')"
            />
        @endif
    </x-verum.account.panel>
</x-layouts::seller>
