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
        @php
            $action = route('admin.listings.review', $listing);
            $replace = ['listing' => $listing->title];
        @endphp

        {{-- Every decision tells the seller; the dialog shows what. --}}
        @if ($mode === 'approval')
            @if ($listing->approval_state !== Listing::APPROVAL_APPROVED)
                <x-verum.admin.notice-form
                    :$action
                    method="PUT"
                    kind="listing_approved"
                    :$replace
                    :title="__('Approve “:listing”', $replace)"
                    :lede="__('It goes live on the site at once.')"
                    :confirm="__('Approve')"
                >
                    <input type="hidden" name="action" value="approve">
                    <button class="btn btn--ruby btn--sm" type="submit">{{ __('Approve') }}</button>
                </x-verum.admin.notice-form>
            @endif

            @if ($listing->approval_state !== Listing::APPROVAL_REJECTED)
                {{-- Whatever is sent becomes the reason on the seller's
                     inventory, so they know what to fix. --}}
                <x-verum.admin.notice-form
                    :$action
                    method="PUT"
                    kind="listing_rejected"
                    :$replace
                    :title="__('Reject “:listing”', $replace)"
                    :lede="__('The seller sees this reason on their inventory, so say what to fix.')"
                    :confirm="__('Reject')"
                    danger
                >
                    <input type="hidden" name="action" value="reject">
                    <button class="btn btn--ghost btn--sm" type="submit">{{ __('Reject') }}</button>
                </x-verum.admin.notice-form>
            @endif
        @else
            @if ($listing->feature_state !== Listing::FEATURE_ACTIVE)
                <x-verum.admin.notice-form
                    :$action
                    method="PUT"
                    kind="feature_granted"
                    :$replace
                    :title="__('Feature “:listing”', $replace)"
                    :lede="__('It joins the featured section on the home page.')"
                    :confirm="__('Feature')"
                    class="revrow__feature"
                >
                    <input type="hidden" name="action" value="feature">
                    <input class="revrow__days" type="number" name="days" min="1" max="365"
                        placeholder="{{ __('Days') }}" aria-label="{{ __('Days to feature for') }}">
                    <button class="btn btn--ruby btn--sm" type="submit">{{ __('Feature') }}</button>
                </x-verum.admin.notice-form>

                <x-verum.admin.notice-form
                    :$action
                    method="PUT"
                    kind="feature_declined"
                    :$replace
                    :title="__('Decline featuring “:listing”', $replace)"
                    :confirm="__('Decline')"
                >
                    <input type="hidden" name="action" value="decline">
                    <button class="btn btn--ghost btn--sm" type="submit">{{ __('Decline') }}</button>
                </x-verum.admin.notice-form>
            @else
                <x-verum.admin.notice-form
                    :$action
                    method="PUT"
                    kind="feature_ended"
                    :$replace
                    :title="__('Remove “:listing” from featured', $replace)"
                    :confirm="__('Remove')"
                >
                    <input type="hidden" name="action" value="unfeature">
                    <button class="btn btn--ghost btn--sm" type="submit">{{ __('Remove from featured') }}</button>
                </x-verum.admin.notice-form>
            @endif
        @endif
    </div>
</article>
