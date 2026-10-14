<x-layouts::public
    :title="__('Home')"
    :description="__('Buy and sell certified gemstones and fine jewellery at auction.')"
>
    <div class="flex flex-col gap-6">
        <flux:heading size="xl">{{ config('app.name') }}</flux:heading>
        <flux:text>
            {{ __('Marketplace homepage. Featured auctions and categories land here.') }}
        </flux:text>
    </div>
</x-layouts::public>
