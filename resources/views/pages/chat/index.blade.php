<x-verum.chat.shell
    :$viewer
    :$account
    :title="__('Messages')"
    :heading="__('Messages')"
    :lede="$viewer === \App\Models\Message::FROM_ADMIN
        ? __('Every conversation on the platform. Flagged threads first.')
        : __('Your conversations about listings.')"
>
    @if ($conversations->isEmpty())
        <x-verum.account.panel :title="__('No messages')" icon="chat">
            <x-verum.account.empty
                icon="chat"
                :title="__('Nothing here yet')"
                :body="__('Open a conversation from any listing to ask the seller a question.')"
            />
        </x-verum.account.panel>
    @else
        <div class="threads">
            @foreach ($conversations as $conversation)
                @php $unread = $conversation->unreadFor($viewer); @endphp

                <a
                    @class(['thread', 'is-unread' => $unread > 0, 'is-flagged' => $conversation->is_flagged])
                    href="{{ route('chat.show', $conversation) }}"
                    wire:navigate
                >
                    <span class="thread__who">
                        {{ $viewer === \App\Models\Message::FROM_ADMIN
                            ? $conversation->buyer?->name.' · '.$conversation->seller?->name
                            : $conversation->counterpartName($viewer) }}
                    </span>

                    @if ($conversation->subject)
                        <span class="thread__subject">{{ $conversation->subject }}</span>
                    @endif

                    <span class="thread__preview">{{ $conversation->last_message_preview }}</span>

                    <span class="thread__meta">
                        @if ($conversation->is_flagged)
                            <span class="chip chip--warn">{{ __('Flagged') }}</span>
                        @endif

                        @if ($unread > 0)
                            <span class="thread__badge">{{ $unread }}</span>
                        @endif

                        <time datetime="{{ $conversation->last_message_at?->toIso8601String() }}">
                            {{ $conversation->last_message_at?->diffForHumans() }}
                        </time>
                    </span>
                </a>
            @endforeach
        </div>

        <div class="pager">{{ $conversations->onEachSide(1)->links() }}</div>
    @endif
</x-verum.chat.shell>
