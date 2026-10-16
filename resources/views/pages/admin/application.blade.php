<x-layouts::admin
    :title="$application->reference"
    :heading="$application->business_name"
    :lede="$application->reference"
>
    <div class="apanels">
        <x-verum.account.panel :title="__('Application')" icon="file">
            <dl class="specs">
                <div><dt>{{ __('Status') }}</dt><dd><x-verum.admin.status-pill :status="$application->status" /></dd></div>
                <div><dt>{{ __('Country') }}</dt><dd>{{ $application->country }}</dd></div>
                <div><dt>{{ __('Trade license') }}</dt><dd>{{ $application->trade_license_number ?? __('Not given') }}</dd></div>
                <div><dt>{{ __('Specialties') }}</dt><dd>{{ $application->specialties ?? '—' }}</dd></div>
                <div><dt>{{ __('Submitted') }}</dt><dd>{{ $application->submitted_at?->format('d/m/Y H:i') }}</dd></div>
                @if ($application->reviewed_at)
                    <div><dt>{{ __('Reviewed') }}</dt><dd>{{ $application->reviewed_at->format('d/m/Y H:i') }} · {{ $application->reviewed_by }}</dd></div>
                @endif
            </dl>

            @if ($application->about)
                <p class="apanel__note">{{ $application->about }}</p>
            @endif

            @if ($application->decision_note)
                <div class="notice notice--bad">
                    <x-verum.icon name="info" />
                    <p>{{ $application->decision_note }}</p>
                </div>
            @endif
        </x-verum.account.panel>

        <x-verum.account.panel :title="__('Applicant')" icon="user">
            <dl class="specs">
                <div><dt>{{ __('Name') }}</dt><dd>{{ $application->sellerAccount?->name }}</dd></div>
                <div><dt>{{ __('Email') }}</dt><dd>{{ $application->sellerAccount?->email }}</dd></div>
                <div>
                    <dt>{{ __('Account approved') }}</dt>
                    <dd>{{ $application->sellerAccount?->isApproved() ? __('Yes') : __('Not yet') }}</dd>
                </div>
            </dl>
        </x-verum.account.panel>
    </div>

    @if ($application->isPending())
        <div class="apanels">
            <x-verum.account.panel :title="__('Approve')" icon="check" :hint="__('Creates the public storefront and unlocks the seller area.')">
                <form method="POST" action="{{ route('admin.applications.approve', $application) }}">
                    @csrf
                    <button class="btn btn--ruby btn--block" type="submit">
                        {{ __('Approve :reference', ['reference' => $application->reference]) }}
                    </button>
                </form>
            </x-verum.account.panel>

            <x-verum.account.panel :title="__('Reject')" icon="close" :hint="__('The applicant sees your reason on their status page.')">
                <form method="POST" action="{{ route('admin.applications.reject', $application) }}" class="cform" data-validate>
                    @csrf
                    <x-verum.form.textarea
                        name="reason"
                        :label="__('Reason')"
                        :rows="3"
                        required
                        data-minlength="10"
                        data-maxlength="1000"
                    />
                    <button class="btn btn--ghost btn--block" type="submit">{{ __('Reject application') }}</button>
                </form>
            </x-verum.account.panel>
        </div>
    @endif

    <p><a class="link" href="{{ route('admin.applications') }}" wire:navigate>{{ __('Back to applications') }}</a></p>
</x-layouts::admin>
