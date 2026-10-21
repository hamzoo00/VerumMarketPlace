@php
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
@endphp

<x-verum.chat.shell
    :$viewer
    :$account
    :title="$conversation->counterpartName($viewer)"
    :heading="$viewer === \App\Models\Message::FROM_ADMIN
        ? $conversation->buyer?->name.' and '.$conversation->seller?->name
        : $conversation->counterpartName($viewer)"
    :lede="$conversation->subject"
>
    <a class="link chat__back" href="{{ route('chat.index') }}" wire:navigate>
        {{ __('← All messages') }}
    </a>

    @if ($conversation->listing)
        <x-verum.account.panel :title="__('About this listing')" icon="gem">
            <a class="link" href="{{ route('listings.show', $conversation->listing) }}" wire:navigate>
                {{ $conversation->listing->title }}
            </a>
        </x-verum.account.panel>
    @endif

    {{-- Re-read over the socket when either side posts; see live-chat.js --}}
    <div class="thread__view" data-chat="{{ $conversation->id }}">
        <div class="bubbles" data-chat-log>
            @foreach ($messages as $message)
                <x-verum.chat.bubble
                    :$message
                    :$viewer
                    :show-receipt="$receiptId !== null && $message->id === $receiptId"
                />
            @endforeach
        </div>

        @if ($messages->hasPages())
            <div class="pager">{{ $messages->links() }}</div>
        @endif

        <form
            class="composer"
            method="POST"
            action="{{ route('chat.store', $conversation) }}"
            enctype="multipart/form-data"
            data-chat-form
        >
            @csrf

            <label class="sr-only" for="chatBody">{{ __('Your message') }}</label>
            <textarea
                id="chatBody"
                class="composer__text"
                name="body"
                rows="3"
                maxlength="{{ config('verum_chat.limits.body_max') }}"
                placeholder="{{ __('Write a message…') }}"
                data-chat-body
            >{{ old('body') }}</textarea>

            {{-- Filled by moderation.js as the sender types, so the
                 reminder arrives before they commit to sending. --}}
            <p class="composer__warn" data-chat-warning hidden></p>

            <div class="composer__foot">
                <label class="btn btn--ghost btn--sm">
                    <input
                        type="file"
                        class="sr-only"
                        name="attachments[]"
                        accept="{{ implode(',', $files['mimes']) }}"
                        multiple
                        data-chat-files
                    >
                    <x-verum.icon name="upload" />{{ __('Add images') }}
                </label>

                <span class="composer__hint" data-chat-filenames>
                    {{ __('Up to :n images, :mb MB each', [
                        'n' => $files['max_per_message'],
                        'mb' => round($files['max_kb'] / 1024),
                    ]) }}
                </span>

                <button class="btn btn--ruby btn--sm" type="submit">{{ __('Send') }}</button>
            </div>
        </form>

        <p class="composer__policy">
            <x-verum.icon name="shield" />
            {{ __('Keep payments and contact on :app. Off-platform deals are not protected, and we review attempts to move them.', ['app' => config('app.name')]) }}
        </p>
    </div>
</x-verum.chat.shell>
