<?php

use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Zvizvi\FilamentColumnTools\Filters\ColumnFilter;
use Zvizvi\FilamentColumnTools\Tests\Fixtures\Donor;
use Zvizvi\FilamentColumnTools\Tests\Fixtures\DonorsTable;
use Zvizvi\FilamentColumnTools\Tests\Fixtures\DonorsTableWithCustomDateFilter;

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

it('auto-syncs with an existing filter on the same attribute without syncWith', function () {
    $component = livewire(DonorsTable::class);
    $table = $component->instance()->getTable();

    // The status column has no syncWith(), yet no cf_status filter is
    // generated because the regular "status" SelectFilter matches — and the
    // header popup shows its options.
    expect($table->getFilter('cf_status'))->toBeNull();

    $component->assertSeeHtml('value="open"');
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
        ->and($indicators[0]->getLabel())->toContain('abc')
        ->and($indicators[0]->getRemoveField())->toBe('value');
});

it('shows a separate indicator for each active generated filter', function () {
    $component = livewire(DonorsTable::class)
        ->set('tableFilters.cf_name.value', 'abc')
        ->set('tableFilters.cf_display_name.value', 'def')
        ->set('tableFilters.cf_created_at', ['from' => '2026-01-01', 'until' => '2026-12-31'])
        ->instance();

    $labels = collect($component->getTable()->getFilterIndicators())
        ->map(fn ($indicator): string => (string) $indicator->getLabel())
        ->all();

    expect(array_filter($labels, fn (string $label): bool => str_contains($label, 'abc')))->toHaveCount(1)
        ->and(array_filter($labels, fn (string $label): bool => str_contains($label, 'def')))->toHaveCount(1)
        ->and(array_filter($labels, fn (string $label): bool => str_contains($label, '2026-01-01')))->toHaveCount(1)
        ->and(array_filter($labels, fn (string $label): bool => str_contains($label, '2026-12-31')))->toHaveCount(1);
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

it('adopts the mode of a matching single select filter when multiple() was not set', function () {
    $table = livewire(DonorsTable::class)->instance()->getTable();
    $column = $table->getColumn('name');

    $match = ColumnFilter::select()->findExistingFilter($table, $column);

    expect($match)->toBeInstanceOf(SelectFilter::class)
        ->and($match->getName())->toBe('name_single');
});

it('does not auto-sync an explicitly multiple column filter with a single select filter', function () {
    $table = livewire(DonorsTable::class)->instance()->getTable();
    $column = $table->getColumn('name');

    $match = ColumnFilter::select()->multiple()->findExistingFilter($table, $column);

    expect($match)->toBeNull();
});

it('auto-syncs an explicitly multiple column filter with a multiple select filter', function () {
    $table = livewire(DonorsTable::class)->instance()->getTable();
    $column = $table->getColumn('status');

    $match = ColumnFilter::select()->multiple()->findExistingFilter($table, $column);

    expect($match)->toBeInstanceOf(SelectFilter::class)
        ->and($match->getName())->toBe('status');
});

it('generates a separate filter when a same-named regular filter has different state keys', function () {
    $component = livewire(DonorsTableWithCustomDateFilter::class);
    $table = $component->instance()->getTable();

    // The regular "created_at" filter uses created_from / created_until, so
    // the column filter cannot sync with it and works independently.
    expect($table->getFilter('cf_created_at'))->not->toBeNull();

    $old = Donor::create(['name' => 'Old', 'created_at' => '2020-01-15 10:00:00']);
    $recent = Donor::create(['name' => 'Recent', 'created_at' => '2026-06-15 10:00:00']);

    livewire(DonorsTableWithCustomDateFilter::class)
        ->set('tableFilters.cf_created_at', ['from' => '2026-01-01', 'until' => '2026-12-31'])
        ->assertCanSeeTableRecords([$recent])
        ->assertCanNotSeeTableRecords([$old]);

    $indicators = livewire(DonorsTableWithCustomDateFilter::class)
        ->set('tableFilters.cf_created_at', ['from' => '2026-01-01', 'until' => null])
        ->instance()
        ->getTable()
        ->getFilter('cf_created_at')
        ->getIndicators();

    expect($indicators)->toHaveCount(1);
});

it('auto-syncs with a same-named regular filter whose state keys match', function () {
    $table = livewire(DonorsTable::class)->instance()->getTable();

    $table->pushFilters([
        Filter::make('created_at')
            ->schema([
                DatePicker::make('from'),
                DatePicker::make('until'),
            ]),
    ]);

    $column = $table->getColumn('created_at');
    $match = ColumnFilter::date()->findExistingFilter($table, $column);

    expect($match)->not->toBeNull()
        ->and($match->getName())->toBe('created_at');
});
