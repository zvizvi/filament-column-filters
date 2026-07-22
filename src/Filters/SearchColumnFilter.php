<?php

namespace Zvizvi\FilamentColumnTools\Filters;

use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

class SearchColumnFilter extends ColumnFilter
{
    protected ?string $placeholder = null;

    public function getType(): string
    {
        return 'search';
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function makeTableFilter(Column $column): BaseFilter
    {
        $attribute = $this->getAttribute($column);
        $label = $this->getLabel($column);
        $applyUsing = $this->getApplyCallback();

        return Filter::make($this->getTargetFilterName($column))
            ->label($label)
            ->schema([
                TextInput::make('value')
                    ->label($label)
                    ->placeholder($this->getPlaceholder($column)),
            ])
            ->query(function (Builder $query, array $data) use ($column, $attribute, $applyUsing): Builder {
                if ($applyUsing !== null) {
                    return $applyUsing($query, $data) ?? $query;
                }

                $value = trim((string) ($data['value'] ?? ''));

                if ($value === '') {
                    return $query;
                }

                // When the column is searchable, use its search definition —
                // custom search columns like searchable(['first_name']) and
                // searchable query closures — so the popup matches the column
                // search. The column name itself may be an accessor that does
                // not exist in the database.
                if ($this->attribute === null && $column->isSearchable()) {
                    if ($this->getColumnSearchQueryCallback($column) !== null) {
                        // A searchable(query:) closure is applied through the
                        // column's own mechanism.
                        return $query->where(function (Builder $query) use ($column, $value): void {
                            $isFirst = true;

                            $column->applySearchConstraint($query, $value, $isFirst);
                        });
                    }

                    return $this->applySearchColumns($query, $column, $value);
                }

                return $this->applyToAttribute(
                    $query,
                    $attribute,
                    fn (Builder $subQuery, string $qualifiedAttribute): Builder => $subQuery->where(
                        $qualifiedAttribute,
                        'like',
                        '%' . $value . '%',
                    ),
                );
            })
            ->indicateUsing(function (array $data) use ($label): array {
                $value = trim((string) ($data['value'] ?? ''));

                if ($value === '') {
                    return [];
                }

                // Indicators must be returned as a list: string-keyed
                // indicator arrays collide across filters when Filament
                // merges them, leaving only the last filter's indicator.
                return [
                    Indicator::make("{$label}: {$value}")->removeField('value'),
                ];
            });
    }

    public function getDefaultState(): array
    {
        return ['value' => null];
    }

    protected function getColumnSearchQueryCallback(Column $column): ?Closure
    {
        return Closure::bind(fn (): ?Closure => $this->searchQuery, $column, $column::class)();
    }

    /**
     * Apply the column's search columns with proper relationship resolution.
     * Filament's own search treats extra dots in a search column as JSON
     * paths (e.g. searchable(['donor.person.first_name']) becomes
     * json_extract("donor", ...)), which breaks for nested relationships —
     * here relation segments are resolved by walking the models instead.
     */
    protected function applySearchColumns(Builder $query, Column $column, string $value): Builder
    {
        $model = $query->getModel();
        $searchColumns = $column->getSearchColumns($model);

        if ($searchColumns === []) {
            return $query;
        }

        $columnName = $column->getName();
        $columnRelation = str_contains($columnName, '.') ? Str::beforeLast($columnName, '.') : null;

        return $query->where(function (Builder $outerQuery) use ($model, $searchColumns, $columnRelation, $value): void {
            $isFirst = true;

            foreach ($searchColumns as $searchColumn) {
                $whereClause = $isFirst ? 'where' : 'orWhere';
                $isFirst = false;

                // The search column may be an absolute path from the base
                // model, or relative to the column's own relationship.
                $resolved = $this->resolveRelationPath($model, $searchColumn)
                    ?? ($columnRelation !== null
                        ? $this->resolveRelationPath($model, "{$columnRelation}.{$searchColumn}")
                        : null);

                if ($resolved !== null) {
                    [$relationPath, $field] = $resolved;

                    $outerQuery->{$whereClause === 'where' ? 'whereHas' : 'orWhereHas'}(
                        $relationPath,
                        fn (Builder $relatedQuery): Builder => $relatedQuery->where(
                            $this->getSearchColumnExpression($relatedQuery, $field),
                            'like',
                            "%{$value}%",
                        ),
                    );

                    continue;
                }

                $outerQuery->{$whereClause}(
                    $this->getSearchColumnExpression($outerQuery, $searchColumn),
                    'like',
                    "%{$value}%",
                );
            }
        });
    }

    /**
     * Walk relationship methods from the model to split a dotted path into
     * [relation path, field]. Returns null when the first segment is not a
     * relationship (a plain or JSON column).
     *
     * @return array{0: string, 1: string} | null
     */
    protected function resolveRelationPath(Model $model, string $path): ?array
    {
        $segments = explode('.', $path);
        $relations = [];
        $current = $model;

        foreach ($segments as $index => $segment) {
            if (count($segments) - $index === 1) {
                // The last segment is always a field.
                break;
            }

            if (! method_exists($current, $segment)) {
                break;
            }

            try {
                $relation = $current->{$segment}();
            } catch (\Throwable) {
                break;
            }

            if (! $relation instanceof Relation) {
                break;
            }

            $relations[] = $segment;
            $current = $relation->getRelated();
        }

        if ($relations === []) {
            return null;
        }

        return [
            implode('.', $relations),
            implode('.', array_slice($segments, count($relations))),
        ];
    }

    /**
     * Remaining dots in a field are JSON paths; plain fields are qualified
     * with the query's table to avoid ambiguity in joined queries.
     */
    protected function getSearchColumnExpression(Builder $query, string $field): string
    {
        return str_contains($field, '.')
            ? str_replace('.', '->', $field)
            : $query->qualifyColumn($field);
    }

    public function getPlaceholder(Column $column): string
    {
        return $this->placeholder ?? __('filament-column-tools::filters.search_placeholder', [
            'label' => $this->getLabel($column),
        ]);
    }

    public function getPopupConfig(Column $column, Table $table, ?BaseFilter $targetFilter): array
    {
        return [
            'type' => 'search',
            'filterName' => $this->getTargetFilterName($column),
            'fields' => [
                'value' => $this->getStateKey('value'),
            ],
            'placeholder' => $this->getPlaceholder($column),
        ];
    }
}
