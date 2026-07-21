<?php

namespace Zvizvi\FilamentColumnTools\Filters;

use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\Filter;
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
            ->query(function (Builder $query, array $data) use ($attribute, $applyUsing): Builder {
                if ($applyUsing !== null) {
                    return $applyUsing($query, $data) ?? $query;
                }

                $value = trim((string) ($data['value'] ?? ''));

                if ($value === '') {
                    return $query;
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

                return $value === '' ? [] : ["{$label}: {$value}"];
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
