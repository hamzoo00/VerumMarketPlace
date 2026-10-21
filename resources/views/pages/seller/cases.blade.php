<x-layouts::seller
    :account="$account"
    :title="__('Cases')"
    :heading="__('Cases')"
    :lede="__('Disputes and audits raised about your store, and how they were settled.')"
>
    @if ($cases->isEmpty())
        <x-verum.account.panel :title="__('Nothing raised')" icon="shield">
            <x-verum.account.empty
                icon="shield"
                :title="__('No cases')"
                :body="__('Nothing has been raised about your store. Keep it that way.')"
            />
        </x-verum.account.panel>
    @else
        <div class="caselist">
            @foreach ($cases as $case)
                <x-verum.case.row :$case />
            @endforeach
        </div>

        <div class="pager">{{ $cases->onEachSide(1)->links() }}</div>
    @endif
</x-layouts::seller>
