<x-layouts::app :title="__('Dashboard')">
    <div class="flex flex-col gap-6">
        <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>
        <flux:text>
            {{ __('Your bids, listings, and orders will appear here.') }}
        </flux:text>
    </div>
</x-layouts::app>
