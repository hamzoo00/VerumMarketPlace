{{--
    Global message host.

    Messages arrive two ways: flashed from the server on page load, or
    pushed from JS via window.verumToast(message, status). Rendered once in
    the layout; nothing else needs to include it.
--}}
@php
    $flash = collect([
        'success' => session('status') ?? session('success'),
        'error' => session('error'),
        'warning' => session('warning'),
        'info' => session('info'),
    ])->filter();
@endphp

<div
    class="toasts"
    id="toasts"
    role="status"
    aria-live="polite"
    aria-atomic="false"
    data-toast-host
    @if ($flash->isNotEmpty())
        data-flash="{{ $flash->map(fn ($message, $status) => ['status' => $status, 'message' => $message])->values()->toJson() }}"
    @endif
></div>
