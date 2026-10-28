@php
    use App\Models\Listing;

    $tabs = [
        Listing::APPROVAL_PENDING => __('Waiting'),
        Listing::APPROVAL_APPROVED => __('Approved'),
        Listing::APPROVAL_REJECTED => __('Rejected'),
    ];
@endphp

<x-layouts::admin
    :title="__('Listing approvals')"
    :heading="__('Approvals')"
    :lede="__('Stones waiting to go public.')"
>
    @unless ($approvalRequired)
        <div class="notice" role="status">
            <x-verum.icon name="info" />
            <div>
                <b>{{ __('Approval is switched off.') }}</b>
                <p>{{ __('New listings go live on their own. Anything already queued still waits for you.') }}</p>
                <a class="link" href="{{ route('admin.settings') }}" wire:navigate>{{ __('Change this in settings') }}</a>
            </div>
        </div>
    @endunless

    <nav class="adminfilters" aria-label="{{ __('Filter by state') }}">
        @foreach ($tabs as $value => $label)
            <a
                href="{{ route('admin.approvals', ['state' => $value]) }}"
                @class(['chip', 'is-on' => $state === $value])
                @if ($state === $value) aria-current="page" @endif
                wire:navigate
            >{{ $label }}@if ($value === Listing::APPROVAL_PENDING && $pendingCount > 0) <b>{{ $pendingCount }}</b>@endif</a>
        @endforeach
    </nav>

    @if ($listings->isEmpty())
        <x-verum.account.panel :title="__('Nothing here')" icon="check">
            <x-verum.account.empty
                icon="check"
                :title="__('Queue is clear')"
                :body="__('No listings match that filter.')"
            />
        </x-verum.account.panel>
    @else
        <div class="revlist">
            @foreach ($listings as $listing)
                <x-verum.premium.review-row :$listing mode="approval" />
            @endforeach
        </div>

        <div class="pager">{{ $listings->onEachSide(1)->links() }}</div>
    @endif
</x-layouts::admin>
