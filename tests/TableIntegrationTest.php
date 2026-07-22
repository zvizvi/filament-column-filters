<?php

use Filament\Tables\Filters\SelectFilter;
use Zvizvi\FilamentColumnTools\Tests\Fixtures\Donor;
use Zvizvi\FilamentColumnTools\Tests\Fixtures\DonorsTable;

use function Pest\Livewire\livewire;

it('renders the table component', function () {
    livewire(DonorsTable::class)->assertSuccessful();
});

it('injects generated filters into the table', function () {
    $table = livewire(DonorsTable::class)->instance()->getTable();

    expect($table->getFilter('cf_name'))->not->toBeNull()
        ->and($table->getFilter('cf_created_at'))->not->toBeNull()
        ->and($table->getFilter('status'))->toBeInstanceOf(SelectFilter::class)
        ->and($table->getFilter('cf_status'))->toBeNull();
});

it('decorates configured column headers with the filter popup', function () {
    livewire(DonorsTable::class)
        ->assertSeeHtml('fct-trigger')
        ->assertSeeHtml('filamentColumnTools(');
});

it('filters records through the generated search filter', function () {
    $alice = Donor::create(['name' => 'Alice']);
    $bob = Donor::create(['name' => 'Bob']);

    livewire(DonorsTable::class)
        ->set('tableFilters.cf_name.value', 'Ali')
        ->assertCanSeeTableRecords([$alice])
        ->assertCanNotSeeTableRecords([$bob]);
});

it('filters records through the generated date filter', function () {
    $old = Donor::create(['name' => 'Old', 'created_at' => '2020-01-15 10:00:00']);
    $recent = Donor::create(['name' => 'Recent', 'created_at' => '2026-06-15 10:00:00']);

    livewire(DonorsTable::class)
        ->set('tableFilters.cf_created_at', ['from' => '2026-01-01', 'until' => '2026-12-31'])
        ->assertCanSeeTableRecords([$recent])
        ->assertCanNotSeeTableRecords([$old]);
});

it('filters records through the synced regular select filter', function () {
    $open = Donor::create(['name' => 'Open donor', 'status' => 'open']);
    $closed = Donor::create(['name' => 'Closed donor', 'status' => 'closed']);

    livewire(DonorsTable::class)
        ->set('tableFilters.status.values', ['open'])
        ->assertCanSeeTableRecords([$open])
        ->assertCanNotSeeTableRecords([$closed]);
});

it('keeps generated filters out of the standard filters form', function () {
    $component = livewire(DonorsTable::class)->instance();

    $schema = $component->getTableFiltersForm();

    expect($schema->getComponent(fn ($schemaComponent): bool => $schemaComponent->getKey() === 'cf_name' || str_contains((string) ($schemaComponent->getStatePath(false) ?? ''), 'cf_name'), withHidden: true))
        ->toBeNull();
});

it('produces indicators for active generated filters', function () {
    $component = livewire(DonorsTable::class)
        ->set('tableFilters.cf_name.value', 'abc')
        ->instance();

    $indicators = $component->getTable()->getFilter('cf_name')->getIndicators();

    expect($indicators)->toHaveCount(1)
        ->and($indicators['value']->getLabel())->toContain('abc');
});

it('clears a generated filter when its indicator is removed', function () {
    $alice = Donor::create(['name' => 'Alice']);
    $bob = Donor::create(['name' => 'Bob']);

    livewire(DonorsTable::class)
        ->set('tableFilters.cf_name.value', 'Ali')
        ->assertCanNotSeeTableRecords([$bob])
        ->call('removeTableFilter', 'cf_name', 'value')
        ->assertSet('tableFilters.cf_name.value', null)
        ->assertCanSeeTableRecords([$alice, $bob]);
});

it('clears generated filters when all filters are removed', function () {
    $alice = Donor::create(['name' => 'Alice']);
    $bob = Donor::create(['name' => 'Bob']);

    livewire(DonorsTable::class)
        ->set('tableFilters.cf_name.value', 'Ali')
        ->call('removeTableFilters')
        ->assertSet('tableFilters.cf_name.value', null)
        ->assertCanSeeTableRecords([$alice, $bob]);
});

it('searches through the column search columns when the column name is an accessor', function () {
    $alice = Donor::create(['name' => 'Alice']);
    $bob = Donor::create(['name' => 'Bob']);

    // display_name is a model accessor, not a database column; the filter
    // must use the column's searchable(['name']) definition instead.
    livewire(DonorsTable::class)
        ->set('tableFilters.cf_display_name.value', 'Ali')
        ->assertCanSeeTableRecords([$alice])
        ->assertCanNotSeeTableRecords([$bob]);
});
