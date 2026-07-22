<?php

namespace Zvizvi\FilamentColumnTools\Filters;

use Closure;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SelectColumnFilter extends ColumnFilter
{
    /**
     * @var array<int | string, string | array<string, string>> | Closure | null
     */
    protected array | Closure | null $options = null;

    protected bool $isMultiple = true;

    public function getType(): string
    {
        return 'select';
    }

    /**
     * @param  array<int | string, string | array<string, string>> | Closure  $options
     */
    public function options(array | Closure $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function multiple(bool $condition = true): static
    {
        $this->isMultiple = $condition;

        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->isMultiple;
    }

    /**
     * @return array<int | string, string | array<string, string>>
     */
    public function getOptions(?BaseFilter $targetFilter = null): array
    {
        if ($this->options !== null) {
            $options = $this->options instanceof Closure ? ($this->options)() : $this->options;

            return (array) $options;
        }

        if ($targetFilter instanceof SelectFilter) {
            return $targetFilter->getOptions();
        }

        return [];
    }

    public function makeTableFilter(Column $column): BaseFilter
    {
        $attribute = $this->getAttribute($column);

        $filter = SelectFilter::make($this->getTargetFilterName($column))
            ->label($this->getLabel($column))
            ->options(fn (): array => $this->getOptions())
            ->attribute($attribute)
            ->indicateUsing(fn (): array => []);

        if ($this->isMultiple) {
            $filter->multiple();
        }

        $applyUsing = $this->getApplyCallback();

        if ($applyUsing !== null) {
            $filter->query($applyUsing);
        } elseif (str_contains($attribute, '.')) {
            // SelectFilter does not handle dotted attributes on its own, so
            // constrain through the relationship instead.
            $isMultiple = $this->isMultiple;

            $filter->query(function (Builder $query, array $data) use ($attribute, $isMultiple): Builder {
                $values = $isMultiple ? ($data['values'] ?? []) : ($data['value'] ?? null);

                if (blank(array_filter((array) $values, fn ($value): bool => filled($value)))) {
                    return $query;
                }

                return $this->applyToAttribute(
                    $query,
                    $attribute,
                    fn (Builder $subQuery, string $qualifiedAttribute): Builder => $isMultiple
                        ? $subQuery->whereIn($qualifiedAttribute, (array) $values)
                        : $subQuery->where($qualifiedAttribute, $values),
                );
            });
        }

        return $filter;
    }

    public function getDefaultState(): array
    {
        return $this->isMultiple ? ['values' => []] : ['value' => null];
    }

    public function getPopupConfig(Column $column, Table $table, ?BaseFilter $targetFilter): array
    {
        $isMultiple = $targetFilter instanceof SelectFilter
            ? $targetFilter->isMultiple()
            : $this->isMultiple;

        $options = [];

        foreach ($this->getOptions($targetFilter) as $value => $label) {
            if (is_array($label)) {
                // Flatten grouped options for the popup list.
                foreach ($label as $groupedValue => $groupedLabel) {
                    $options[] = ['value' => (string) $groupedValue, 'label' => (string) $groupedLabel];
                }

                continue;
            }

            $options[] = ['value' => (string) $value, 'label' => (string) $label];
        }

        return [
            'type' => 'select',
            'filterName' => $this->getTargetFilterName($column),
            'fields' => [
                'value' => $this->getStateKey($isMultiple ? 'values' : 'value'),
            ],
            'multiple' => $isMultiple,
            'options' => $options,
        ];
    }
}
