@props(['stone'])

<dl class="specs" data-reveal>
    <div><dt>{{ __('Weight') }}</dt><dd>{{ $stone->caratLabel() }}</dd></div>
    <div><dt>{{ __('Dimensions') }}</dt><dd>{{ $stone->dimensions }}</dd></div>
    <div><dt>{{ __('Shape / cut') }}</dt><dd>{{ $stone->cut }}</dd></div>
    <div><dt>{{ __('Colour') }}</dt><dd>{{ $stone->color }}</dd></div>
    <div>
        <dt>{{ __('Treatment') }}</dt>
        <dd>
            {{ $stone->treatment }}@unless ($stone->untreated) <span class="disc">{{ __('Disclosed') }}</span>@endunless
        </dd>
    </div>
    <div><dt>{{ __('Origin') }}</dt><dd>{{ $stone->originLabel() }}</dd></div>
    <div>
        <dt>{{ __('Certificate') }}</dt>
        <dd>
            <button class="link link--mono" type="button" data-scrollto="#certSec">
                {{ $stone->report_number }}<x-verum.icon name="arrow" />
            </button>
        </dd>
    </div>
    <div>
        <dt>{{ __('Sold by') }}</dt>
        <dd>
            <a class="link" href="{{ route('sellers.show', $stone->seller) }}" wire:navigate>{{ $stone->seller->name }}</a>
            <span class="vtick" title="{{ __('Verified seller') }}"><x-verum.icon name="check" /></span>
        </dd>
    </div>
</dl>
