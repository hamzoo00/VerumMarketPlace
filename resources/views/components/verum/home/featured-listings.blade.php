@props(['listings'])

{{--
    Paid feature slots, distinct from "Just listed" above: these are
    premium sellers who asked and were granted a slot by an admin.
    Rendered only when there is something in it, so an empty programme
    leaves no hole in the page.
--}}
@if ($listings->isNotEmpty())
    <section class="sec feat feat--paid" data-spy="{{ __('Featured') }}" aria-labelledby="featPaidTitle">
        <div class="wrap">
            <x-verum.section-head
                :kicker="__('Featured')"
                :title="__('Chosen by our')"
                :emphasis="__('team.')"
                title-id="featPaidTitle"
                :aside="__('Stones from premium sellers, each reviewed before it appears here.')"
            />

            <div class="cards cards--4">
                @foreach ($listings as $index => $listing)
                    <x-verum.listing.card :$listing :$index />
                @endforeach
            </div>
        </div>
    </section>
@endif
