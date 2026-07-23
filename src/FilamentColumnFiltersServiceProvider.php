<?php

namespace Zvizvi\FilamentColumnFilters;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentColumnFiltersServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-column-filters';

    public static string $viewNamespace = 'filament-column-filters';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasTranslations()
            ->hasViews(static::$viewNamespace);
    }

    public function packageBooted(): void
    {
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentColumnFilters::registerColumnMacro();

        FilamentColumnFilters::registerLivewireListeners();
    }

    protected function getAssetPackageName(): string
    {
        return 'zvizvi/filament-column-filters';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            AlpineComponent::make('filament-column-filters', __DIR__ . '/../resources/dist/components/filament-column-filters.js'),
            Css::make('filament-column-filters-styles', __DIR__ . '/../resources/dist/filament-column-filters.css'),
        ];
    }
}
