<?php

namespace Zvizvi\FilamentColumnFilters\Tests\Fixtures;

use Zvizvi\FilamentColumnFilters\FilamentColumnFilters;
use Zvizvi\FilamentColumnFilters\Tests\TestCase;

/**
 * A test harness identical to {@see TestCase} but with the plugin NOT
 * registered on any panel, so the column filter behaviour stays dormant.
 */
class TestCaseWithoutPlugin extends TestCase
{
    protected function getPackageProviders($app)
    {
        return array_values(array_filter(
            parent::getPackageProviders($app),
            fn ($provider): bool => $provider !== TestPanelProvider::class,
        ));
    }

    protected function setUp(): void
    {
        parent::setUp();

        // The `columnFilter()` macro lives on Filament's Column class, which is
        // process-global; other tests in the suite may already have registered
        // it. Register it directly here so the fixtures can still call
        // ->columnFilter() without the plugin — this does NOT wire up the
        // Livewire listeners, which is precisely the behaviour under test:
        // without the plugin nothing is decorated or injected.
        FilamentColumnFilters::registerColumnMacro();
    }
}
