@php
    use App\Support\GemSpec;

    $isEdit = $listing->exists;
    // A draft can be saved or sent live from the same form; a listing
    // already live is simply saved.
    $canPublish = ! $listing->isPublished();
    $publishLabel = \App\Support\PlatformSettings::approvalRequired()
        && $listing->approval_state !== \App\Models\Listing::APPROVAL_APPROVED
            ? __('Submit for approval')
            : __('Publish');
@endphp

<x-layouts::seller
    :title="$isEdit ? __('Edit listing') : __('Sell an item')"
    :heading="$isEdit ? $listing->title : __('Sell an item')"
    :lede="$isEdit
        ? __('Update the details, then publish when you are ready.')
        : __('Describe the piece, then save it as a draft or publish it straight away.')"
    :account="$account"
>
    <form method="POST" action="{{ $action }}" class="cform listingform" data-validate>
        @csrf
        @if ($method !== 'POST')
            @method($method)
        @endif

        <fieldset class="fgrp">
            <legend class="lbl">{{ __('What are you listing?') }}</legend>

            <x-verum.seller.category-picker :$kinds :selected="$listing->category" />

            <div class="cform__grid">
                <x-verum.form.field
                    name="title"
                    :label="__('Title')"
                    :value="old('title', $listing->title)"
                    :hint="__('What a buyer searches for, e.g. 2.14 ct unheated Mogok ruby')"
                    minlength="3"
                    maxlength="120"
                    :pattern="GemSpec::pattern('title')"
                    :data-pattern-message="__('Describe the piece in words, e.g. 2.14 ct unheated Mogok ruby.')"
                    required
                    full
                />

                <x-verum.form.select
                    name="listing_type"
                    :label="__('Listing type')"
                    :options="\App\Models\Listing::typeLabels()"
                    :selected="old('listing_type', $listing->listing_type)"
                    required
                />

                <x-verum.form.field
                    name="sku"
                    :label="__('SKU (optional)')"
                    :value="old('sku', $listing->sku)"
                    maxlength="60"
                    :pattern="GemSpec::pattern('sku')"
                    :data-pattern-message="__('Letters, numbers, dashes and dots only.')"
                />

                <x-verum.form.textarea
                    name="description"
                    :label="__('Description')"
                    :rows="5"
                    :value="$listing->description"
                    data-maxlength="5000"
                />
            </div>
        </fieldset>

        @if ($isEdit)
            <fieldset class="fgrp">
                <legend class="lbl">{{ __('Photos and video') }}</legend>
                <p class="fgrp__hint">
                    {{ __('Buyers decide on the photographs. Shoot in daylight against a plain ground.') }}
                </p>

                <x-verum.form.media-upload :$listing kind="images" />
                <x-verum.form.media-upload :$listing kind="videos" />
            </fieldset>
        @endif

        <x-verum.seller.listing.pricing :$listing />
        <x-verum.seller.listing.offers :$listing />
        <x-verum.seller.listing.shipping :$listing />
        <x-verum.seller.listing.attributes :$listing />
        <x-verum.seller.listing.certification :$listing />

        {{-- Both buttons validate and save the same form; `intent` says
             whether it then goes in front of buyers. The label follows the
             approval switch live, so an admin turning it off changes
             "Submit for approval" to "Publish" while the seller is here. --}}
        <div class="listingform__actions" data-live-region="listing-publish" data-settings-section="listings">
            @if ($canPublish)
                <button class="btn btn--ruby btn--lg" type="submit" name="intent" value="publish">
                    <x-verum.icon name="send" />{{ $publishLabel }}
                </button>
                <button class="btn btn--ghost btn--lg" type="submit" name="intent" value="draft">
                    {{ $isEdit ? __('Save draft') : __('Save as draft') }}
                </button>
            @else
                <button class="btn btn--ruby btn--lg" type="submit" name="intent" value="save">
                    {{ __('Save changes') }}
                </button>
            @endif

            <a class="link" href="{{ route('seller.listings') }}" wire:navigate>{{ __('Cancel') }}</a>
        </div>

        @if ($canPublish && ! $isEdit)
            <p class="listingform__note">
                <x-verum.icon name="info" />
                {{ __('Photographs are added on the next page, once the listing exists.') }}
            </p>
        @endif
    </form>
</x-layouts::seller>
