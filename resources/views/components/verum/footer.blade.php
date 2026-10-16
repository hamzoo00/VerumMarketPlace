<footer class="ft">
    <div class="wrap ft__top">
        <div class="ft__brand">
            <x-verum.brand class="brand brand--ft" />
            <p>{{ __('Natural and certified gemstones, sourced directly from vetted cutters and verified sellers worldwide.') }}</p>

            <form class="news" id="newsForm" novalidate>
                <label class="lbl" for="newsIn">{{ __('The Report · new lots and field notes, monthly') }}</label>
                <div class="news__row">
                    <input id="newsIn" type="email" placeholder="you@example.com" autocomplete="email" required>
                    <button class="btn btn--ruby btn--sm" type="submit">{{ __('Subscribe') }}</button>
                </div>
            </form>
        </div>

        @foreach ($footerLinks as $column)
            <nav class="ft__col" aria-label="{{ $column['label'] }}">
                <h3>{{ $column['label'] }}</h3>
                @foreach ($column['links'] as $link)
                    <a href="{{ $link['url'] }}" wire:navigate>{{ $link['label'] }}</a>
                @endforeach
            </nav>
        @endforeach
    </div>

    <div class="ft__giant" aria-hidden="true" data-reveal>
        @foreach (str_split('VERUM') as $letter)
            <span>{{ $letter }}</span>
        @endforeach
    </div>

    <div class="wrap ft__bottom">
        <p>{{ __('© :year :app. All rights reserved.', ['year' => now()->year, 'app' => config('app.name')]) }}</p>

        @if (config('verum.demo_mode'))
            <p class="ft__demo"><i class="dot-live"></i>{{ __('Demo UI — not a live store') }}</p>
        @endif

        @if (config('verum.social.instagram') || config('verum.social.youtube'))
            <div class="ft__social">
                @if ($instagram = config('verum.social.instagram'))
                    <a class="ibtn ibtn--line" href="{{ $instagram }}" target="_blank" rel="noopener" aria-label="Instagram">
                        <x-verum.icon name="ig" />
                    </a>
                @endif
                @if ($youtube = config('verum.social.youtube'))
                    <a class="ibtn ibtn--line" href="{{ $youtube }}" target="_blank" rel="noopener" aria-label="YouTube">
                        <x-verum.icon name="yt" />
                    </a>
                @endif
            </div>
        @endif

        <p class="ft__sign">{{ config('verum.signature') }}</p>
    </div>
</footer>
