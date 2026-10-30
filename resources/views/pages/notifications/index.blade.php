<x-verum.account.shell
    :$viewer
    :$account
    :title="__('Notifications')"
    :heading="__('Notifications')"
    :lede="__('Everything we have told you, newest first. Opening one marks it read.')"
>
    {{-- Re-read when something new arrives or "mark all" is used. --}}
    <div data-live-region="notifications">
        <div class="nlist__bar">
            <p class="mute">
                {{ trans_choice('{0} Nothing unread.|{1} :count unread.|[2,*] :count unread.', $unread) }}
            </p>

            <form method="POST" action="{{ $routes['readAll'] }}" data-live-form>
                @csrf
                <button class="btn btn--ghost btn--sm" type="submit" @disabled($unread === 0)>
                    <x-verum.icon name="check" />{{ __('Mark all as read') }}
                </button>
            </form>
        </div>

        @if ($notifications->isEmpty())
            <x-verum.account.panel :title="__('Nothing yet')" icon="bell">
                <x-verum.account.empty
                    icon="bell"
                    :title="__('No notifications')"
                    :body="__('Decisions about your account, listings and orders will appear here.')"
                />
            </x-verum.account.panel>
        @else
            <div class="nlist">
                @foreach ($notifications as $item)
                    <x-verum.notifications.item :$item />
                @endforeach
            </div>

            <div class="pager">{{ $notifications->onEachSide(1)->links() }}</div>
        @endif
    </div>
</x-verum.account.shell>
