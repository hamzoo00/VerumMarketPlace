@props(['navigation'])

<div class="mnav" id="mnav" aria-hidden="true">
    <div class="mnav__bg" aria-hidden="true"></div>
    <div class="mnav__inner wrap">
        <nav class="mnav__links" aria-label="{{ __('Mobile') }}">
            @foreach ($navigation as $index => $item)
                <a href="{{ $item['url'] }}" data-nav="{{ $item['key'] }}" wire:navigate>
                    <span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>{{ $item['label'] }}
                </a>
            @endforeach
        </nav>
        <div class="mnav__foot">
            <a href="{{ route('verify') }}" wire:navigate>
                <x-verum.icon name="shield" />{{ __('Verify a report') }}
            </a>
            <a href="{{ route('sell') }}" wire:navigate>
                <x-verum.icon name="gem" />{{ __('Sell on :app', ['app' => config('app.name')]) }}
            </a>
            <a href="mailto:{{ config('verum.contact.email') }}">
                <x-verum.icon name="mail" />{{ config('verum.contact.email') }}
            </a>
        </div>
    </div>
</div>
