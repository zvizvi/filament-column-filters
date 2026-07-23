<?php

use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Zvizvi\FilamentColumnFilters\Filters\ColumnFilter;
use Zvizvi\FilamentColumnFilters\Tests\Fixtures\Category;
use Zvizvi\FilamentColumnFilters\Tests\Fixtures\Donor;
use Zvizvi\FilamentColumnFilters\Tests\Fixtures\DonorsTable;
use Zvizvi\FilamentColumnFilters\Tests\Fixtures\DonorsTableWithCustomDateFilter;
use Zvizvi\FilamentColumnFilters\Tests\Fixtures\Team;

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
        ->assertSeeHtml('fcf-trigger')
        ->assertSeeHtml('filamentColumnFilters(');
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

it('searches through nested relationship search columns', function () {
    $alphaTeam = Team::create(['title' => 'Alpha']);
    $betaTeam = Team::create(['title' => 'Beta']);
    $alphaCategory = Category::create(['name' => 'General', 'team_id' => $alphaTeam->id]);
    $betaCategory = Category::create(['name' => 'General', 'team_id' => $betaTeam->id]);

    $alphaDonor = Donor::create(['name' => 'Alice', 'category_id' => $alphaCategory->id]);
    $betaDonor = Donor::create(['name' => 'Bob', 'category_id' => $betaCategory->id]);

    // The category.name column searches through searchable(['category.team.title']),
    // which spans two relationship levels from the base model.
    livewire(DonorsTable::class)
        ->set('tableFilters.cf_category_name.value', 'Alph')
        ->assertCanSeeTableRecords([$alphaDonor])
        ->assertCanNotSeeTableRecords([$betaDonor]);
});

it('filters records through the generated range filter', function () {
    $small = Donor::create(['name' => 'Small', 'amount' => 100]);
    $big = Donor::create(['name' => 'Big', 'amount' => 900]);

    livewire(DonorsTable::class)
        ->set('tableFilters.cf_amount', ['from' => 500, 'until' => 1000])
        ->assertCanSeeTableRecords([$big])
        ->assertCanNotSeeTableRecords([$small]);

    livewire(DonorsTable::class)
        ->set('tableFilters.cf_amount', ['from' => null, 'until' => 200])
        ->assertCanSeeTableRecords([$small])
        ->assertCanNotSeeTableRecords([$big]);
});

it('produces separate min and max indicators for the range filter', function () {
    $component = livewire(DonorsTable::class)
        ->set('tableFilters.cf_amount', ['from' => 50, 'until' => 300])
        ->instance();

    $indicators = $component->getTable()->getFilter('cf_amount')->getIndicators();

    expect($indicators)->toHaveCount(2)
        ->and($indicators[0]->getRemoveField())->toBe('from')
        ->and($indicators[1]->getRemoveField())->toBe('until');
});
