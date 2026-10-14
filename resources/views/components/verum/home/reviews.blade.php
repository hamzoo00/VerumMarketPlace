@props(['reviews'])

<section class="sec voices" data-spy="{{ __('Voices') }}" aria-labelledby="voicesTitle">
    <div class="wrap">
        <x-verum.section-head
            :kicker="__('Buyers')"
            :title="__('In their own')"
            :emphasis="__('words.')"
            title-id="voicesTitle"
        />

        <div class="quotes">
            @foreach ($reviews as $index => $review)
                <x-verum.review-card :$review :$index />
            @endforeach
        </div>
    </div>
</section>
