@props(['stone'])

<section class="certsec" id="certSec" aria-labelledby="certTitle">
    <div class="certsec__bg" aria-hidden="true"></div>
    <div class="wrap certsec__grid">
        <div class="certsec__copy">
            <p class="kicker" data-reveal>{{ __('The report') }}</p>
            <h2 class="h2" id="certTitle" data-split>{{ __('Graded by labs') }} <em>{{ __('we don\'t own.') }}</em></h2>
            <p data-reveal>
                {{ __('Every stone is sent to an independent laboratory before it is listed. The report number below is printed on the certificate that ships with the stone.') }}
            </p>
            <ul class="certsec__list" data-reveal>
                <li><x-verum.icon name="check" />{{ __('Species and variety confirmed') }}</li>
                <li><x-verum.icon name="check" />{{ __('Treatment detected and named') }}</li>
                <li><x-verum.icon name="check" />{{ __('Origin opinion where offered') }}</li>
            </ul>
            <a class="btn btn--ghost" href="{{ route('verify', ['report' => $stone->report_number]) }}" wire:navigate>
                <x-verum.icon name="shield" />{{ __('Verify this report') }}
            </a>
        </div>

        <figure class="cert" data-reveal="scale">
            <header class="cert__head">
                <svg class="cert__mark" aria-hidden="true"><use href="#vm-mark" /></svg>
                <div>
                    <p class="cert__t">{{ __(':app Report', ['app' => config('app.name')]) }}</p>
                    <p class="cert__n">{{ $stone->report_number }}</p>
                </div>
                <span class="cert__lab">{{ $stone->lab }}</span>
            </header>

            <dl class="cert__rows">
                <div><dt>{{ __('Issued') }}</dt><dd>{{ $stone->report_date->format('d/m/Y') }}</dd></div>
                <div><dt>{{ __('Lab number') }}</dt><dd>{{ $stone->lab_number }}</dd></div>
                <div><dt>{{ __('Species') }}</dt><dd>{{ $stone->gemType->mineral }}</dd></div>
                <div><dt>{{ __('Variety') }}</dt><dd>{{ $stone->gemType->label }}</dd></div>
                <div><dt>{{ __('Weight') }}</dt><dd>{{ $stone->caratLabel() }}</dd></div>
                <div><dt>{{ __('Measurements') }}</dt><dd>{{ $stone->dimensions }}</dd></div>
                <div><dt>{{ __('Cut') }}</dt><dd>{{ $stone->cut }}</dd></div>
                <div><dt>{{ __('Colour') }}</dt><dd>{{ $stone->color }}</dd></div>
                <div><dt>{{ __('Transparency') }}</dt><dd>{{ $stone->transparency }}</dd></div>
                <div><dt>{{ __('Treatment') }}</dt><dd>{{ $stone->treatment }}</dd></div>
                <div><dt>{{ __('Origin') }}</dt><dd>{{ $stone->originLabel() }}</dd></div>
            </dl>

            @if ($stone->comments)
                <figcaption class="cert__note">{{ $stone->comments }}</figcaption>
            @endif
        </figure>
    </div>
</section>
