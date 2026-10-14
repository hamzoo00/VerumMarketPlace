{{-- Scripted gem-guide assistant. Replies are canned until the backend lands. --}}
<div class="chat" id="chat">
    <button
        class="chat__orb"
        id="chatOrb"
        type="button"
        aria-label="{{ __('Open the Verum gem guide') }}"
        aria-expanded="false"
        aria-controls="chatPanel"
    >
        <span class="chat__orb-ring" aria-hidden="true"></span>
        <svg class="chat__orb-mark" aria-hidden="true"><use href="#vm-mark" /></svg>
        <x-verum.icon name="close" class="ic chat__orb-x" />
        <span class="chat__ask" aria-hidden="true">{{ __('Ask me') }}</span>
    </button>

    <div class="chat__panel" id="chatPanel" role="dialog" aria-label="{{ __('Verum gem guide') }}" hidden>
        <header class="chat__head">
            <span class="chat__av"><svg aria-hidden="true"><use href="#vm-mark" /></svg></span>
            <div>
                <p class="chat__t">{{ __('Verum gem guide') }}</p>
                <p class="chat__s"><i class="dot-live"></i>{{ __('Demo · scripted replies') }}</p>
            </div>
            <button class="ibtn" type="button" data-close-chat aria-label="{{ __('Close chat') }}">
                <x-verum.icon name="close" />
            </button>
        </header>

        <div class="chat__log" id="chatLog" aria-live="polite"></div>

        <div class="chat__quick" id="chatQuick">
            @foreach ([__('What is a certificate?'), __('Find a ruby'), __('Shipping'), __('Talk to someone')] as $prompt)
                <button type="button" data-ask="{{ $prompt }}">{{ $prompt }}</button>
            @endforeach
        </div>

        <form class="chat__form" id="chatForm">
            <label class="sr-only" for="chatIn">{{ __('Message') }}</label>
            <input id="chatIn" placeholder="{{ __('Ask about a stone, a report, shipping…') }}" autocomplete="off">
            <button class="chat__send" type="submit" aria-label="{{ __('Send') }}">
                <x-verum.icon name="send" />
            </button>
        </form>
    </div>
</div>
