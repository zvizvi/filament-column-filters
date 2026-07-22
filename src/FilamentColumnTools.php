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

        on('call', function ($component) {
            static::processComponent($component);
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
                // Generated filters exist only in the header popup: they are
                // registered after the filters form schema was built and
                // cached, so they never render in the standard filters
                // dropdown, and their indicators are disabled. Filters synced
                // with syncWith() keep their full regular-filter UI.
                $targetFilter = $config->makeTableFilter($column);
                $table->pushFilters([$targetFilter]);

                static::seedFilterState($component, $filterName, $config->getDefaultState());
            }

            if ($decorate) {
                static::decorateColumnHeader($component, $table, $column, $config, $filterName, $targetFilter);
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

        $html = view('filament-column-tools::column-filter-header', [
            'labelHtml' => $labelHtml,
            'type' => $config->getType(),
            'config' => $config->getPopupConfig($column, $table, $targetFilter),
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
