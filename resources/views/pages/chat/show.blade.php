@php
    use App\Models\Message;

    $files = config('verum_chat.attachments');

    /*
     * The receipt goes on the viewer's newest own message, and only on
     * the last page — paging is oldest-first, so on an earlier page the
     * "latest" row is not actually the latest.
     */
    $receiptId = $messages->currentPage() === $messages->lastPage()
        ? $messages->getCollection()
            ->last(fn ($message): bool => $message->sender_type === $viewer)?->id
        : null;

    $counterpart = $viewer === Message::FROM_ADMIN
        ? $conversation->buyer?->name.' · '.$conversation->seller?->name
        : $conversation->counterpartName($viewer);
@endphp

<x-verum.chat.shell
    :$viewer
    :$account
    :title="$counterpart"
    :heading="__('Messages')"
>
    {{-- Re-read over the socket when either side posts; see live-chat.js --}}
    <div class="thread" data-chat="{{ $conversation->id }}">
        <x-verum.chat.thread-list :$conversations :$viewer :$routes :$active />

        <section class="thread__pane">
            <header class="thread__head">
                {{-- Only shown where the list is hidden, so a narrow
                     screen still has a way back. --}}
                <a class="thread__back" href="{{ route($routes['index']) }}" wire:navigate aria-label="{{ __('All messages') }}">
                    <x-verum.icon name="arrow-l" />
                </a>

                <x-verum.chat.avatar :$conversation :$viewer />

                <div class="thread__who">
                    <h2>{{ $counterpart }}</h2>

                    @if ($conversation->listing)
                        <a class="thread__about" href="{{ route('listings.show', $conversation->listing) }}" wire:navigate>
                            <x-verum.icon name="gem" />{{ $conversation->listing->title }}
                        </a>
                    @elseif ($conversation->subject)
                        <p class="thread__about">{{ $conversation->subject }}</p>
                    @endif
                </div>

                @if ($conversation->is_flagged && $viewer === Message::FROM_ADMIN)
                    <span class="chip chip--warn">{{ __('Flagged') }}</span>
                @endif
            </header>

            <div class="bubbles" data-chat-log>
                @if ($messages->hasPages())
                    <div class="bubbles__more">{{ $messages->links() }}</div>
                @endif

                @foreach ($messages as $index => $message)
                    @php($previous = $messages[$index - 1] ?? null)

                    @if ($message->opensNewDay($previous))
                        {{-- Anchors a thread read back: "09:14" alone
                             could be this morning or last March. --}}
                        <p class="bubbles__day"><span>{{ $message->dayLabel() }}</span></p>
                    @endif

                    <x-verum.chat.bubble
                        :$message
                        :$viewer
                        :grouped="! $message->opensNewDay($previous) && $message->followsSameSpeakerAs($previous)"
                        :show-receipt="$receiptId !== null && $message->id === $receiptId"
                    />
                @endforeach
            </div>

            <form
                class="composer"
                method="POST"
                action="{{ route($routes['store'], $conversation) }}"
                enctype="multipart/form-data"
                data-chat-form
            >
                @csrf

                {{-- Filled by chat-compose.js as the sender types, so the
                     reminder arrives before they commit to sending. --}}
                <p class="composer__warn" data-chat-warning hidden></p>

                <div class="composer__row">
                    <label class="composer__clip" data-tip="{{ __('Add images') }}">
                        <input
                            type="file"
                            class="sr-only"
                            name="attachments[]"
                            accept="{{ implode(',', $files['mimes']) }}"
                            multiple
                            data-chat-files
                        >
                        <x-verum.icon name="upload" />
                        <span class="sr-only">{{ __('Add images') }}</span>
                    </label>

                    <label class="sr-only" for="chatBody">{{ __('Your message') }}</label>
                    <textarea
                        id="chatBody"
                        class="composer__text"
                        name="body"
                        rows="1"
                        maxlength="{{ config('verum_chat.limits.body_max') }}"
                        placeholder="{{ __('Write a message…') }}"
                        data-chat-body
                    >{{ old('body') }}</textarea>

                    <button class="composer__send" type="submit" aria-label="{{ __('Send') }}" data-tip="{{ __('Send') }}">
                        <x-verum.icon name="send" />
                    </button>
                </div>

                <p class="composer__files" data-chat-filenames hidden></p>

                <p class="composer__hint">
                    {{ __('Up to :n images, :mb MB each · :app keeps the record', [
                        'n' => $files['max_per_message'],
                        'mb' => round($files['max_kb'] / 1024),
                        'app' => config('app.name'),
                    ]) }}
                </p>
            </form>
        </section>
    </div>
</x-verum.chat.shell>
