<?php

namespace Zvizvi\FilamentColumnFilters;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentColumnFiltersPlugin implements Plugin
{
    protected bool | Closure $isAutomatic = false;

    /**
     * Give every column that names no filter of its own the one that fits it
     * ({@see AutomaticColumnFilters}). A column opts out with
     * `->withoutColumnFilter()`; one that names a filter keeps it.
     */
    public function automatic(bool | Closure $condition = true): static
    {
        $this->isAutomatic = $condition;

        return $this;
    }

    public function isAutomatic(): bool
    {
        return (bool) ($this->isAutomatic instanceof Closure ? ($this->isAutomatic)() : $this->isAutomatic);
    }

    public function getId(): string
    {
        return 'filament-column-filters';
    }

    public function register(Panel $panel): void
    {
        // Registering the plugin on a panel is what activates the feature:
        // the column macro and the Livewire listeners that decorate headers
        // and register the generated filters are only wired up here. Without
        // this, `->columnFilter()` is not available and nothing is decorated.
        FilamentColumnFilters::registerColumnMacro();

        FilamentColumnFilters::registerLivewireListeners();
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
