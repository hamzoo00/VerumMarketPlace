<x-layouts::public
    :title="__('Verify a report')"
    :description="__('Check a :app report number against the stone it was issued for. Species, weight, treatment and origin, exactly as graded.', ['app' => config('app.name')])"
>
    <x-verum.page-head
        :title="__('Verify a')"
        :emphasis="__('report')"
        :lede="__('Enter the report number printed on the certificate. It must match the stone it shipped with.')"
        :crumbs="[__('Verify') => null]"
    />

    <div class="wrap verify">
        <form class="verify__form" method="GET" action="{{ route('verify') }}" data-reveal>
            <label class="lbl" for="reportIn">{{ __('Report number') }}</label>
            <div class="verify__row">
                <input
                    id="reportIn"
                    name="report"
                    type="text"
                    value="{{ $report }}"
                    placeholder="VR-2026-002448"
                    autocomplete="off"
                    spellcheck="false"
                    required
                >
                <button class="btn btn--ruby" type="submit">
                    <x-verum.icon name="shield" />{{ __('Verify') }}
                </button>
            </div>
            <p class="verify__hint">{{ __('Try VR-2026-002448 from the demo catalogue.') }}</p>
        </form>

        @if ($notFound)
            <div class="verify__result verify__result--bad" data-reveal role="status">
                <x-verum.icon name="close" class="ic verify__ic" />
                <div>
                    <h2>{{ __('No report found.') }}</h2>
                    <p>{{ __('We have no record of :report. Check the number, or contact us if it came with a stone bought here.', ['report' => $report]) }}</p>
                    <a class="btn btn--ghost btn--sm" href="{{ route('contact') }}" wire:navigate>{{ __('Contact us') }}</a>
                </div>
            </div>
        @elseif ($stone)
            <div class="verify__result verify__result--ok" data-reveal role="status">
                <x-verum.icon name="check" class="ic verify__ic" />
                <div>
                    <h2>{{ __('Report verified.') }}</h2>
                    <p>{{ __('This report was issued for the stone below.') }}</p>
                </div>
            </div>

            <x-verum.stone.certificate :$stone />

            <div class="wrap verify__stone">
                <x-verum.stone.card :$stone />
            </div>
        @endif
    </div>
</x-layouts::public>
