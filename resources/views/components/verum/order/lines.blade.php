@props(['order'])

{{--
    Title and price come from the order line, not the listing: the line
    outlives the listing, which is why listing_id is nullable.
--}}
<ul class="olines">
    @foreach ($order->items as $item)
        <li class="oline">
            <div class="oline__what">
                @if ($item->listing && $item->slug)
                    <a class="link" href="{{ route('listings.show', $item->slug) }}" wire:navigate>{{ $item->title }}</a>
                @else
                    <span>{{ $item->title }}</span>
                    <small class="mute">{{ __('No longer listed') }}</small>
                @endif
            </div>

            <span class="oline__qty">× {{ $item->quantity }}</span>
            <span class="oline__price">{{ $item->lineTotal()->format() }}</span>
        </li>
    @endforeach
</ul>
