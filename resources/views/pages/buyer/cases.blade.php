<x-layouts::account-area
    :title="__('Disputes and audits')"
    :heading="__('Your cases')"
    :lede="__('Problems you have raised, and what we decided.')"
    :nav="$buyerNav"
    :account-name="$buyer->name"
    :account-initials="$buyer->initials()"
    :account-meta="__('Buyer account')"
    logout-route="buyer.logout"
>
    @if ($cases->isEmpty())
        <x-verum.account.panel :title="__('Nothing raised')" icon="shield">
            <x-verum.account.empty
                icon="shield"
                :title="__('No cases')"
                :body="__('If a listing looks wrong, or a seller lets you down, tell us from the item page.')"
            />
        </x-verum.account.panel>
    @else
        <div class="caselist">
            @foreach ($cases as $case)
                <x-verum.case.row :$case :href="route('buyer.cases.show', $case)" />
            @endforeach
        </div>

        <div class="pager">{{ $cases->onEachSide(1)->links() }}</div>
    @endif
</x-layouts::account-area>
