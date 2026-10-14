@props([
    'review',
    'index' => 0,
])

<figure class="q" data-reveal style="--d:{{ min($index, 8) * 70 }}">
    <header>
        <span class="q__av">{{ $review->initials() }}</span>
        <div>
            <b>{{ $review->reviewer_name }}</b>
            <small>{{ $review->place }} · {{ $review->reviewed_on->format('d/m/Y') }}</small>
        </div>
        <x-verum.stars :count="$review->stars" />
    </header>
    <blockquote>{{ $review->body }}</blockquote>
    <figcaption>{{ $review->stone_name }}</figcaption>
</figure>
