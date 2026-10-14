@props([
    'count',
    'max' => 5,
])

<span class="stars" aria-label="{{ __('Rated :n out of :max', ['n' => $count, 'max' => $max]) }}">
    {{ str_repeat('★', (int) $count) }}<span class="stars__off">{{ str_repeat('★', $max - (int) $count) }}</span>
</span>
