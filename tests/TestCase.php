<?php

namespace Zvizvi\FilamentColumnFilters\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;
use Zvizvi\FilamentColumnFilters\FilamentColumnFiltersServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        $providers = [
            ActionsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            LivewireServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentColumnFiltersServiceProvider::class,
        ];

        sort($providers);

        return $providers;
    }

    public function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $app['view']->addNamespace('filament-column-filters-tests', __DIR__ . '/Fixtures/views');
    }

    protected function defineDatabaseMigrations(): void
    {
        $schema = $this->app['db']->connection()->getSchemaBuilder();

        $schema->create('teams', function ($table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });

        $schema->create('categories', function ($table) {
            $table->id();
            $table->foreignId('team_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        $schema->create('donors', function ($table) {
            $table->id();
            $table->foreignId('category_id')->nullable();
            $table->string('name');
            $table->string('status')->default('open');
            $table->integer('amount')->default(0);
            $table->timestamps();
        });
    }
}
