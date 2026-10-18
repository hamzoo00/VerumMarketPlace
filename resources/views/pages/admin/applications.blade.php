@php
    $filters = [
        'pending' => __('Pending'),
        'approved' => __('Approved'),
        'rejected' => __('Rejected'),
        'archived' => __('Archived'),
    ];
@endphp

<x-layouts::admin
    :title="__('Seller applications')"
    :heading="__('Applications')"
    :lede="__('Every seller is vetted by hand before their first listing goes live.')"
>
    <nav class="adminfilters" aria-label="{{ __('Filter by status') }}">
        @foreach ($filters as $value => $label)
            <a
                href="{{ route('admin.applications', ['status' => $value]) }}"
                @class(['chip', 'is-on' => $status === $value])
                @if ($status === $value) aria-current="page" @endif
                wire:navigate
            >{{ $label }}</a>
        @endforeach
    </nav>

    @if ($applications->isEmpty())
        <x-verum.account.panel :title="__('Nothing here')">
            <x-verum.account.empty
                icon="check"
                :title="__('No :status applications', ['status' => mb_strtolower($filters[$status] ?? $status)])"
            />
        </x-verum.account.panel>
    @else
        <div class="tablewrap">
            <table class="atable">
                <thead>
                    <tr>
                        <th scope="col">{{ __('Reference') }}</th>
                        <th scope="col">{{ __('Business') }}</th>
                        <th scope="col">{{ __('Applicant') }}</th>
                        <th scope="col">{{ __('Submitted') }}</th>
                        <th scope="col">{{ __('Status') }}</th>
                        <th scope="col"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($applications as $application)
                        @php($email = mb_strtolower((string) $application->sellerAccount?->email))
                        <tr>
                            <th scope="row" data-label="{{ __('Reference') }}">
                                <a class="link link--mono" href="{{ route('admin.applications.show', $application) }}" wire:navigate>
                                    {{ $application->reference }}
                                </a>
                            </th>
                            <td data-label="{{ __('Business') }}">
                                <span class="atable__strong">{{ $application->business_name }}</span>
                                <small>{{ $application->country }}</small>
                            </td>
                            <td data-label="{{ __('Applicant') }}">
                                {{ $application->sellerAccount?->email }}
                                @if (isset($blockedEmails[$email]))
                                    <span class="pill pill--bad">{{ __('Blocked') }}</span>
                                @endif
                            </td>
                            <td data-label="{{ __('Submitted') }}">
                                <time datetime="{{ $application->submitted_at?->toIso8601String() }}">
                                    {{ $application->submitted_at?->format('d/m/Y') }}
                                </time>
                            </td>
                            <td data-label="{{ __('Status') }}">
                                <x-verum.admin.status-pill :status="$application->status" :archived="$application->isArchived()" />
                            </td>
                            <td class="atable__actions">
                                <x-verum.admin.actions-menu
                                    :application="$application"
                                    :blocked="isset($blockedEmails[$email])"
                                />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pager">{{ $applications->onEachSide(1)->links() }}</div>
    @endif
</x-layouts::admin>
