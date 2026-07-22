<?php

namespace Zvizvi\FilamentColumnTools\Filters;

use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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

                // When the column is searchable, reuse its own search
                // behavior — including custom search columns like
                // searchable(['first_name', 'last_name']) and searchable
                // query closures — so the popup matches the column search
                // exactly. The column name itself may be an accessor that
                // does not exist in the database.
                if ($this->attribute === null && $column->isSearchable()) {
                    return $query->where(function (Builder $query) use ($column, $value): void {
                        $isFirst = true;

                        $column->applySearchConstraint($query, $value, $isFirst);
                    });
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
