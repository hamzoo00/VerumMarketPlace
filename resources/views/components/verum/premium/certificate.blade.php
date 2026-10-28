@props([
    'listing',
    // A preview renders the frame with the seller's own details and no
    // stone, for the admin and seller panels.
    'preview' => false,
])

@php
    use App\Support\QrCode;

    // The certificate belongs to a seller; a listing simply carries
    // whichever one its seller holds.
    $subject = $listing?->seller ?? ($preview instanceof \App\Models\Seller ? $preview : null);
    $reference = $subject?->certificate_reference;

    $verifyUrl = $reference === null ? null : route('verify', $reference);

    /*
     * The frame is the seller's; the rows are this stone's. One
     * certificate is issued per seller and reads against whatever they
     * publish, which is why the gem details come from the listing and
     * the seal from the seller.
     */
    $rows = $listing === null ? [] : array_filter([
        __('Gem type') => $listing->category?->name,
        __('Weight') => $listing->carat ? __(':n ct', ['n' => rtrim(rtrim(number_format((float) $listing->carat, 2), '0'), '.')]) : null,
        __('Origin') => $listing->origin ?: \App\Support\Countries::name($listing->origin_country),
        __('Treatment') => $listing->untreated ? __('No indication of treatment') : ($listing->treatment ?: null),
        __('Graded by') => $listing->lab ? __(':lab · independent lab', ['lab' => $listing->lab]) : null,
    ]);
@endphp

{{--
    The certificate card, ported from the demo's .mcert markup so it
    reads identically. It is rendered from the seller's issued
    certificate and this listing's own disclosed details — nothing here
    is decorative, so a row with no data is dropped rather than faked.
--}}
<figure class="mcert" @if ($reference) data-certificate="{{ $reference }}" @endif>
    <div class="mcert__guil" aria-hidden="true"></div>

    <div class="mcert__head">
        <svg class="mcert__logo" aria-hidden="true"><use href="#vm-mark" /></svg>

        <div>
            <p class="mcert__t">{{ __('Verified Seller') }}</p>
            <p class="mcert__s">{{ __(':app · issued by our team', ['app' => config('app.name')]) }}</p>
        </div>

        @if ($reference)
            <p class="mcert__no"><span>{{ __('Certificate No.') }}</span>{{ $reference }}</p>
        @endif
    </div>

    @if ($rows !== [])
        <dl class="mcert__rows">
            @foreach ($rows as $label => $value)
                <div>
                    <dt>{{ $label }}</dt>
                    <dd>{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    @else
        <dl class="mcert__rows">
            <div>
                <dt>{{ __('Seller') }}</dt>
                <dd>{{ $subject?->name }}</dd>
            </div>
            <div>
                <dt>{{ __('Certified') }}</dt>
                <dd>{{ $subject?->certified_at?->format('j M Y') }}</dd>
            </div>
            @if ($subject?->certificate_note)
                <div>
                    <dt>{{ __('Note') }}</dt>
                    <dd>{{ $subject->certificate_note }}</dd>
                </div>
            @endif
        </dl>
    @endif

    <div class="mcert__foot">
        @if ($verifyUrl)
            {{-- Drawn server-side, so a crawler sees it and it survives
                 with scripting off. --}}
            <div class="mcert__qr">
                {!! QrCode::svg($verifyUrl, 92) !!}
                <i class="mcert__scan"></i>
            </div>
        @endif

        <div class="mcert__seal" aria-hidden="true">
            <svg viewBox="0 0 100 100">
                <defs>
                    <path id="certSeal-{{ $reference ?? 'preview' }}" d="M50,50 m-37,0 a37,37 0 1,1 74,0 a37,37 0 1,1 -74,0" />
                </defs>
                <text>
                    <textPath href="#certSeal-{{ $reference ?? 'preview' }}" textLength="228" lengthAdjust="spacing">
                        {{ __('VERIFIED SELLER · :app ·', ['app' => Str::upper(config('app.name'))]) }}
                    </textPath>
                </text>
            </svg>
            <span class="mcert__seal-core"><svg><use href="#vm-mark" /></svg></span>
        </div>

        <p class="mcert__stamp">
            <x-verum.icon name="check" />{{ __('Verified') }}
        </p>
    </div>
</figure>
