@props(['lots'])

<section class="sec auc" data-spy="{{ __('Auctions') }}" aria-labelledby="aucTitle">
    <div class="wrap">
        <x-verum.section-head
            :kicker="__('This week\'s sale')"
            :title="__('Closing')"
            :emphasis="__('soon.')"
            title-id="aucTitle"
            :aside="__('Bids extend the clock, so a late bid never steals a lot.')"
        />

        <div class="cards cards--4">
            @foreach ($lots as $index => $lot)
                <x-verum.stone.card :stone="$lot->stone" :$index />
            @endforeach
        </div>

        <div class="sec__more">
            <a class="btn btn--ghost" href="{{ route('auctions') }}" wire:navigate>
                {{ __('All live lots') }}<x-verum.icon name="arrow" />
            </a>
        </div>
    </div>
</section>
