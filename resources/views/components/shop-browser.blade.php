<?php

use App\Models\Category;
use App\Models\Listing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The shop catalogue.
 *
 * Filtering, sorting and paging happen over the wire, so choosing a filter
 * no longer reloads the page. Every control is still bound to the query
 * string, so a filtered view stays shareable and crawlable — the page is
 * server-rendered on first request exactly as before.
 */
new class extends Component
{
    #[Url(except: '')]
    public string $kind = '';

    #[Url(except: '')]
    public string $category = '';

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: false)]
    public bool $untreated = false;

    #[Url(except: 'newest')]
    public string $sort = 'newest';

    /** Rows shown so far. "Load more" raises this rather than paging away. */
    public int $perPage = self::STEP;

    public const STEP = 12;

    /**
     * A hard ceiling on one page.
     *
     * `perPage` arrives from the browser, so it is attacker-controlled: an
     * unbounded value would ask Postgres for the whole table and render
     * every card. Clicking "load more" past this is not a real journey —
     * the filters are there for that.
     */
    public const MAX_ROWS = 96;

    /** The only orderings the UI offers, and the only ones accepted. */
    public const SORTS = ['newest', 'price-asc', 'price-desc', 'carat-desc'];

    /**
     * URL properties are attacker- and typo-supplied, so anything the UI
     * could not have produced is dropped on the way in. Without this a
     * junk `?kind=moonrock` renders an empty shop at 200 instead of
     * saying the URL is wrong.
     */
    public function mount(): void
    {
        if ($this->kind !== '' && ! array_key_exists($this->kind, Category::kinds())) {
            abort(404);
        }

        if (! in_array($this->sort, self::SORTS, true)) {
            $this->sort = 'newest';
        }

        if ($this->type !== '' && ! in_array($this->type, [Listing::TYPE_FIXED, Listing::TYPE_AUCTION], true)) {
            $this->type = '';
        }
    }

    /** Choosing a branch invalidates a family from the other branch. */
    public function updatedKind(): void
    {
        $this->category = '';
        $this->resetPaging();
    }

    public function updatedCategory(): void
    {
        $this->resetPaging();
    }

    public function updatedType(): void
    {
        $this->resetPaging();
    }

    public function updatedUntreated(): void
    {
        $this->resetPaging();
    }

    public function updatedSort(): void
    {
        $this->resetPaging();
    }

    public function loadMore(): void
    {
        $this->perPage = min($this->perPage + self::STEP, self::MAX_ROWS);
    }

    public function clearAll(): void
    {
        $this->kind = '';
        $this->category = '';
        $this->type = '';
        $this->untreated = false;
        $this->sort = 'newest';
        $this->resetPaging();
    }

    /**
     * A "load more" list must not grow without bound: each click re-runs
     * this query, so the page is capped and the pager takes over after it.
     */
    private function resetPaging(): void
    {
        $this->perPage = self::STEP;
    }

    #[Computed]
    public function activeCategory(): ?Category
    {
        return $this->category === ''
            ? null
            : Category::where('slug', $this->category)->first();
    }

    /** A chosen family implies its branch, so the two stay consistent. */
    #[Computed]
    public function activeKind(): ?string
    {
        return $this->activeCategory()?->kind ?? ($this->kind ?: null);
    }

    /** @return LengthAwarePaginator<int, Listing> */
    #[Computed]
    public function listings(): LengthAwarePaginator
    {
        $category = $this->activeCategory();
        $kind = $this->activeKind();

        return Listing::query()
            ->published()
            // `media` is eager-loaded: every card asks for its cover
            // photograph, which was one query per card before.
            ->with(['category', 'seller', 'media'])
            ->when($category, fn ($q, Category $c) => $q->whereHas(
                'category',
                fn ($inner) => $inner->where('id', $c->id)
                    ->orWhereLike('path', $c->path.'/%'),
            ))
            ->when($category === null && $kind !== null,
                fn ($q) => $q->whereRelation('category', 'kind', $kind))
            ->when($this->untreated, fn ($q) => $q->where('untreated', true))
            ->when($this->type !== '', fn ($q) => $q->where('listing_type', $this->type))
            ->tap(fn ($q) => match ($this->sort) {
                'price-asc' => $q->orderByRaw('coalesce(price_cents, current_bid_cents, start_price_cents) asc'),
                'price-desc' => $q->orderByRaw('coalesce(price_cents, current_bid_cents, start_price_cents) desc'),
                'carat-desc' => $q->orderByDesc('carat'),
                default => $q->latest('published_at'),
            })
            // A stable tiebreaker: without one, rows with equal sort keys
            // can swap between pages and a card appears twice or not at all.
            ->orderByDesc('id')
            // Clamped on read as well as on write: the property round-trips
            // through the browser, so the setter alone cannot be trusted.
            ->paginate(
                max(self::STEP, min($this->perPage, self::MAX_ROWS)),
                ['*'],
                'page',
                1,
            );
    }

    /**
     * Top-level families that actually have something listed.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function families(): Collection
    {
        $kind = $this->activeKind();

        return Category::query()
            ->roots()
            ->active()
            ->when($kind, fn ($q, string $k) => $q->ofKind($k))
            ->where(fn ($q) => $q
                ->whereHas('listings', fn ($i) => $i->published())
                ->orWhereHas('children.listings', fn ($i) => $i->published()))
            ->orderBy('name')
            ->get();
    }
};
?>

<div class="shop__live">
    <x-verum.shop.filters
        :families="$this->families"
        :active-category="$this->activeCategory"
        :active-kind="$this->activeKind"
        :untreated-only="$untreated"
        :active-sort="$sort"
        :active-type="$type"
        live
    />

    <div class="filters__scrim" data-close-filters></div>

    <div class="results">
        <x-verum.shop.toolbar
            :total="$this->listings->total()"
            :active-sort="$sort"
            :active-category="$this->activeCategory"
            :untreated-only="$untreated"
            :active-type="$type"
            live
        />

        {{--
            wire:key on every card so Livewire moves existing DOM instead of
            rewriting it: without it a re-sort re-creates each card and the
            images flash.
        --}}
        <div
            class="cards cards--shop"
            data-live-grid
            data-live-kind="{{ $this->activeKind ?? '' }}"
            data-live-category="{{ $this->activeCategory?->slug ?? '' }}"
            data-live-type="{{ $type }}"
            data-live-untreated="{{ $untreated ? '1' : '0' }}"
        >
            @foreach ($this->listings as $index => $listing)
                <x-verum.listing.card :$listing :$index :key="'l-'.$listing->id" />
            @endforeach
        </div>

        @if ($this->listings->isEmpty())
            <div class="empty" data-live-empty>
                <x-verum.icon name="loupe" class="empty__ic" />
                <h3>{{ __('Nothing matches those filters.') }}</h3>
                <p>{{ __('Loosen a filter, or clear them all to see everything.') }}</p>
                <button class="btn btn--ruby btn--sm" type="button" wire:click="clearAll">
                    {{ __('Clear filters') }}
                </button>
            </div>
        @elseif ($this->listings->hasMorePages())
            <div class="loadmore">
                <button
                    class="btn btn--ghost"
                    type="button"
                    wire:click="loadMore"
                    wire:loading.attr="disabled"
                    wire:target="loadMore"
                >
                    <span wire:loading.remove wire:target="loadMore">
                        {{ __('Load more') }}
                        <small>{{ __(':shown of :total', [
                            'shown' => $this->listings->count(),
                            'total' => $this->listings->total(),
                        ]) }}</small>
                    </span>
                    <span wire:loading wire:target="loadMore">{{ __('Loading…') }}</span>
                </button>
            </div>
        @endif
    </div>
</div>
