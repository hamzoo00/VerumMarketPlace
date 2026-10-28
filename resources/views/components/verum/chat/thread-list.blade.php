@props([
    'conversations',
    'viewer',
    'routes',
    'active' => null,
])

@php
    use App\Models\Message;
@endphp

{{--
    The inbox, rendered as one pane. It sits beside an open conversation
    on a wide screen and stands alone on a narrow one, so a reader can
    move between threads without going back a page.
--}}
<aside class="inbox" aria-label="{{ __('Conversations') }}">
    <header class="inbox__head">
        <h2 class="inbox__title">{{ __('Messages') }}</h2>

        @if ($conversations->total() > 0)
            <span class="inbox__count">{{ $conversations->total() }}</span>
        @endif
    </header>

    {{-- Filters the rendered rows only; the server still owns ordering
         and paging, so this is a convenience and never the source. --}}
    <div class="inbox__search">
        <x-verum.icon name="search" />
        <label class="sr-only" for="threadFilter">{{ __('Filter conversations') }}</label>
        <input
            id="threadFilter"
            type="search"
            placeholder="{{ __('Filter…') }}"
            autocomplete="off"
            data-thread-filter
        >
    </div>

    @if ($conversations->isEmpty())
        <p class="inbox__empty">
            <x-verum.icon name="chat" />
            {{ __('No conversations yet.') }}
        </p>
    @else
        <ul class="inbox__list" data-thread-list>
            @foreach ($conversations as $conversation)
                @php
                    $unread = $conversation->unreadFor($viewer);
                    $who = $viewer === Message::FROM_ADMIN
                        ? $conversation->buyer?->name.' · '.$conversation->seller?->name
                        : $conversation->counterpartName($viewer);
                @endphp

                <li data-thread-row data-label="{{ Str::lower($who.' '.$conversation->last_message_preview) }}">
                    <a
                        @class([
                            'tcard',
                            'is-unread' => $unread > 0,
                            'is-flagged' => $conversation->is_flagged,
                            'is-on' => (int) $active === (int) $conversation->id,
                        ])
                        href="{{ route($routes['show'], $conversation) }}"
                        @if ((int) $active === (int) $conversation->id) aria-current="page" @endif
                        wire:navigate
                    >
                        <x-verum.chat.avatar :$conversation :$viewer />

                        <span class="tcard__body">
                            <span class="tcard__top">
                                <span class="tcard__who">{{ $who }}</span>
                                <time class="tcard__at" datetime="{{ $conversation->last_message_at?->toIso8601String() }}">
                                    {{ $conversation->last_message_at?->diffForHumans(short: true) }}
                                </time>
                            </span>

                            @if ($conversation->listing)
                                <span class="tcard__about">
                                    <x-verum.icon name="gem" />{{ $conversation->listing->title }}
                                </span>
                            @endif

                            <span class="tcard__line">
                                <span class="tcard__preview">{{ $conversation->last_message_preview }}</span>

                                @if ($unread > 0)
                                    <span class="tcard__badge">{{ $unread > 9 ? '9+' : $unread }}</span>
                                @elseif ($conversation->is_flagged)
                                    <x-verum.icon name="shield" class="ic tcard__flag" />
                                @endif
                            </span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>

        <p class="inbox__none" data-thread-none hidden>{{ __('Nothing matches that.') }}</p>

        @if ($conversations->hasPages())
            <div class="inbox__pager">{{ $conversations->onEachSide(0)->links() }}</div>
        @endif
    @endif
</aside>
