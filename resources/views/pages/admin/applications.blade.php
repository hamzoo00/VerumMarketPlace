@php
    $filters = [
        'pending' => __('Pending'),
        'approved' => __('Approved'),
        'rejected' => __('Rejected'),
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
            <table class="otable">
                <thead>
                    <tr>
                        <th scope="col">{{ __('Reference') }}</th>
                        <th scope="col">{{ __('Business') }}</th>
                        <th scope="col">{{ __('Applicant') }}</th>
                        <th scope="col">{{ __('Submitted') }}</th>
                        <th scope="col">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($applications as $application)
                        <tr>
                            <th scope="row">
                                <a class="link link--mono" href="{{ route('admin.applications.show', $application) }}" wire:navigate>
                                    {{ $application->reference }}
                                </a>
                            </th>
                            <td>{{ $application->business_name }}<br><small>{{ $application->country }}</small></td>
                            <td>{{ $application->sellerAccount?->email }}</td>
                            <td>{{ $application->submitted_at?->format('d/m/Y') }}</td>
                            <td><x-verum.admin.status-pill :status="$application->status" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pager">{{ $applications->onEachSide(1)->links() }}</div>
    @endif
</x-layouts::admin>
