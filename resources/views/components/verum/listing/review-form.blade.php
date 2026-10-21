@props(['listing'])

@php
    use App\Support\ReviewEligibility;

    $buyer = auth('buyer')->user();
    $canReview = $buyer !== null && ReviewEligibility::canReview($listing, $buyer);
@endphp

@if ($canReview)
    {{-- Shown only to a buyer who actually bought this; the controller
         re-checks, so this is presentation, not the guard. --}}
    <section class="revform" aria-labelledby="revTitle">
        <h2 class="lbl" id="revTitle">{{ __('How did it go?') }}</h2>

        <form method="POST" action="{{ route('listings.reviews.store', $listing) }}">
            @csrf

            <fieldset class="revform__stars">
                <legend class="sr-only">{{ __('Your rating') }}</legend>

                @foreach (range(5, 1) as $value)
                    <input
                        type="radio"
                        id="star{{ $value }}"
                        name="stars"
                        value="{{ $value }}"
                        @checked(old('stars') == $value)
                        required
                    >
                    <label for="star{{ $value }}" title="{{ trans_choice(':count star|:count stars', $value) }}">
                        <x-verum.icon name="star" />
                    </label>
                @endforeach
            </fieldset>

            <x-verum.form.textarea
                name="body"
                :label="__('What should other buyers know?')"
                maxlength="1500"
                required
            />

            <button class="btn btn--ruby btn--sm" type="submit">{{ __('Post review') }}</button>
        </form>
    </section>
@endif
