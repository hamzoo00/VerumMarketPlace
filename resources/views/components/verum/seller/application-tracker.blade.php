@props(['application'])

{{-- Progress tracker, mirroring the three stages shown in the demo. --}}
<ol class="sellok__tl">
    @foreach ($application->timeline() as $stage)
        <li @class([
            'is-done' => $stage['state'] === 'done',
            'is-now' => $stage['state'] === 'now',
        ])>
            <span>
                @if ($stage['state'] === 'done')
                    <x-verum.icon name="check" />
                @endif
            </span>
            <b>{{ $stage['label'] }}</b>
            <small>{{ $stage['hint'] }}</small>
        </li>
    @endforeach
</ol>
