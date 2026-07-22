<?php

namespace Zvizvi\FilamentColumnTools;

use Error;
use Filament\Tables\Columns\Column;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use WeakMap;
use Zvizvi\FilamentColumnTools\Filters\ColumnFilter;

use function Livewire\on;

class FilamentColumnTools
{
    /**
     * @var WeakMap<Column, ColumnFilter> | null
     */
    protected static ?WeakMap $registry = null;

    /**
     * @var WeakMap<Column, true> | null
     */
    protected static ?WeakMap $decorated = null;

    public static function registerColumnMacro(): void
    {
        if (Column::hasMacro('columnFilter')) {
            return;
        }

        Column::macro('columnFilter', function (ColumnFilter $filter) {
            /** @var Column $this */
            FilamentColumnTools::attach($this, $filter);

            return $this;
        });
    }

    /**
     * Filters must be registered on the table before Livewire updates, calls
     * and rendering, so we process the table as early as possible on every
     * request. All listeners are idempotent; Livewire's own lifecycle
     * listeners (which build the table) are registered first, so by the time
     * these run the table is available.
     *
     * Livewire's event bus is bound to the application instance, so this must
     * run on every boot of the service provider (no static guard).
     */
    public static function registerLivewireListeners(): void
    {
        on('mount', function ($component) {
            static::processComponent($component);
        });

        on('hydrate', function ($component) {
            static::processComponent($component);
        });

        on('call', function ($component, $method = null, $params = null) {
            static::processComponent($component);
            static::handleFilterRemovalCall($component, $method, is_array($params) ? $params : []);
        });

        on('render', function ($component) {
            static::processComponent($component, decorate: true);
        });
    }

    public static function attach(Column $column, ColumnFilter $filter): void
    {
        static::registry()->offsetSet($column, $filter);
    }

    public static function getColumnFilter(Column $column): ?ColumnFilter
    {
        return static::registry()->offsetExists($column)
            ? static::registry()->offsetGet($column)
            : null;
    }

    /**
     * Register the auto-generated table filters for configured columns and,
     * on render, decorate the column headers with the filter trigger + popup.
     */
    public static function processComponent(mixed $component, bool $decorate = false): void
    {
        if (! $component instanceof Component || ! $component instanceof HasTable) {
            return;
        }

        try {
            $table = $component->getTable();
        } catch (Error) {
            // The table has not been booted yet; a later listener will retry.
            return;
        }

        foreach ($table->getColumns() as $column) {
            $config = static::getColumnFilter($column);

            if ($config === null) {
                continue;
            }

            $filterName = $config->getTargetFilterName($column);
            $targetFilter = $table->getFilter($filterName);

            if ($targetFilter === null && ! $config->isSyncingWithExisting()) {
                // A matching regular filter (same name or, for selects, same
                // attribute) is synced with automatically, so the popup and
                // the regular filter share one state and one indicator.
                $existingFilter = $config->findExistingFilter($table, $column);

                if ($existingFilter !== null) {
                    $filterName = $existingFilter->getName();
                    $targetFilter = $existingFilter;
                } else {
                    // Otherwise a filter is generated. It exists only in the
                    // header popup and its indicators: it is registered after
                    // the filters form schema was built and cached, so it
                    // never renders in the standard filters dropdown.
                    $targetFilter = $config->makeTableFilter($column);
                    $table->pushFilters([$targetFilter]);

                    static::seedFilterState($component, $filterName, $config->getDefaultState());
                }
            }

            if ($decorate) {
                static::decorateColumnHeader($component, $table, $column, $config, $filterName, $targetFilter);
            }
        }
    }

