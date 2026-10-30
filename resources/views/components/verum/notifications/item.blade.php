@props([
    // One row from NotificationFeed::present().
    'item',
    // The bell's panel is tighter than the full page.
    'compact' => false,
])

{{--
    Opening a notification is a POST: it marks the row read on the
    server, then follows the link — so the count drops wherever the
    reader goes next, with or without JavaScript.
--}}
<form method="POST" action="{{ $item['open'] }}" @class(['nitem', 'is-unread' => ! $item['read'], 'nitem--compact' => $compact])>
    @csrf
    <button class="nitem__btn" type="submit">
        <span class="nitem__icon" aria-hidden="true"><x-verum.icon :name="$item['icon']" /></span>

        <span class="nitem__text">
            <b class="nitem__title">{{ $item['title'] }}</b>
            @if ($item['body'] !== '')
                <span class="nitem__body">{{ $item['body'] }}</span>
            @endif
            <time class="nitem__when" datetime="{{ $item['at'] }}">{{ $item['when'] }}</time>
        </span>

        @unless ($item['read'])
            <span class="nitem__dot"><span class="sr-only">{{ __('Unread') }}</span></span>
        @endunless
    </button>
</form>
