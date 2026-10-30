@props(['account'])

@php
    use App\Models\AccountNotice;

    $kind = match (true) {
        $account->isClosing() => 'closing',
        $account->isSuspended() => 'suspended',
        default => null,
    };

    // The words they were actually sent, not today's default.
    $notice = $kind === null ? null : AccountNotice::latestFor($account, $kind);
@endphp

{{--
    Why a seller cannot sell right now, on every page of their workspace.
    The email and the bell say it once; this keeps saying it, because the
    disabled buttons it explains are on every page.
--}}
@if ($kind !== null)
    <div class="standing standing--{{ $kind }}" role="status">
        <x-verum.icon :name="$kind === 'closing' ? 'clock' : 'pause'" />
        <div>
            <b>
                @if ($kind === 'closing')
                    {{ __('Your account is closing on :date.', ['date' => $account->closes_at?->format('j M Y')]) }}
                @else
                    {{ __('Your selling is suspended.') }}
                @endif
            </b>
            <p>{{ $notice?->body ?? __('Your storefront is hidden and no new orders can be placed.') }}</p>
            <p class="standing__more">
                {{ __('You can still finish orders already placed and answer your messages.') }}
            </p>
        </div>
    </div>
@endif
