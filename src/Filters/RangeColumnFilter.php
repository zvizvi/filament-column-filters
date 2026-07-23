<?php

namespace Zvizvi\FilamentColumnFilters\Filters;

use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RangeColumnFilter extends ColumnFilter
{
    protected int | float | null $step = null;

    public function getType(): string
    {
        return 'range';
    }

    /**
     * Step for the numeric inputs (e.g. 0.01 for money values).
     */
    public function step(int | float $step): static
    {
        $this->step = $step;

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
                TextInput::make('from')
                    ->label(__('filament-column-filters::filters.range_from'))
                    ->numeric(),
                TextInput::make('until')
                    ->label(__('filament-column-filters::filters.range_until'))
                    ->numeric(),
            ])
            ->query(function (Builder $query, array $data) use ($attribute, $applyUsing): Builder {
                if ($applyUsing !== null) {
                    return $applyUsing($query, $data) ?? $query;
                }

                $from = $data['from'] ?? null;
                $until = $data['until'] ?? null;

                return $this->applyToAttribute(
                    $query,
                    $attribute,
                    function (Builder $subQuery, string $qualifiedAttribute) use ($from, $until): Builder {
                        if (filled($from)) {
                            $subQuery->where($qualifiedAttribute, '>=', $from);
                        }

                        if (filled($until)) {
                            $subQuery->where($qualifiedAttribute, '<=', $until);
                        }

                        return $subQuery;
                    },
                );
            })
            ->indicateUsing(function (array $data) use ($label): array {
                // Returned as a list of Indicator objects — string-keyed
                // arrays collide across filters when Filament merges them.
                $indicators = [];

                if (filled($data['from'] ?? null)) {
                    $indicators[] = Indicator::make(__('filament-column-filters::filters.indicator_min', [
                        'label' => $label,
                        'value' => $data['from'],
                    ]))->removeField('from');
                }

                if (filled($data['until'] ?? null)) {
                    $indicators[] = Indicator::make(__('filament-column-filters::filters.indicator_max', [
                        'label' => $label,
                        'value' => $data['until'],
                    ]))->removeField('until');
                }

                return $indicators;
            });
    }

    public function getDefaultState(): array
    {
        return ['from' => null, 'until' => null];
    }

    public function getPopupConfig(Column $column, Table $table, ?BaseFilter $targetFilter): array
    {
        return [
            'type' => 'range',
            'filterName' => $this->getTargetFilterName($column),
            'fields' => [
                'from' => $this->getStateKey('from'),
                'until' => $this->getStateKey('until'),
            ],
            'step' => $this->step,
        ];
    }
}
