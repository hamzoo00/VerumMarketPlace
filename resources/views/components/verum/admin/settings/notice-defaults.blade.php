@props([
    // Kind => definition, from NoticeCatalogue::kinds($category).
    'notices',
])

@php
    use App\Support\NoticeCatalogue;
    use App\Support\NoticeTemplates;

    // Escaped first, then placeholders marked up — never the other way.
    $mark = fn (string $text): string => preg_replace('/:([a-z]+)/', '<code>:$1</code>', e($text));
@endphp

{{--
    The standard message behind every action in this section — what a
    seller or buyer receives when the admin acting writes nothing of
    their own. Each can be reworded here and reset to the wording in
    config/verum_notices.php, which always stays the fallback.

    An edit changes what is sent from then on, never what was already
    sent: account_notices keeps every message word for word.
--}}
<x-verum.account.panel
    :title="__('Messages sent from here')"
    icon="mail"
    :hint="__('Sent whenever you act without writing your own message. Reword any of them; reset to go back to the standard wording.')"
>
    <div class="noticedefs">
        @foreach ($notices as $kind => $definition)
            @php
                $edited = NoticeTemplates::for($kind);
                $placeholders = NoticeTemplates::placeholders($kind);
            @endphp

            <article class="noticedef" id="notice-{{ $kind }}">
                <header class="noticedef__head">
                    <b>{{ NoticeCatalogue::label($kind) }}</b>
                    <span class="pill pill--muted">{{ __('To: :who', ['who' => __($definition['to'])]) }}</span>
                    @if ($definition['counts_as_warning'] ?? false)
                        <span class="pill pill--hold">{{ __('Counts as a warning') }}</span>
                    @endif
                    @if ($definition['record_only'] ?? false)
                        <span class="pill pill--muted">{{ __('Recorded, not sent') }}</span>
                    @endif
                    @if ($edited)
                        <span class="pill pill--ok">{{ __('Edited') }}</span>
                        <small class="noticedef__who">
                            {{ __('by :who, :when', [
                                'who' => $edited['editor'] ?? __('an admin'),
                                'when' => $edited['updated_at'] ? \Illuminate\Support\Carbon::parse($edited['updated_at'])->diffForHumans() : '',
                            ]) }}
                        </small>
                    @endif
                </header>

                <p class="noticedef__subject">
                    <x-verum.icon name="mail" />{!! $mark(NoticeCatalogue::subjectTemplate($kind)) !!}
                </p>
                <blockquote class="noticedef__body">{!! $mark(NoticeCatalogue::bodyTemplate($kind)) !!}</blockquote>

                <details class="noticedef__edit">
                    <summary class="noticedef__toggle">
                        <x-verum.icon name="edit" />{{ __('Edit message') }}
                    </summary>

                    <form
                        method="POST"
                        action="{{ route('admin.notices.update', $kind) }}"
                        class="noticedef__form"
                        data-live-form
                    >
                        @csrf
                        @method('PUT')

                        <label for="nsubj-{{ $kind }}">{{ __('Subject') }}</label>
                        <input
                            id="nsubj-{{ $kind }}"
                            type="text"
                            name="subject"
                            value="{{ NoticeCatalogue::subjectTemplate($kind) }}"
                            maxlength="160"
                            required
                        >

                        <label for="nbody-{{ $kind }}">{{ __('Message') }}</label>
                        <textarea
                            id="nbody-{{ $kind }}"
                            name="body"
                            rows="5"
                            maxlength="{{ \App\Actions\Notices\IssueNotice::MAX_LENGTH }}"
                            required
                        >{{ NoticeCatalogue::bodyTemplate($kind) }}</textarea>

                        <p class="noticedef__vars">
                            {{ __('Filled in when it is sent:') }}
                            @foreach ($placeholders as $placeholder)
                                <code>:{{ $placeholder }}</code>
                            @endforeach
                        </p>

                        @if ($edited)
                            <p class="noticedef__orig">
                                <b>{{ __('Standard wording') }}</b>
                                {{ NoticeCatalogue::originalBody($kind) }}
                            </p>
                        @endif

                        <div class="noticedef__actions">
                            <button class="btn btn--ruby btn--sm" type="submit">{{ __('Save message') }}</button>
                        </div>
                    </form>

                    @if ($edited)
                        <form
                            method="POST"
                            action="{{ route('admin.notices.reset', $kind) }}"
                            class="noticedef__reset"
                            data-live-form
                            data-confirm="{{ __('Go back to the standard wording for “:label”?', ['label' => NoticeCatalogue::label($kind)]) }}"
                        >
                            @csrf
                            @method('DELETE')
                            <button class="link" type="submit">{{ __('Reset to standard wording') }}</button>
                        </form>
                    @endif
                </details>
            </article>
        @endforeach
    </div>
</x-verum.account.panel>
