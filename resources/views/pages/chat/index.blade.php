<x-verum.chat.shell
    :$viewer
    :$account
    :title="__('Messages')"
    :heading="__('Messages')"
    :lede="$viewer === \App\Models\Message::FROM_ADMIN
        ? __('Every conversation on the platform. Flagged threads first.')
        : __('Your conversations about listings.')"
>
    {{--
        The list alone, with the reading pane empty. On a wide screen the
        same two columns appear here and on a thread, so opening one is a
        change of pane rather than a change of page.
    --}}
    <div class="thread thread--empty">
        <x-verum.chat.thread-list :$conversations :$viewer :$routes :$active />

        <section class="thread__pane thread__pane--blank">
            <div class="threadblank">
                <x-verum.icon name="chat" />
                <h2>{{ __('Nothing open') }}</h2>
                <p>
                    {{ $conversations->isEmpty()
                        ? __('Open a conversation from any listing to ask the seller a question.')
                        : __('Choose a conversation to read it.') }}
                </p>

                @if ($conversations->isEmpty() && $viewer === \App\Models\Message::FROM_BUYER)
                    <a class="btn btn--ghost btn--sm" href="{{ route('shop') }}" wire:navigate>
                        {{ __('Browse gemstones') }}
                    </a>
                @endif
            </div>
        </section>
    </div>
</x-verum.chat.shell>
