@props([
    'application',
    'blocked' => false,
])

@php
    $id = 'act-'.$application->id;
    $pending = $application->isPending();
@endphp

{{--
    Three-dot actions menu. Each action is its own POST form so it works
    without JavaScript; the menu only controls visibility.
--}}
<div class="amenu" data-menu>
    <button
        class="amenu__dots"
        type="button"
        id="{{ $id }}-btn"
        aria-haspopup="true"
        aria-expanded="false"
        aria-controls="{{ $id }}"
        aria-label="{{ __('Actions for :reference', ['reference' => $application->reference]) }}"
        data-menu-toggle
    >
        <span aria-hidden="true">&vellip;</span>
    </button>

    <div class="amenu__pop" id="{{ $id }}" role="menu" aria-labelledby="{{ $id }}-btn" hidden>
        @if ($pending)
            <form method="POST" action="{{ route('admin.applications.approve', $application) }}" role="none">
                @csrf
                <button class="amenu__item amenu__item--ok" type="submit" role="menuitem">
                    <x-verum.icon name="check" />{{ __('Approve') }}
                </button>
            </form>

            <a class="amenu__item" role="menuitem" href="{{ route('admin.applications.show', $application) }}#reject" wire:navigate>
                <x-verum.icon name="close" />{{ __('Reject') }}
            </a>

            <a class="amenu__item" role="menuitem" href="{{ route('admin.applications.show', $application) }}#hold" wire:navigate>
                <x-verum.icon name="clock" />{{ __('Put on hold') }}
            </a>

            <hr class="amenu__sep">
        @endif

        @if ($blocked)
            <form method="POST" action="{{ route('admin.applications.unblock', $application) }}" role="none">
                @csrf
                <button class="amenu__item" type="submit" role="menuitem">
                    <x-verum.icon name="shield" />{{ __('Lift email block') }}
                </button>
            </form>
        @endif

        <form
            method="POST"
            action="{{ $application->isArchived()
                ? route('admin.applications.unarchive', $application)
                : route('admin.applications.archive', $application) }}"
            role="none"
        >
            @csrf
            <button class="amenu__item" type="submit" role="menuitem">
                <x-verum.icon name="box" />
                {{ $application->isArchived() ? __('Restore to queue') : __('Archive') }}
            </button>
        </form>

        <form
            method="POST"
            action="{{ route('admin.applications.destroy', $application) }}"
            role="none"
            data-confirm="{{ __('Delete :reference permanently? This cannot be undone.', ['reference' => $application->reference]) }}"
        >
            @csrf
            @method('DELETE')
            <button class="amenu__item amenu__item--bad" type="submit" role="menuitem">
                <x-verum.icon name="trash" />{{ __('Delete') }}
            </button>
        </form>
    </div>
</div>
