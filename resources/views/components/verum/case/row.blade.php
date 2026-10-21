@props([
    'case',
    'href' => null,
])

{{-- One case, from any side. The reference is what every party quotes. --}}
<article @class(['caserow', 'is-open' => $case->isPending()])>
    <div class="caserow__head">
        <span class="caserow__ref mono">{{ $case->reference }}</span>
        <span @class(['chip', 'chip--live' => $case->isPending(), 'chip--ok' => ! $case->isPending()])>
            {{ $case->statusLabel() }}
        </span>
        <span class="chip">{{ $case->kindLabel() }}</span>
    </div>

    <p class="caserow__reason">{{ $case->reasonLabel() }}</p>

    <dl class="caserow__meta">
        @if ($case->listing)
            <div>
                <dt>{{ __('Listing') }}</dt>
                <dd><a class="link" href="{{ route('listings.show', $case->listing) }}" wire:navigate>{{ $case->listing->title }}</a></dd>
            </div>
        @endif

        @if ($case->seller)
            <div><dt>{{ __('Seller') }}</dt><dd>{{ $case->seller->name }}</dd></div>
        @endif

        <div><dt>{{ __('Raised') }}</dt><dd>{{ $case->created_at->diffForHumans() }}</dd></div>
    </dl>

    @if ($case->resolution)
        <div class="caserow__outcome">
            <strong>{{ $case->resolutionLabel() }}</strong>
            @if ($case->resolution_note)
                <p>{{ $case->resolution_note }}</p>
            @endif
        </div>
    @endif

    @if ($href)
        <a class="btn btn--ghost btn--sm" href="{{ $href }}" wire:navigate>{{ __('Open') }}</a>
    @endif
</article>
