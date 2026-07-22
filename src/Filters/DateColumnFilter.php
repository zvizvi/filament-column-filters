<?php

namespace Zvizvi\FilamentColumnTools\Filters;

use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DateColumnFilter extends ColumnFilter
{
    public const PRESETS = [
        'today',
        'yesterday',
        'this_week',
        'last_week',
        'this_month',
        'last_month',
        'last_7_days',
        'last_30_days',
        'this_year',
        'last_year',
    ];

    /**
     * @var list<string>
     */
    protected array $presets = self::PRESETS;

    protected int $weekStartsOn = 0;

    public function getType(): string
    {
        return 'date';
    }

    /**
     * Limit or reorder the quick-select presets shown in the popup.
     *
     * @param  list<string>  $presets
     */
    public function presets(array $presets): static
    {
        $this->presets = array_values(array_intersect($presets, self::PRESETS));

        return $this;
    }

    /**
     * First day of the week for the "this week" / "last week" presets.
     * 0 = Sunday (default), 1 = Monday.
     */
    public function weekStartsOn(int $day): static
    {
        $this->weekStartsOn = $day;

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
                DatePicker::make('from')
                    ->label(__('filament-column-tools::filters.from_date')),
                DatePicker::make('until')
                    ->label(__('filament-column-tools::filters.until_date')),
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
                        if ($from) {
                            $subQuery->whereDate($qualifiedAttribute, '>=', $from);
                        }

                        if ($until) {
                            $subQuery->whereDate($qualifiedAttribute, '<=', $until);
                        }

                        return $subQuery;
                    },
                );
            })
            ->indicateUsing(function (array $data) use ($label): array {
                $indicators = [];

                if (filled($data['from'] ?? null)) {
                    $indicators['from'] = __('filament-column-tools::filters.indicator_from', [
                        'label' => $label,
                        'date' => $data['from'],
                    ]);
                }

                if (filled($data['until'] ?? null)) {
                    $indicators['until'] = __('filament-column-tools::filters.indicator_until', [
                        'label' => $label,
                        'date' => $data['until'],
                    ]);
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
            'type' => 'date',
            'filterName' => $this->getTargetFilterName($column),
            'fields' => [
                'from' => $this->getStateKey('from'),
                'until' => $this->getStateKey('until'),
            ],
            'presets' => $this->presets,
            'weekStartsOn' => $this->weekStartsOn,
        ];
    }
}
