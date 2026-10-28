@php
    use App\Support\PremiumStatus;

    $tabs = [
        'premium' => __('Premium'),
        'certified' => __('Certified'),
        'all' => __('All sellers'),
    ];
@endphp

<x-layouts::admin
    :title="__('Premium sellers')"
    :heading="__('Premium')"
    :lede="__('Who gets a feature slot, and whose certificate a buyer sees.')"
>
    <nav class="adminfilters" aria-label="{{ __('Filter sellers') }}">
        @foreach ($tabs as $value => $label)
            <a
                href="{{ route('admin.premium', ['filter' => $value]) }}"
                @class(['chip', 'is-on' => $filter === $value])
                @if ($filter === $value) aria-current="page" @endif
                wire:navigate
            >{{ $label }}@if (($counts[$value] ?? 0) > 0) <b>{{ $counts[$value] }}</b>@endif</a>
        @endforeach
    </nav>

    <form method="GET" action="{{ route('admin.premium') }}" class="adminsearch">
        <input type="hidden" name="filter" value="{{ $filter }}">
        <label class="sr-only" for="premiumSearch">{{ __('Search sellers') }}</label>
        <input id="premiumSearch" type="search" name="q" value="{{ $term }}" placeholder="{{ __('Search by name…') }}">
        <button class="btn btn--ghost btn--sm" type="submit">{{ __('Search') }}</button>
    </form>

    @if ($sellers->isEmpty())
        <x-verum.account.panel :title="__('No sellers')" icon="badge">
            <x-verum.account.empty
                icon="badge"
                :title="__('Nothing matches')"
                :body="__('Try another filter or search.')"
            />
        </x-verum.account.panel>
    @else
        <div class="revlist">
            @foreach ($sellers as $seller)
                @php
                    $isPremium = PremiumStatus::isPremium($seller);
                    $isCertified = PremiumStatus::isCertified($seller);
                @endphp

                <article class="revrow revrow--seller">
                    <div class="revrow__what">
                        <a class="revrow__title" href="{{ route('sellers.show', $seller) }}" target="_blank" rel="noopener">
                            {{ $seller->name }}
                        </a>

                        <p class="revrow__meta">
                            @if ($isPremium)
                                <span class="chip chip--gold">{{ __('Premium') }}</span>
                            @elseif (PremiumStatus::hasExpired($seller))
                                {{-- Lapsed reads differently from never granted. --}}
                                <span class="chip chip--warn">{{ __('Lapsed') }}</span>
                            @endif

                            @if ($isCertified)
                                <span class="chip chip--ok">{{ $seller->certificate_reference }}</span>
                            @endif

                            {{ trans_choice('{0} No listings|{1} :count listing|[2,*] :count listings', $seller->listings_count) }}

                            @if ($seller->premium_until)
                                · {{ __('until :date', ['date' => $seller->premium_until->format('j M Y')]) }}
                            @endif
                        </p>
                    </div>

                    <div class="revrow__acts">
                        @if ($isPremium)
                            <form method="POST" action="{{ route('admin.premium.update', $seller) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="action" value="revoke">
                                <button class="btn btn--ghost btn--sm" type="submit">{{ __('End premium') }}</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.premium.update', $seller) }}" class="revrow__feature">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="action" value="grant">
                                <input class="revrow__days" type="number" name="months" min="1" max="120"
                                    placeholder="{{ __('Months') }}" aria-label="{{ __('Months of premium') }}">
                                <button class="btn btn--ruby btn--sm" type="submit">{{ __('Grant premium') }}</button>
                            </form>
                        @endif

                        @if ($isCertified)
                            <a class="btn btn--ghost btn--sm" href="{{ route('admin.premium.certificate', $seller) }}" wire:navigate>
                                {{ __('View certificate') }}
                            </a>

                            <form method="POST" action="{{ route('admin.premium.update', $seller) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="action" value="decertify">
                                <button class="link" type="submit">{{ __('Withdraw') }}</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.premium.update', $seller) }}" class="revrow__feature">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="action" value="certify">
                                <input class="revrow__reason" type="text" name="note" maxlength="160"
                                    placeholder="{{ __('Note (optional)') }}" aria-label="{{ __('Certificate note') }}">
                                <button class="btn btn--ghost btn--sm" type="submit">{{ __('Issue certificate') }}</button>
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <div class="pager">{{ $sellers->onEachSide(1)->links() }}</div>
    @endif
</x-layouts::admin>
