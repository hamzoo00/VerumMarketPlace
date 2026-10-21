<x-layouts::seller
    :account="$account"
    :title="__('Storefront')"
    :heading="__('Your storefront')"
    :lede="__('How buyers see your house.')"
>
    @if ($storefront)
        <x-verum.account.panel :title="__('Public page')" icon="shield">
            <dl class="specs">
                <div><dt>{{ __('Name') }}</dt><dd>{{ $storefront->name }}</dd></div>
                <div><dt>{{ __('Location') }}</dt><dd>{{ $storefront->location }}</dd></div>
                {{-- Counted from `listings`; stones() is the seeded demo table. --}}
                <div><dt>{{ __('Live listings') }}</dt><dd>{{ $stats->live }}</dd></div>
                <div><dt>{{ __('Drafts') }}</dt><dd>{{ $stats->drafts }}</dd></div>
                <div>
                    <dt>{{ __('Rating') }}</dt>
                    <dd>{{ number_format($storefront->rating, 1) }} ★</dd>
                </div>
            </dl>

            <a class="btn btn--ghost btn--sm" href="{{ route('sellers.show', $storefront) }}" wire:navigate>
                {{ __('View public page') }}
            </a>
        </x-verum.account.panel>

        <x-verum.account.panel :title="__('Branding')" icon="spark"
            :hint="__('These appear on your public store page.')">
            <x-verum.form.image-upload
                kind="avatar"
                :label="__('Store avatar')"
                :hint="__('Square works best.')"
                :current="$storefront->avatarUrl()"
            />

            <x-verum.form.image-upload
                kind="banner"
                :label="__('Store banner')"
                :hint="__('Wide works best.')"
                :current="$storefront->bannerUrl()"
            />
        </x-verum.account.panel>
    @else
        <x-verum.account.panel :title="__('Public page')" icon="shield">
            <x-verum.account.empty
                icon="shield"
                :title="__('Storefront not set up')"
                :body="__('Your public page is created once your first listing goes live.')"
            />
        </x-verum.account.panel>
    @endif

    {{-- One editor for both uploads on this page. --}}
    <x-verum.form.cropper />
</x-layouts::seller>
