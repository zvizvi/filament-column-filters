<?php

namespace Zvizvi\FilamentColumnTools;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentColumnToolsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-column-tools';

    public static string $viewNamespace = 'filament-column-tools';

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

        FilamentColumnTools::registerColumnMacro();

        FilamentColumnTools::registerLivewireListeners();
    }

    protected function getAssetPackageName(): string
    {
        return 'zvizvi/filament-column-tools';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            AlpineComponent::make('filament-column-tools', __DIR__ . '/../resources/dist/components/filament-column-tools.js'),
            Css::make('filament-column-tools-styles', __DIR__ . '/../resources/dist/filament-column-tools.css'),
        ];
    }
}
