<x-layouts::admin
    :title="$case->reference"
    :heading="$case->reference"
    :lede="$case->kindLabel().' · '.$case->reasonLabel()"
>
    <x-verum.account.panel :title="__('What was raised')" icon="chat">
        <p class="casebody">{{ $case->body }}</p>

        <dl class="specs">
            <div><dt>{{ __('Raised by') }}</dt><dd>{{ $case->buyer?->name }} · {{ $case->buyer?->email }}</dd></div>
            <div><dt>{{ __('When') }}</dt><dd>{{ $case->created_at->format('j M Y, H:i') }}</dd></div>

            @if ($case->listing)
                <div>
                    <dt>{{ __('Listing') }}</dt>
                    <dd><a class="link" href="{{ route('listings.show', $case->listing) }}">{{ $case->listing->title }}</a></dd>
                </div>
            @endif

            @if ($case->subjectSeller())
                <div>
                    <dt>{{ __('Seller') }}</dt>
                    <dd>
                        <a class="link" href="{{ route('sellers.show', $case->subjectSeller()) }}">
                            {{ $case->subjectSeller()->name }}
                        </a>
                    </dd>
                </div>
            @endif

            <div><dt>{{ __('Status') }}</dt><dd>{{ $case->statusLabel() }}</dd></div>
        </dl>
    </x-verum.account.panel>

    @if ($case->isPending())
        <x-verum.account.panel :title="__('Settle it')" icon="shield"
            :hint="__('Both the buyer and the seller are told, in the app and by email.')">

            @if ($case->status === \App\Models\SupportCase::STATUS_OPEN)
                <form method="POST" action="{{ route('admin.cases.review', $case) }}" class="casereview">
                    @csrf
                    <button class="btn btn--ghost btn--sm" type="submit">{{ __('Mark as being reviewed') }}</button>
                </form>
            @endif

            <form method="POST" action="{{ route('admin.cases.resolve', $case) }}" class="cform">
                @csrf

                <x-verum.form.select
                    name="resolution"
                    :label="__('Outcome')"
                    :options="$resolutions"
                    :placeholder="__('Choose…')"
                    required
                    full
                />

                <x-verum.form.textarea
                    name="note"
                    :label="__('What both sides should know')"
                    maxlength="2000"
                    :hint="__('This is quoted in the email to each party.')"
                />

                <div class="cform__actions">
                    <button class="btn btn--ruby" type="submit">{{ __('Settle and notify') }}</button>
                </div>
            </form>
        </x-verum.account.panel>
    @else
        <x-verum.account.panel :title="__('Decision')" icon="check">
            <p class="casebody"><strong>{{ $case->resolutionLabel() }}</strong></p>

            @if ($case->resolution_note)
                <p class="casebody">{{ $case->resolution_note }}</p>
            @endif

            <p class="mute">
                {{ __('Settled by :who on :when', [
                    'who' => $case->resolver?->username ?? __('an admin'),
                    'when' => $case->resolved_at?->format('j M Y, H:i'),
                ]) }}
            </p>
        </x-verum.account.panel>
    @endif
</x-layouts::admin>
