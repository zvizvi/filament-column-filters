<?php

namespace Zvizvi\FilamentColumnTools\Filters;

use Closure;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

abstract class ColumnFilter
{
    protected ?string $filterName = null;

    protected ?string $syncWithFilter = null;

    /**
     * Maps this filter's field keys (e.g. "value", "from", "until") to the
     * state keys of the synced table filter.
     *
     * @var array<string, string>
     */
    protected array $syncFieldMap = [];

    protected ?string $attribute = null;

    protected ?string $label = null;

    protected ?Closure $applyUsing = null;

    public static function search(): SearchColumnFilter
    {
        return new SearchColumnFilter;
    }

    public static function date(): DateColumnFilter
    {
        return new DateColumnFilter;
    }

    public static function select(): SelectColumnFilter
    {
        return new SelectColumnFilter;
    }

    abstract public function getType(): string;

    /**
     * Create the real Filament table filter that backs this column filter
     * when it is not synced with an existing one.
     */
    abstract public function makeTableFilter(Column $column): BaseFilter;

    /**
     * The configuration passed to the Alpine popup component.
     *
     * @return array<string, mixed>
     */
    abstract public function getPopupConfig(Column $column, Table $table, ?BaseFilter $targetFilter): array;

    /**
     * The default (empty) state for the auto-registered table filter.
     *
     * @return array<string, mixed>
     */
    abstract public function getDefaultState(): array;

    /**
     * Sync this column filter with an existing table filter instead of
     * registering its own. The popup will read and write the state of the
     * given filter, so both stay in sync.
     *
     * @param  array<string, string>  $fieldMap  maps this filter's field keys to the target filter's state keys
     */
    public function syncWith(string $filterName, array $fieldMap = []): static
    {
        $this->syncWithFilter = $filterName;
        $this->syncFieldMap = $fieldMap;

        return $this;
    }

    public function isSyncingWithExisting(): bool
    {
        return $this->syncWithFilter !== null;
    }

    /**
     * Override the name used for the auto-registered table filter.
     */
    public function filterName(string $name): static
    {
        $this->filterName = $name;

        return $this;
    }

    /**
     * Override the database column (or dotted relationship path) to filter on.
     * Defaults to the table column's name.
     */
    public function attribute(string $attribute): static
    {
        $this->attribute = $attribute;

        return $this;
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Fully customize how the filter is applied to the query.
     * Signature: fn (Builder $query, array $data): Builder
     */
    public function applyUsing(Closure $callback): static
    {
        $this->applyUsing = $callback;

        return $this;
    }

    public function getTargetFilterName(Column $column): string
    {
        if ($this->syncWithFilter !== null) {
            return $this->syncWithFilter;
        }

        // Dots are not allowed in generated filter names: the name becomes a
        // Livewire state path segment, where a dot would nest the state
        // incorrectly.
        return str_replace('.', '_', $this->filterName ?? 'cf_' . $column->getName());
    }

    public function getAttribute(Column $column): string
    {
        return $this->attribute ?? $column->getName();
    }

    public function getLabel(Column $column): string
    {
        if ($this->label !== null) {
            return $this->label;
        }

        $label = $column->getLabel();

        return $label instanceof Htmlable
            ? strip_tags($label->toHtml())
            : (string) $label;
    }

    /**
     * Map a local field key ("value", "values", "from", "until") to the state
     * key inside the target filter's state array.
     */
    public function getStateKey(string $field): string
    {
        return $this->syncFieldMap[$field] ?? $field;
    }

    protected function getApplyCallback(): ?Closure
    {
        return $this->applyUsing;
    }

    /**
     * Apply a constraint on the attribute, transparently handling dotted
     * relationship paths (e.g. "author.name") using whereHas().
     *
     * @param  Closure(Builder, string): Builder  $callback
     */
    protected function applyToAttribute(Builder $query, string $attribute, Closure $callback): Builder
    {
        if (! str_contains($attribute, '.')) {
            return $callback($query, $attribute);
        }

        $relation = Str::beforeLast($attribute, '.');
        $field = Str::afterLast($attribute, '.');

        return $query->whereHas(
            $relation,
            fn (Builder $subQuery): Builder => $callback($subQuery, $subQuery->getModel()->qualifyColumn($field)),
        );
    }
}
