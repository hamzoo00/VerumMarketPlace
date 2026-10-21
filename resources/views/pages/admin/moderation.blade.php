@php
    $states = [
        'pending' => __('Needs review'),
        'upheld' => __('Upheld'),
        'cleared' => __('False positives'),
        'all' => __('All'),
    ];
@endphp

<x-layouts::admin
    :title="__('Flagged messages')"
    :heading="__('Moderation')"
    :lede="__('Suspected attempts to move a deal off :app. Messages were delivered — these are the record.', ['app' => config('app.name')])"
>
    <nav class="adminfilters" aria-label="{{ __('Filter by state') }}">
        @foreach ($states as $value => $label)
            <a
                href="{{ route('admin.moderation', ['state' => $value]) }}"
                @class(['chip', 'is-on' => $state === $value])
                @if ($state === $value) aria-current="page" @endif
                wire:navigate
            >{{ $label }}@if ($value === 'pending' && $pendingCount > 0) <b>{{ $pendingCount }}</b>@endif</a>
        @endforeach
    </nav>

    @if ($messages->isEmpty())
        <x-verum.account.panel :title="__('Nothing flagged')" icon="shield">
            <x-verum.account.empty
                icon="shield"
                :title="__('Queue is clear')"
                :body="__('No messages match that filter.')"
            />
        </x-verum.account.panel>
    @else
        <div class="flaglist">
            @foreach ($messages as $message)
                <article class="flagrow">
                    <header class="flagrow__head">
                        <span class="chip chip--warn">{{ $message->flagLabels() }}</span>

                        @if ($message->review_state)
                            <span @class(['chip', 'chip--ok' => $message->review_state === \App\Models\Message::REVIEW_CLEARED])>
                                {{ $message->review_state === \App\Models\Message::REVIEW_UPHELD ? __('Upheld') : __('False positive') }}
                            </span>
                        @endif

                        <time datetime="{{ $message->created_at?->toIso8601String() }}">
                            {{ $message->created_at?->diffForHumans() }}
                        </time>
                    </header>

                    <p class="flagrow__who">
                        {{ __('From the :side', ['side' => $message->sender_type]) }} ·
                        {{ $message->conversation?->buyer?->name }} ↔ {{ $message->conversation?->seller?->name }}

                        @if ($offences->for($message) > 0)
                            {{-- Upheld before, so this row is a pattern and
                                 not a first offence. --}}
                            <span @class(['chip', 'chip--warn' => $offences->isRepeat($message)])>
                                {{ trans_choice(
                                    '{1} :count upheld flag before|[2,*] :count upheld flags before',
                                    $offences->for($message),
                                ) }}
                            </span>
                        @endif
                    </p>

                    {{-- Quoted, not rendered as the sender wrote it. --}}
                    <blockquote class="flagrow__body">{{ $message->body }}</blockquote>

                    <div class="flagrow__acts">
                        <a class="btn btn--ghost btn--sm" href="{{ route('chat.show', $message->conversation_id) }}">
                            {{ __('Read the thread') }}
                        </a>

                        @if ($message->review_state === null)
                            <form method="POST" action="{{ route('admin.moderation.review', $message) }}" class="offeract">
                                @csrf
                                <input type="hidden" name="verdict" value="{{ \App\Models\Message::REVIEW_UPHELD }}">
                                <button class="btn btn--ruby btn--sm" type="submit">{{ __('Uphold') }}</button>
                            </form>

                            <form method="POST" action="{{ route('admin.moderation.review', $message) }}" class="offeract">
                                @csrf
                                <input type="hidden" name="verdict" value="{{ \App\Models\Message::REVIEW_CLEARED }}">
                                <button class="btn btn--ghost btn--sm" type="submit">{{ __('False positive') }}</button>
                            </form>
                        @elseif ($message->reviewer)
                            <span class="mute">{{ __('by :who', ['who' => $message->reviewer->username]) }}</span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <div class="pager">{{ $messages->onEachSide(1)->links() }}</div>
    @endif
</x-layouts::admin>
