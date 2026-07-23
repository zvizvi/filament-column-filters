<?php

namespace Zvizvi\FilamentColumnFilters;

use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentColumnFiltersPlugin implements Plugin
{
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
