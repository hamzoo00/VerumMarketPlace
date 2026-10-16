<x-layouts::public
    :title="__('Contact')"
    :description="__('Questions about a stone, a report or a shipment? We reply within a day.')"
>
    <x-verum.page-head
        :title="__('Get in')"
        :emphasis="__('touch')"
        :lede="__('Questions about a stone, a report or a shipment. We reply within a day.')"
        :crumbs="[__('Contact') => null]"
    />

    <div class="wrap contact">
        <div class="contact__grid">
            <form class="cform" data-validate data-reveal onsubmit="return false">
                <div class="fgrid">
                    <p class="field">
                        <label class="lbl" for="cName">{{ __('Your name') }}</label>
                        <input id="cName" name="name" type="text" autocomplete="name" required>
                    </p>
                    <p class="field">
                        <label class="lbl" for="cEmail">{{ __('Email') }}</label>
                        <input id="cEmail" name="email" type="email" autocomplete="email" required>
                    </p>
                </div>
                <p class="field">
                    <label class="lbl" for="cSubject">{{ __('Subject') }}</label>
                    <input id="cSubject" name="subject" type="text" required>
                </p>
                <p class="field">
                    <label class="lbl" for="cBody">{{ __('Message') }}</label>
                    <textarea id="cBody" name="message" rows="6" required></textarea>
                </p>

                @if (config('verum.demo_mode'))
                    <p class="cform__note">
                        <x-verum.icon name="info" />{{ __('This form is not connected yet. Use email or phone below.') }}
                    </p>
                @endif

                <button class="btn btn--ruby btn--lg" type="submit" @disabled(config('verum.demo_mode'))>
                    {{ __('Send message') }}
                </button>
            </form>

            <aside class="cside" data-reveal style="--d:120">
                <x-verum.contact-methods />
            </aside>
        </div>
    </div>
</x-layouts::public>
