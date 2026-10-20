<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * A node in the listing taxonomy.
 *
 * Adjacency list with a materialised `path` ("1/14/87"), so breadcrumbs and
 * subtree queries are one read each.
 *
 * @property int|null $parent_id
 * @property string $path
 */
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    public const KIND_STONE = 'stone';

    public const KIND_JEWELLERY = 'jewellery';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<self, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<self, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }

    /** @return HasMany<Listing, $this> */
    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    /** @param  Builder<self>  $query */
    public function scopeRoots(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    /** @param  Builder<self>  $query */
    public function scopeOfKind(Builder $query, string $kind): void
    {
        $query->where('kind', $kind);
    }

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /** Only a leaf may hold a listing; a branch is for navigation. */
    public function isSelectable(): bool
    {
        return ! $this->hasChildren();
    }

    /**
     * The id of this node's root, read straight from the materialised path.
     *
     * Cheap on purpose: a card only wants to know which family a stone
     * belongs to, and resolving that through `ancestors()` cost one query
     * per card on a grid of twelve.
     */
    public function rootId(): int
    {
        $first = (int) explode('/', (string) $this->path)[0];

        // An unsaved or path-less node is its own root.
        return $first !== 0 ? $first : $this->id;
    }

    /**
     * Root id → root name, for the whole tree.
     *
     * Request-scoped: a grid of cards all want their family name, and the
     * roots are few (two branches, a few dozen families) and change only
     * when the taxonomy is reseeded.
     *
     * @return array<int, string>
     */
    public static function rootNames(): array
    {
        /** @var array<int, string>|null $cache */
        static $cache = null;

        if ($cache === null) {
            /** @var array<int, string> $cache */
            $cache = self::query()
                ->whereNull('parent_id')
                ->pluck('name', 'id')
                ->all();
        }

        return $cache;
    }

    /** The family this node belongs to, e.g. "Sapphire" for a sub-type. */
    public function rootName(): ?string
    {
        return self::rootNames()[$this->rootId()] ?? $this->name;
    }

    /**
     * This node's ancestors, root first.
     *
     * `path` ends with this node's own id, so it is excluded here.
     *
     * @return Collection<int, self>
     */
    public function ancestors(): Collection
    {
        $ids = array_filter(
            explode('/', (string) $this->path),
            fn (string $id): bool => (int) $id !== $this->id,
        );

        if ($ids === []) {
            return new Collection;
        }

        return self::query()
            ->whereIn('id', $ids)
            ->orderBy('depth')
            ->get();
    }

    /**
     * The full trail, as "Zircon › Blue Zircon".
     *
     * @return Collection<int, self>
     */
    public function trail(): Collection
    {
        return $this->ancestors()->push($this);
    }

    public function breadcrumb(string $separator = ' › '): string
    {
        return $this->trail()
            ->map(fn (self $c): string => $c->name)
            ->implode($separator);
    }

    /** Recomputes `path` and `depth` from the parent chain. */
    public function refreshPath(): void
    {
        $parent = $this->parent;

        $path = $parent === null
            ? (string) $this->id
            : trim($parent->path.'/'.$this->id, '/');

        $depth = $parent === null ? 0 : $parent->depth + 1;

        if ($this->path !== $path || $this->depth !== $depth) {
            $this->forceFill(['path' => $path, 'depth' => $depth])->saveQuietly();
        }
    }
}
