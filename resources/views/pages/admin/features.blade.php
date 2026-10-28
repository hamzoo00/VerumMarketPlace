@php
    use App\Models\Listing;

    $tabs = [
        Listing::FEATURE_REQUESTED => __('Requested'),
        Listing::FEATURE_ACTIVE => __('Featured'),
        Listing::FEATURE_DECLINED => __('Declined'),
    ];
@endphp

<x-layouts::admin
    :title="__('Featured listings')"
    :heading="__('Featured')"
    :lede="__('Premium sellers ask; you decide what the storefront shows.')"
>
    @unless ($featuringEnabled)
        <div class="notice" role="status">
            <x-verum.icon name="info" />
            <div>
                <b>{{ __('Feature requests are closed.') }}</b>
                <p>{{ __('Sellers cannot ask for a slot while this is off.') }}</p>
                <a class="link" href="{{ route('admin.settings') }}" wire:navigate>{{ __('Change this in settings') }}</a>
            </div>
        </div>
    @endunless

    <p class="mute">
        {{ __(':used of :slots slots in use.', ['used' => $activeCount, 'slots' => $slots]) }}
    </p>

    <nav class="adminfilters" aria-label="{{ __('Filter by state') }}">
        @foreach ($tabs as $value => $label)
            <a
                href="{{ route('admin.features', ['state' => $value]) }}"
                @class(['chip', 'is-on' => $state === $value])
                @if ($state === $value) aria-current="page" @endif
                wire:navigate
            >{{ $label }}@if ($value === Listing::FEATURE_REQUESTED && $requestedCount > 0) <b>{{ $requestedCount }}</b>@endif</a>
        @endforeach
    </nav>

    @if ($listings->isEmpty())
        <x-verum.account.panel :title="__('Nothing here')" icon="spark">
            <x-verum.account.empty
                icon="spark"
                :title="__('No requests')"
                :body="__('Premium sellers can ask for a slot from their inventory.')"
            />
        </x-verum.account.panel>
    @else
        <div class="revlist">
            @foreach ($listings as $listing)
                <x-verum.premium.review-row :$listing mode="feature" />
            @endforeach
        </div>

        <div class="pager">{{ $listings->onEachSide(1)->links() }}</div>
    @endif
</x-layouts::admin>
