{{--
    Global overlays: command-palette search, saved-stones drawer, modals,
    the gem-guide chat, toasts and the back-to-top control.
    Markup only — behaviour lives in resources/js/verum/.
--}}

<div class="search" id="search" role="dialog" aria-modal="true" aria-label="{{ __('Search') }}" hidden>
    <div class="search__scrim" data-close-search></div>
    <div class="search__panel">
        <div class="search__bar">
            <x-verum.icon name="search" />
            <input
                id="searchIn"
                type="search"
                placeholder="{{ __('Search stones, origins, sellers, or a report number') }}"
                autocomplete="off"
                spellcheck="false"
                role="combobox"
                aria-expanded="true"
                aria-controls="searchList"
                aria-autocomplete="list"
            >
            <kbd>Esc</kbd>
            <button class="ibtn" type="button" data-close-search aria-label="{{ __('Close search') }}">
                <x-verum.icon name="close" />
            </button>
        </div>
        <div class="search__body">
            <div class="search__popular" id="searchPopular">
                <p class="lbl">{{ __('Popular') }}</p>
                <div class="search__chips">
                    @foreach (['Ruby', 'Sapphire', 'Emerald', 'Padparadscha', 'Unheated', 'Mogok'] as $term)
                        <button type="button" class="chip" data-q="{{ $term }}">{{ $term }}</button>
                    @endforeach
                </div>
                <div id="searchRecent"></div>
            </div>
            <div class="search__list" id="searchList" role="listbox" aria-label="{{ __('Results') }}"></div>
        </div>
        <div class="search__foot">
            <span><kbd>↑</kbd><kbd>↓</kbd> {{ __('move') }}</span>
            <span><kbd>Enter</kbd> {{ __('open') }}</span>
            <span><kbd>Esc</kbd> {{ __('close') }}</span>
        </div>
    </div>
</div>

<div class="drawer" id="wish" role="dialog" aria-modal="true" aria-labelledby="wishTitle" hidden>
    <div class="drawer__scrim" data-close-wish></div>
    <div class="drawer__panel">
        <header class="drawer__head">
            <h2 id="wishTitle">{{ __('Saved stones') }} <span class="drawer__n" data-wish-count2>0</span></h2>
            <button class="ibtn" type="button" data-close-wish aria-label="{{ __('Close') }}">
                <x-verum.icon name="close" />
            </button>
        </header>
        <div class="drawer__body" id="wishBody"></div>
    </div>
</div>

<div class="modal" id="bidModal" role="dialog" aria-modal="true" aria-labelledby="bidTitle" hidden>
    <div class="modal__scrim" data-close-modal></div>
    <div class="modal__card" id="bidCard"></div>
</div>

<div class="modal" id="okModal" role="dialog" aria-modal="true" aria-labelledby="okTitle" hidden>
    <div class="modal__scrim" data-close-modal></div>
    <div class="modal__card modal__card--ok" id="okCard"></div>
</div>

<x-verum.chat-widget />

<x-verum.toast-host />

<button class="totop" id="totop" type="button" aria-label="{{ __('Back to top') }}">
    <svg class="totop__ring" viewBox="0 0 48 48" aria-hidden="true">
        <circle cx="24" cy="24" r="22" />
        <circle class="totop__fill" cx="24" cy="24" r="22" />
    </svg>
    <x-verum.icon name="arrow-up" />
</button>
