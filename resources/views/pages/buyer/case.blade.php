<x-layouts::account-area
    :title="$case->reference"
    :heading="$case->reference"
    :lede="$case->kindLabel().' · '.$case->reasonLabel()"
    :nav="$buyerNav"
    :account-name="$buyer->name"
    :account-initials="$buyer->initials()"
    :account-meta="__('Buyer account')"
    logout-route="buyer.logout"
>
    <x-verum.account.panel :title="__('What you told us')" icon="chat">
        <p class="casebody">{{ $case->body }}</p>

        <dl class="specs">
            <div><dt>{{ __('Status') }}</dt><dd>{{ $case->statusLabel() }}</dd></div>
            <div><dt>{{ __('Raised') }}</dt><dd>{{ $case->created_at->format('j M Y') }}</dd></div>
            @if ($case->listing)
                <div>
                    <dt>{{ __('Listing') }}</dt>
                    <dd><a class="link" href="{{ route('listings.show', $case->listing) }}" wire:navigate>{{ $case->listing->title }}</a></dd>
                </div>
            @endif
        </dl>
    </x-verum.account.panel>

    @if ($case->resolution)
        <x-verum.account.panel :title="__('Our decision')" icon="shield">
            <p class="casebody"><strong>{{ $case->resolutionLabel() }}</strong></p>

            @if ($case->resolution_note)
                <p class="casebody">{{ $case->resolution_note }}</p>
            @endif

            <p class="mute">{{ __('Settled :when', ['when' => $case->resolved_at?->diffForHumans()]) }}</p>
        </x-verum.account.panel>
    @else
        <x-verum.account.panel :title="__('With our team')" icon="clock">
            <p class="mute">{{ __('We will email you as soon as this is settled.') }}</p>
        </x-verum.account.panel>
    @endif
</x-layouts::account-area>
