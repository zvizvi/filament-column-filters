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
        //
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
