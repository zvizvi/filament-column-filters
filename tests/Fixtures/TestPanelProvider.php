<?php

namespace Zvizvi\FilamentColumnFilters\Tests\Fixtures;

use Filament\Panel;
use Filament\PanelProvider;
use Zvizvi\FilamentColumnFilters\FilamentColumnFiltersPlugin;

class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('testing')
            ->plugin(FilamentColumnFiltersPlugin::make());
    }
}
