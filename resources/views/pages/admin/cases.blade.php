@php
    $statuses = [
        'open' => __('Needs attention'),
        'reviewing' => __('Being reviewed'),
        'resolved' => __('Settled'),
        'all' => __('All'),
    ];

    $kinds = [
        'all' => __('Everything'),
        \App\Models\SupportCase::KIND_DISPUTE => __('Disputes'),
        \App\Models\SupportCase::KIND_AUDIT => __('Audits'),
    ];
@endphp

<x-layouts::admin
    :title="__('Disputes and audits')"
    :heading="__('Cases')"
    :lede="__('Buyers reporting a listing, or raising a problem with a seller.')"
>
    <nav class="adminfilters" aria-label="{{ __('Filter by status') }}">
        @foreach ($statuses as $value => $label)
            <a
                href="{{ route('admin.cases', ['status' => $value, 'kind' => $kind]) }}"
                @class(['chip', 'is-on' => $status === $value])
                @if ($status === $value) aria-current="page" @endif
                wire:navigate
            >{{ $label }}@if ($value === 'open' && $openCount > 0) <b>{{ $openCount }}</b>@endif</a>
        @endforeach
    </nav>

    <nav class="adminfilters" aria-label="{{ __('Filter by kind') }}">
        @foreach ($kinds as $value => $label)
            <a
                href="{{ route('admin.cases', ['status' => $status, 'kind' => $value]) }}"
                @class(['chip', 'is-on' => $kind === $value])
                wire:navigate
            >{{ $label }}</a>
        @endforeach
    </nav>

    @if ($cases->isEmpty())
        <x-verum.account.panel :title="__('Nothing here')" icon="shield">
            <x-verum.account.empty
                icon="shield"
                :title="__('Queue is clear')"
                :body="__('No cases match that filter.')"
            />
        </x-verum.account.panel>
    @else
        <div class="tablewrap">
            <table class="atable">
                <thead>
                    <tr>
                        <th scope="col">{{ __('Reference') }}</th>
                        <th scope="col">{{ __('Kind') }}</th>
                        <th scope="col">{{ __('About') }}</th>
                        <th scope="col">{{ __('Raised by') }}</th>
                        <th scope="col">{{ __('Status') }}</th>
                        <th scope="col"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cases as $case)
                        <tr>
                            <th scope="row" data-label="{{ __('Reference') }}">
                                <a class="link mono" href="{{ route('admin.cases.show', $case) }}" wire:navigate>
                                    {{ $case->reference }}
                                </a>
                                <small>{{ $case->created_at->diffForHumans() }}</small>
                            </th>
                            <td data-label="{{ __('Kind') }}">
                                <span class="chip">{{ $case->kindLabel() }}</span>
                            </td>
                            <td data-label="{{ __('About') }}">
                                {{ $case->reasonLabel() }}
                                @if ($case->listing)
                                    <small>{{ $case->listing->title }}</small>
                                @elseif ($case->seller)
                                    <small>{{ $case->seller->name }}</small>
                                @endif
                            </td>
                            <td data-label="{{ __('Raised by') }}">{{ $case->buyer?->name }}</td>
                            <td data-label="{{ __('Status') }}">
                                <span @class(['chip', 'chip--live' => $case->isPending(), 'chip--ok' => ! $case->isPending()])>
                                    {{ $case->statusLabel() }}
                                </span>
                            </td>
                            <td>
                                <a class="btn btn--ghost btn--sm" href="{{ route('admin.cases.show', $case) }}" wire:navigate>
                                    {{ __('Open') }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pager">{{ $cases->onEachSide(1)->links() }}</div>
    @endif
</x-layouts::admin>
