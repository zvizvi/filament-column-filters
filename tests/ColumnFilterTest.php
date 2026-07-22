<?php

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Zvizvi\FilamentColumnTools\FilamentColumnTools;
use Zvizvi\FilamentColumnTools\Filters\ColumnFilter;
use Zvizvi\FilamentColumnTools\Filters\DateColumnFilter;
use Zvizvi\FilamentColumnTools\Filters\RangeColumnFilter;
use Zvizvi\FilamentColumnTools\Filters\SearchColumnFilter;
use Zvizvi\FilamentColumnTools\Filters\SelectColumnFilter;
use Zvizvi\FilamentColumnTools\Tests\Fixtures\DonorsTable;

use function Pest\Livewire\livewire;

it('registers the columnFilter macro on columns', function () {
    expect(TextColumn::hasMacro('columnFilter'))->toBeTrue();
});

it('attaches a column filter to a column through the macro', function () {
    $column = TextColumn::make('name')->columnFilter(ColumnFilter::search());

    expect(FilamentColumnTools::getColumnFilter($column))
        ->toBeInstanceOf(SearchColumnFilter::class);
});

it('creates the correct filter types from the factory methods', function () {
    expect(ColumnFilter::search())->toBeInstanceOf(SearchColumnFilter::class)
        ->and(ColumnFilter::date())->toBeInstanceOf(DateColumnFilter::class)
        ->and(ColumnFilter::select())->toBeInstanceOf(SelectColumnFilter::class);
});

it('generates a filter name from the column name', function () {
    $column = TextColumn::make('author.name');

    expect(ColumnFilter::search()->getTargetFilterName($column))->toBe('cf_author_name');
});

it('uses the synced filter name as target when syncing', function () {
    $column = TextColumn::make('status');
    $filter = ColumnFilter::select()->syncWith('status');

    expect($filter->getTargetFilterName($column))->toBe('status')
        ->and($filter->isSyncingWithExisting())->toBeTrue();
});

it('maps state keys through the sync field map', function () {
    $filter = ColumnFilter::date()->syncWith('created', [
        'from' => 'created_from',
        'until' => 'created_until',
    ]);

    expect($filter->getStateKey('from'))->toBe('created_from')
        ->and($filter->getStateKey('until'))->toBe('created_until');
});

it('builds a search table filter with the expected name and indicator', function () {
    $column = TextColumn::make('name')->label('Name');
    $filter = ColumnFilter::search()->makeTableFilter($column);

    expect($filter->getName())->toBe('cf_name');
});

it('builds a multi select table filter by default', function () {
    $column = TextColumn::make('status');

    /** @var SelectFilter $filter */
    $filter = ColumnFilter::select()
        ->options(['a' => 'A', 'b' => 'B'])
        ->makeTableFilter($column);

    expect($filter)->toBeInstanceOf(SelectFilter::class)
        ->and($filter->isMultiple())->toBeTrue();
});

it('reads options from a synced select filter', function () {
    $target = SelectFilter::make('status')->options(['x' => 'X']);
    $filter = ColumnFilter::select()->syncWith('status');

    expect($filter->getOptions($target))->toBe(['x' => 'X']);
});

it('produces popup config for a select filter synced with a multiple select filter', function () {
    $column = TextColumn::make('status');
    $target = SelectFilter::make('status')->options(['x' => 'X'])->multiple();
    $filter = ColumnFilter::select()->syncWith('status');

    $table = livewire(DonorsTable::class)->instance()->getTable();

    $config = $filter->getPopupConfig($column, $table, $target);

    expect($config['multiple'])->toBeTrue()
        ->and($config['fields']['value'])->toBe('values')
        ->and($config['options'])->toBe([['value' => 'x', 'label' => 'X']]);
});

it('limits date presets to known ones', function () {
    $filter = ColumnFilter::date()->presets(['today', 'nonsense', 'last_week']);
    $column = TextColumn::make('created_at');

    $table = livewire(DonorsTable::class)->instance()->getTable();

    $config = $filter->getPopupConfig($column, $table, null);

    expect($config['presets'])->toBe(['today', 'last_week']);
});

it('sanitizes dots in a custom filter name', function () {
    $column = TextColumn::make('group.name');

    expect(ColumnFilter::search()->filterName('group.name')->getTargetFilterName($column))
        ->toBe('group_name');
});

it('shows the option search field automatically above the threshold', function () {
    $column = TextColumn::make('status');
    $table = livewire(DonorsTable::class)->instance()->getTable();

    $fewOptions = array_combine(range('a', 'e'), range('a', 'e'));
    $manyOptions = array_combine(range('a', 'z'), range('a', 'z'));

    $few = ColumnFilter::select()->options($fewOptions)->getPopupConfig($column, $table, null);
    $many = ColumnFilter::select()->options($manyOptions)->getPopupConfig($column, $table, null);
    $forced = ColumnFilter::select()->options($fewOptions)->searchable()->getPopupConfig($column, $table, null);
    $disabled = ColumnFilter::select()->options($manyOptions)->searchable(false)->getPopupConfig($column, $table, null);
    $lowThreshold = ColumnFilter::select()->options($fewOptions)->searchThreshold(3)->getPopupConfig($column, $table, null);

    expect($few['searchable'])->toBeFalse()
        ->and($many['searchable'])->toBeTrue()
        ->and($forced['searchable'])->toBeTrue()
        ->and($disabled['searchable'])->toBeFalse()
        ->and($lowThreshold['searchable'])->toBeTrue();
});

it('creates a range filter from the factory with a step in its popup config', function () {
    $column = TextColumn::make('amount');
    $table = livewire(DonorsTable::class)->instance()->getTable();

    $filter = ColumnFilter::range()->step(0.01);

    expect($filter)->toBeInstanceOf(RangeColumnFilter::class);

    $config = $filter->getPopupConfig($column, $table, null);

    expect($config['type'])->toBe('range')
        ->and($config['fields'])->toBe(['from' => 'from', 'until' => 'until'])
        ->and($config['step'])->toBe(0.01);
});
