@php
    $isEdit = $listing->exists;
@endphp

<x-layouts::seller
    :title="$isEdit ? __('Edit listing') : __('Sell an item')"
    :heading="$isEdit ? $listing->title : __('Sell an item')"
    :lede="$isEdit
        ? __('Update the details, then publish when you are ready.')
        : __('Describe the piece. You can save a draft and finish later.')"
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
                    data-maxlength="120"
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

                <x-verum.form.field name="sku" :label="__('SKU (optional)')" :value="old('sku', $listing->sku)" />

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

        <div class="listingform__actions">
            <button class="btn btn--ruby btn--lg" type="submit">
                {{ $isEdit ? __('Save changes') : __('Save draft') }}
            </button>

            <a class="btn btn--ghost" href="{{ route('seller.listings') }}" wire:navigate>{{ __('Cancel') }}</a>
        </div>
    </form>

    @if ($isEdit && ! $listing->isPublished())
        <x-verum.account.panel :title="__('Ready to go live?')" icon="check" :hint="__('Publishing puts it in front of buyers.')">
            <form method="POST" action="{{ route('seller.listings.publish', $listing) }}">
                @csrf
                <button class="btn btn--ruby btn--block" type="submit">{{ __('Publish listing') }}</button>
            </form>
        </x-verum.account.panel>
    @endif
</x-layouts::seller>
