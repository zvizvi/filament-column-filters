<?php

use Filament\Facades\Filament;
use Filament\Tables\Filters\SelectFilter;
use Zvizvi\FilamentColumnFilters\AutomaticColumnFilters;
use Zvizvi\FilamentColumnFilters\FilamentColumnFiltersPlugin;
use Zvizvi\FilamentColumnFilters\Tests\Fixtures\AutomaticDonorsTable;
use Zvizvi\FilamentColumnFilters\Tests\Fixtures\Category;
use Zvizvi\FilamentColumnFilters\Tests\Fixtures\Donor;
use Zvizvi\FilamentColumnFilters\Tests\Fixtures\DonorKind;

use function Pest\Livewire\livewire;

function columnFiltersPlugin(): FilamentColumnFiltersPlugin
{
    /** @var FilamentColumnFiltersPlugin */
    return Filament::getPanel('testing')->getPlugin('filament-column-filters');
}

beforeEach(function () {

    AutomaticColumnFilters::flushCache();
    columnFiltersPlugin()->automatic();
});

afterEach(function () {
    columnFiltersPlugin()->automatic(false);
});

it('gives each column the filter that fits it', function () {
    $table = livewire(AutomaticDonorsTable::class)->instance()->getTable();

    expect($table->getFilter('cf_name'))->not->toBeNull()
        ->and($table->getFilter('cf_created_at'))->not->toBeNull()
        ->and($table->getFilter('cf_amount'))->not->toBeNull()
        ->and($table->getFilter('cf_kind'))->not->toBeNull()
        ->and($table->getFilter('cf_category_name'))->not->toBeNull()
        ->and($table->getFilter('cf_searchable_name'))->not->toBeNull()
        // The regular "status" filter is synced with, not duplicated.
        ->and($table->getFilter('cf_status'))->toBeNull()
        ->and($table->getFilter('status'))->toBeInstanceOf(SelectFilter::class);
});

it('offers nothing it cannot filter by', function () {
    $table = livewire(AutomaticDonorsTable::class)->instance()->getTable();

    expect($table->getFilter('cf_category_id'))->toBeNull() // a key
        ->and($table->getFilter('cf_display_name'))->toBeNull() // an accessor
        ->and($table->getFilter('cf_avatar'))->toBeNull() // not a text column
        ->and($table->getFilter('cf_updated_at'))->toBeNull(); // opted out
});

it('leaves a filter a column names itself as it is', function () {
    $table = livewire(AutomaticDonorsTable::class)->instance()->getTable();

    expect($table->getFilter('cf_id'))->not->toBeNull();
});

it('filters by what it chose', function () {
    $alice = Donor::create(['name' => 'Alice', 'amount' => 50, 'kind' => DonorKind::Company, 'created_at' => '2026-06-15 10:00:00']);
    $bob = Donor::create(['name' => 'Bob', 'amount' => 500, 'kind' => DonorKind::Private, 'created_at' => '2020-01-15 10:00:00']);

    livewire(AutomaticDonorsTable::class)
        ->set('tableFilters.cf_name.value', 'Ali')
        ->assertCanSeeTableRecords([$alice])
        ->assertCanNotSeeTableRecords([$bob]);

    livewire(AutomaticDonorsTable::class)
        ->set('tableFilters.cf_amount', ['from' => 100, 'until' => null])
        ->assertCanSeeTableRecords([$bob])
        ->assertCanNotSeeTableRecords([$alice]);

    livewire(AutomaticDonorsTable::class)
        ->set('tableFilters.cf_created_at', ['from' => '2026-01-01', 'until' => '2026-12-31'])
        ->assertCanSeeTableRecords([$alice])
        ->assertCanNotSeeTableRecords([$bob]);

    livewire(AutomaticDonorsTable::class)
        ->set('tableFilters.cf_kind.values', ['company'])
        ->assertCanSeeTableRecords([$alice])
        ->assertCanNotSeeTableRecords([$bob]);
});

it('lists an enum column by its cases and labels', function () {
    livewire(AutomaticDonorsTable::class)
        ->assertSeeHtml('Private person')
        ->assertSeeHtml('value="company"');
});

it('searches a relationship column through the relationship', function () {
    $books = Category::create(['name' => 'Books']);
    $alice = Donor::create(['name' => 'Alice', 'category_id' => $books->id]);
    $bob = Donor::create(['name' => 'Bob']);

    livewire(AutomaticDonorsTable::class)
        ->set('tableFilters.cf_category_name.value', 'Boo')
        ->assertCanSeeTableRecords([$alice])
        ->assertCanNotSeeTableRecords([$bob]);
});

it('does nothing when automatic mode is off', function () {
    columnFiltersPlugin()->automatic(false);

    $table = livewire(AutomaticDonorsTable::class)->instance()->getTable();

    expect($table->getFilter('cf_name'))->toBeNull()
        ->and($table->getFilter('cf_id'))->not->toBeNull();
});