    /**
     * Generated filters have no fields in the (cached) filters form, so
     * Filament's own removeTableFilter() cannot reset their state — the
     * indicator's remove button would silently do nothing. The state is
     * reset here instead, before the Livewire call runs.
     *
     * @param  array<int, mixed>  $params
     */
    protected static function handleFilterRemovalCall(mixed $component, ?string $method, array $params): void
    {
        if (! in_array($method, ['removeTableFilter', 'removeTableFilters'], true)) {
            return;
        }

        if (! $component instanceof Component || ! $component instanceof HasTable) {
            return;
        }

        try {
            $table = $component->getTable();
        } catch (Error) {
            return;
        }

        foreach ($table->getColumns() as $column) {
            $config = static::getColumnFilter($column);

            if ($config === null || $config->isSyncingWithExisting()) {
                continue;
            }

            $filterName = $config->getTargetFilterName($column);

            // Auto-synced filters (matched existing regular filters) reset
            // through the filters form like any regular filter; only
            // generated filters, which exist under the generated name, need
            // manual resetting.
            if ($table->getFilter($filterName) === null) {
                continue;
            }

            if ($method === 'removeTableFilter' && ($params[0] ?? null) !== $filterName) {
                continue;
            }

            $defaultState = $config->getDefaultState();
            $field = $method === 'removeTableFilter' ? ($params[1] ?? null) : null;

            foreach (['tableFilters', 'tableDeferredFilters'] as $property) {
                if (! property_exists($component, $property)) {
                    continue;
                }

                $state = $component->{$property} ?? [];

                if ($field !== null && array_key_exists($field, $defaultState)) {
                    $state[$filterName][$field] = $defaultState[$field];
                } else {
                    $state[$filterName] = $defaultState;
                }

                $component->{$property} = $state;
            }
        }
    }

    /**
     * Seed the default state for a generated filter without touching any
     * existing filter state.
     *
     * @param  array<string, mixed>  $defaultState
     */
    protected static function seedFilterState(Component $component, string $filterName, array $defaultState): void
    {
        foreach (['tableFilters', 'tableDeferredFilters'] as $property) {
            if (! property_exists($component, $property)) {
                continue;
            }

            $state = $component->{$property} ?? [];

            if (! array_key_exists($filterName, $state)) {
                $state[$filterName] = $defaultState;

                $component->{$property} = $state;
            }
        }
    }

    protected static function decorateColumnHeader(
        Component & HasTable $component,
        Table $table,
        Column $column,
        ColumnFilter $config,
        string $filterName,
        mixed $targetFilter,
    ): void {
        if (static::decoratedRegistry()->offsetExists($column)) {
            return;
        }

        static::decoratedRegistry()->offsetSet($column, true);

        // getLabel() already applies translateLabel() here, so the flag must
        // be turned off afterwards — otherwise Filament would pass the
        // HtmlString wrapper to the translator, which only accepts strings.
        $label = $column->getLabel();
        $labelHtml = $label instanceof Htmlable ? $label->toHtml() : e($label);

        $state = $component->getTableFilterState($filterName);

        // The popup must bind to the resolved filter name, which may be an
        // auto-detected existing filter rather than the generated name.
        $popupConfig = $config->getPopupConfig($column, $table, $targetFilter);
        $popupConfig['filterName'] = $filterName;

        $html = view('filament-column-tools::column-filter-header', [
            'labelHtml' => $labelHtml,
            'type' => $config->getType(),
            'config' => $popupConfig,
            'isActive' => static::hasActiveState($state),
        ])->render();

        $column
            ->label(new HtmlString($html))
            ->translateLabel(false)
            ->extraHeaderAttributes(['class' => 'fct-th'], merge: true);
    }

    /**
     * @return WeakMap<Column, ColumnFilter>
     */
    protected static function registry(): WeakMap
    {
        return static::$registry ??= new WeakMap;
    }

    /**
     * @return WeakMap<Column, true>
     */
    protected static function decoratedRegistry(): WeakMap
    {
        return static::$decorated ??= new WeakMap;
    }

    protected static function hasActiveState(mixed $state): bool
    {
        if (! is_array($state)) {
            return filled($state);
        }

        foreach ($state as $value) {
            if (is_array($value) ? $value !== [] : filled($value)) {
                return true;
            }
        }

        return false;
    }
}
