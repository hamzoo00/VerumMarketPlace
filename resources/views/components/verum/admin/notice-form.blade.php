@props([
    'action',
    'method' => 'POST',
    // A key from config/verum_notices.php: what the recipient is sent.
    'kind',
    // Placeholders for that kind's standard message.
    'replace' => [],
    // What the dialog says is about to happen, e.g. "Suspend Zarar".
    'title',
    // An extra line above the message, e.g. "This will be warning 3."
    'lede' => null,
    'confirm' => null,
    // Destructive actions get the red button.
    'danger' => false,
])

{{--
    An admin action that tells a seller or buyer something.

    With JavaScript the submit opens the shared notice dialog, where the
    admin can write their own message, and the form is then sent without
    a reload. Without it the form simply posts and the standard message
    is used — the action never depends on the dialog.
--}}
<form
    method="POST"
    action="{{ $action }}"
    data-live-form
    data-notice
    data-notice-title="{{ $title }}"
    data-notice-default="{{ \App\Support\NoticeCatalogue::defaultBody($kind, $replace) }}"
    data-notice-confirm="{{ $confirm ?? __('Send') }}"
    @if ($lede) data-notice-lede="{{ $lede }}" @endif
    @if ($danger) data-notice-danger @endif
    {{ $attributes }}
>
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif
    <input type="hidden" name="message" value="">

    {{ $slot }}
</form>
