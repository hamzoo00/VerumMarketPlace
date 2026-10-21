@props([
    'message',
    'viewer',
    // Only the newest of your own messages carries a receipt; one on
    // every bubble is noise, and the latest is what you want to know.
    'showReceipt' => false,
])

@php
    $mine = $message->sender_type === $viewer;
    $fromAdmin = $message->isFromAdmin();
@endphp

{{-- One message. Body is escaped by Blade; attachments are images only. --}}
<article @class([
    'bubble',
    'bubble--mine' => $mine,
    'bubble--staff' => $fromAdmin,
    'bubble--flagged' => $message->is_flagged,
])>
    <header class="bubble__head">
        <span class="bubble__who">
            @if ($fromAdmin)
                {{ __(':app team', ['app' => config('app.name')]) }}
            @elseif ($mine)
                {{ __('You') }}
            @else
                {{ $message->conversation?->counterpartName($viewer) }}
            @endif
        </span>

        <time class="bubble__at" datetime="{{ $message->created_at?->toIso8601String() }}">
            {{ $message->created_at?->format('j M, H:i') }}
        </time>
    </header>

    @if (filled($message->body))
        <p class="bubble__body">{{ $message->body }}</p>
    @endif

    @if ($message->attachments() !== [])
        <div class="bubble__shots">
            @foreach ($message->attachments() as $shot)
                <a href="{{ $shot['url'] }}" target="_blank" rel="noopener noreferrer">
                    <img src="{{ $shot['thumb'] }}" alt="{{ __('Attached image') }}" loading="lazy" decoding="async">
                </a>
            @endforeach
        </div>
    @endif

    @if ($showReceipt && $mine && ! $fromAdmin)
        {{-- `read_at` is stamped only by the other party, so this says
             they opened the thread — not that staff looked at it. --}}
        <p class="bubble__receipt">
            @if ($message->read_at)
                <x-verum.icon name="check" />
                <time datetime="{{ $message->read_at->toIso8601String() }}">
                    {{ __('Read :when', ['when' => $message->read_at->diffForHumans()]) }}
                </time>
            @else
                {{ __('Sent') }}
            @endif
        </p>
    @endif

    @if ($message->is_flagged)
        {{-- The sender sees why; the other side sees nothing, so a flag
             is never a way to accuse someone publicly. --}}
        @if ($mine || $viewer === \App\Models\Message::FROM_ADMIN)
            <p class="bubble__flag">
                <x-verum.icon name="shield" />
                {{ __('Flagged: :reasons', ['reasons' => $message->flagLabels()]) }}
            </p>
        @endif
    @endif
</article>
