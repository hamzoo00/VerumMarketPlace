<x-layouts::public
    :title="__('Natural & Certified Gemstones')"
    :description="__('Verum is a marketplace for natural, independently certified gemstones. Disclosed origin, independent grading, vetted sellers, insured worldwide shipping.')"
    body-class="page--home"
>
    <x-verum.home.hero :$heroStones />
    <x-verum.promise-band />
    <x-verum.home.trust />
    <x-verum.home.categories :$gemTypes />
    <x-verum.home.featured :stones="$featured" />
    <x-verum.home.auctions :lots="$closingLots" />
    <x-verum.home.reviews :$reviews />
    <x-verum.split-cta />
</x-layouts::public>
