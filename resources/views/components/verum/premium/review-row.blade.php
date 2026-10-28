@props([
    'listing',
    // 'approval' or 'feature' — the same row, different buttons.
    'mode' => 'approval',
])

@php
    use App\Models\Listing;

    $shot = $listing->imageUrls()[0] ?? null;
@endphp

{{--
    One listing awaiting a decision. Shared by both queues because the
    job is the same — look at the stone, look at who is selling it,
    decide — and only the actions differ.
--}}
<article class="revrow">
    @if ($shot)
        <img class="revrow__shot" src="{{ $shot }}" alt="" loading="lazy" decoding="async">
    @endif

    <div class="revrow__what">
        <a class="revrow__title" href="{{ route('listings.show', $listing) }}" target="_blank" rel="noopener">
            {{ $listing->title }}
        </a>

        <p class="revrow__meta">
            {{ $listing->seller?->name }}
            @if (\App\Support\PremiumStatus::isPremium($listing->seller))
                <span class="chip chip--gold">{{ __('Premium') }}</span>
            @endif
            · {{ $listing->category?->name }}
            @if ($listing->price())
                · {{ $listing->price()->format() }}
            @endif
        </p>

        @if ($mode === 'approval' && $listing->approval_note)
            <p class="revrow__note">{{ $listing->approval_note }}</p>
        @endif

        <time class="revrow__when" datetime="{{ ($mode === 'approval' ? $listing->approval_requested_at : $listing->feature_requested_at)?->toIso8601String() }}">
            {{ __('Asked :when', [
                'when' => ($mode === 'approval' ? $listing->approval_requested_at : $listing->feature_requested_at)?->diffForHumans(),
            ]) }}
        </time>
    </div>

    <div class="revrow__acts">
        @if ($mode === 'approval')
            @if ($listing->approval_state !== Listing::APPROVAL_APPROVED)
                <form method="POST" action="{{ route('admin.listings.review', $listing) }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="action" value="approve">
                    <button class="btn btn--ruby btn--sm" type="submit">{{ __('Approve') }}</button>
                </form>
            @endif

            @if ($listing->approval_state !== Listing::APPROVAL_REJECTED)
                {{-- A reason is required: a rejection a seller cannot act
                     on just gets resubmitted unchanged. --}}
                <form method="POST" action="{{ route('admin.listings.review', $listing) }}" class="revrow__reject">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="action" value="reject">
                    <input class="revrow__reason" type="text" name="reason" required maxlength="200"
                        placeholder="{{ __('Why?') }}" aria-label="{{ __('Reason for rejection') }}">
                    <button class="btn btn--ghost btn--sm" type="submit">{{ __('Reject') }}</button>
                </form>
            @endif
        @else
            @if ($listing->feature_state !== Listing::FEATURE_ACTIVE)
                <form method="POST" action="{{ route('admin.listings.review', $listing) }}" class="revrow__feature">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="action" value="feature">
                    <input class="revrow__days" type="number" name="days" min="1" max="365"
                        placeholder="{{ __('Days') }}" aria-label="{{ __('Days to feature for') }}">
                    <button class="btn btn--ruby btn--sm" type="submit">{{ __('Feature') }}</button>
                </form>

                <form method="POST" action="{{ route('admin.listings.review', $listing) }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="action" value="decline">
                    <button class="btn btn--ghost btn--sm" type="submit">{{ __('Decline') }}</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.listings.review', $listing) }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="action" value="unfeature">
                    <button class="btn btn--ghost btn--sm" type="submit">{{ __('Remove from featured') }}</button>
                </form>
            @endif
        @endif
    </div>
</article>
