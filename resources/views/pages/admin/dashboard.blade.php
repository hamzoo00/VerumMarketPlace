<x-layouts::admin
    :title="__('Admin overview')"
    :heading="__('Overview')"
    :lede="__('Marketplace at a glance.')"
>
    <ul class="astats">
        <x-verum.account.stat :label="__('Pending applications')" :value="$stats['applications_pending']" />
        <x-verum.account.stat :label="__('Approved sellers')" :value="$stats['sellers_approved']" />
        <x-verum.account.stat :label="__('Buyers')" :value="$stats['buyers']" />
        <x-verum.account.stat :label="__('Listings')" :value="$stats['stones']" />
    </ul>

    <div class="apanels">
        <x-verum.account.panel
            :title="__('Waiting for review')"
            icon="file"
            :hint="__('Oldest first')"
        >
            @if ($pending->isEmpty())
                <x-verum.account.empty
                    icon="check"
                    :title="__('Nothing waiting')"
                    :body="__('Every application has been decided.')"
                />
            @else
                <ul class="adminlist">
                    @foreach ($pending as $application)
                        <li>
                            <a href="{{ route('admin.applications.show', $application) }}" wire:navigate>
                                <b>{{ $application->business_name }}</b>
                                <small>{{ $application->reference }} · {{ $application->country }}</small>
                            </a>
                            <span class="pill pill--wait">{{ $application->submitted_at?->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
                <a class="btn btn--ghost btn--sm" href="{{ route('admin.applications') }}" wire:navigate>
                    {{ __('Review all') }}
                </a>
            @endif
        </x-verum.account.panel>

        <x-verum.account.panel :title="__('Catalogue')" icon="gem">
            <dl class="specs">
                <div><dt>{{ __('Fixed price') }}</dt><dd>{{ $stats['stones_for_sale'] }}</dd></div>
                <div><dt>{{ __('In auction') }}</dt><dd>{{ $stats['stones_in_auction'] }}</dd></div>
                <div><dt>{{ __('Storefronts') }}</dt><dd>{{ $stats['storefronts'] }}</dd></div>
                <div><dt>{{ __('Seller accounts') }}</dt><dd>{{ $stats['seller_accounts'] }}</dd></div>
            </dl>
        </x-verum.account.panel>
    </div>
</x-layouts::admin>
