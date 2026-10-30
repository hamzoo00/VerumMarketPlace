{{--
    The notification bell. $feed comes from NotificationBellComposer (or
    the live-refresh endpoint) and is null for a guest, who sees nothing.

    live-notifications.js listens on the account's private channel and,
    when something arrives, re-reads this same component and swaps the
    parts marked data-bell-*: the count, the list, and the "mark all"
    form. The panel itself stays put, so an open bell stays open.
--}}
@if ($feed ?? null)
    @php
        $unread = $feed['unread'];
        $label = $unread === 0
            ? __('Notifications')
            : trans_choice('{1} Notifications, :count unread|[2,*] Notifications, :count unread', $unread);
    @endphp

    <div
        class="bell"
        data-bell
        data-bell-src="{{ $feed['routes']['bell'] }}"
        data-bell-channel="{{ $feed['channel'] }}"
        @if ($feed['area'] === 'admin') data-bell-staff @endif
    >
        <button
            class="ibtn bell__btn"
            type="button"
            aria-expanded="false"
            aria-controls="bellPanel"
            aria-label="{{ $label }}"
            data-tip="{{ __('Notifications') }}"
            data-bell-toggle
        >
            <x-verum.icon name="bell" />
            <span class="badge bell__count" data-bell-count @if ($unread === 0) hidden @endif>
                {{ $unread > 99 ? '99+' : $unread }}
            </span>
        </button>

        <div class="bell__panel" id="bellPanel" role="dialog" aria-label="{{ __('Notifications') }}" data-bell-panel hidden>
            <header class="bell__head">
                <b>{{ __('Notifications') }}</b>

                <form method="POST" action="{{ $feed['routes']['readAll'] }}" data-live-form data-bell-readall @if ($unread === 0) hidden @endif>
                    @csrf
                    <button class="link bell__readall" type="submit">{{ __('Mark all as read') }}</button>
                </form>
            </header>

            <div class="bell__list" data-bell-list>
                @forelse ($feed['items'] as $item)
                    <x-verum.notifications.item :$item compact />
                @empty
                    <p class="bell__empty">
                        <x-verum.icon name="bell" />
                        {{ __('You are all caught up.') }}
                    </p>
                @endforelse
            </div>

            <a class="bell__all" href="{{ $feed['routes']['index'] }}" wire:navigate>
                {{ __('See all notifications') }}<x-verum.icon name="arrow" />
            </a>
        </div>
    </div>
@endif
