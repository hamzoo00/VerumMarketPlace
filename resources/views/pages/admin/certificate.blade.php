<x-layouts::admin
    :title="__('Certificate')"
    :heading="$seller->name"
    :lede="__('The certificate as a buyer sees it on every listing this seller publishes.')"
>
    <a class="link" href="{{ route('admin.premium') }}" wire:navigate>{{ __('← All premium sellers') }}</a>

    <div class="certpreview">
        {{-- The seller-level frame, with no stone: on a listing the rows
             fill with that gem's own disclosed details. --}}
        <x-verum.premium.certificate :listing="null" :preview="$seller" />

        <x-verum.account.panel :title="__('Issued')" icon="badge">
            <dl class="osum">
                <div class="osum__row">
                    <dt>{{ __('Reference') }}</dt>
                    <dd>{{ $seller->certificate_reference }}</dd>
                </div>
                <div class="osum__row">
                    <dt>{{ __('Issued') }}</dt>
                    <dd>{{ $seller->certified_at?->format('j M Y') }}</dd>
                </div>
                <div class="osum__row">
                    <dt>{{ __('By') }}</dt>
                    <dd>{{ $seller->certifier?->username ?? __('—') }}</dd>
                </div>
            </dl>

            @if ($seller->certificate_note)
                <p class="mute">{{ $seller->certificate_note }}</p>
            @endif
        </x-verum.account.panel>
    </div>
</x-layouts::admin>
