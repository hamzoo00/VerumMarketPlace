@props(['stones'])

<section class="sec feat" data-spy="{{ __('Featured') }}" aria-labelledby="featTitle">
    <div class="wrap">
        <x-verum.section-head
            :kicker="__('Just listed')"
            :title="__('New on the')"
            :emphasis="__('counter.')"
            title-id="featTitle"
            :aside="__('Fresh stones from vetted sellers, each with an independent report.')"
        />

        <div class="cards cards--4">
            @foreach ($stones as $index => $stone)
                <x-verum.stone.card :$stone :$index />
            @endforeach
        </div>

        <div class="sec__more">
            <a class="btn btn--ghost" href="{{ route('shop') }}" wire:navigate>
                {{ __('View all gemstones') }}<x-verum.icon name="arrow" />
            </a>
        </div>
    </div>
</section>
