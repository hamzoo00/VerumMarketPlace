@php
    $contact = config('verum.contact');
@endphp

<ul class="cmethods">
    <li>
        <span class="cmethods__ic"><x-verum.icon name="mail" /></span>
        <div>
            <b>{{ __('Email') }}</b>
            <a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a>
            <small>{{ __('We reply within one business day') }}</small>
        </div>
    </li>
    <li>
        <span class="cmethods__ic"><x-verum.icon name="phone" /></span>
        <div>
            <b>{{ __('Phone') }}</b>
            <a href="tel:{{ preg_replace('/\s+/', '', $contact['phone']) }}">{{ $contact['phone'] }}</a>
            <small>{{ __('Mon–Sat, 10:00–19:00 PKT') }}</small>
        </div>
    </li>
    <li>
        <span class="cmethods__ic"><x-verum.icon name="pin" /></span>
        <div>
            <b>{{ __('Counter') }}</b>
            <span>{{ $contact['address'] }}</span>
            <small>{{ __('Visits by appointment') }}</small>
        </div>
    </li>
</ul>
