@props(['listing'])

@php($id = 'lst-'.$listing->id)

<div class="amenu" data-menu>
    <button
        class="amenu__dots"
        type="button"
        id="{{ $id }}-btn"
        aria-haspopup="true"
        aria-expanded="false"
        aria-controls="{{ $id }}"
        aria-label="{{ __('Actions for :title', ['title' => $listing->title]) }}"
        data-menu-toggle
    >
        <span aria-hidden="true">&vellip;</span>
    </button>

    <div class="amenu__pop" id="{{ $id }}" role="menu" aria-labelledby="{{ $id }}-btn" hidden>
        <a class="amenu__item" role="menuitem" href="{{ route('seller.listings.edit', $listing) }}" wire:navigate>
            <x-verum.icon name="file" />{{ __('Edit') }}
        </a>

        @if (! $listing->isPublished())
            <form method="POST" action="{{ route('seller.listings.publish', $listing) }}" role="none">
                @csrf
                <button class="amenu__item amenu__item--ok" type="submit" role="menuitem">
                    <x-verum.icon name="check" />{{ __('Publish') }}
                </button>
            </form>
        @endif

        <hr class="amenu__sep">

        <form
            method="POST"
            action="{{ route('seller.listings.destroy', $listing) }}"
            role="none"
            data-confirm="{{ __('Remove :title? You can ask support to restore it.', ['title' => $listing->title]) }}"
        >
            @csrf
            @method('DELETE')
            <button class="amenu__item amenu__item--bad" type="submit" role="menuitem">
                <x-verum.icon name="trash" />{{ __('Remove') }}
            </button>
        </form>
    </div>
</div>
