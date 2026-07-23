# Filament Column Filters

Excel-style column header filters for [Filament](https://filamentphp.com) tables.

Adds a small filter icon to the header of any table column. Clicking it opens a popup toolbar — just like the column filters you know from Excel — with a filter type you choose per column:

- **Search** — a free-text search on the column.
- **Date** — a date range with quick presets (today, yesterday, this week, last week, this month, last month, last 7 days, last 30 days, this year, last year) and a custom from/until range.
- **Select** — a single or multi value picker.
- **Range** — a numeric from/until range with two number inputs side by side.

The header filters are backed by *real* Filament table filters, so they apply to the table query like any other filter and show the standard filter indicators (with working remove buttons) — but they do not clutter the standard filters dropdown. When you **sync with an existing filter** you already have on the table (via `syncWith()`), the popup reads and writes that filter's state, so choosing a value in the header popup updates the regular filter — dropdown included — and vice versa.

RTL is fully supported and Hebrew translations are included.

## Installation

```bash
composer require zvizvi/filament-column-filters
```

Register the plugin on each panel that should have column filters. This is required — the `columnFilter()` method and the header popups only become available on panels where the plugin is registered:

```php
use Zvizvi\FilamentColumnFilters\FilamentColumnFiltersPlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugin(FilamentColumnFiltersPlugin::make());
}
```

## Usage

Attach a filter to any table column with the `columnFilter()` method:

```php
use Filament\Tables\Columns\TextColumn;
use Zvizvi\FilamentColumnFilters\Filters\ColumnFilter;

public function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('user_name')
                ->label('User Name')
                ->columnFilter(ColumnFilter::search()),

            TextColumn::make('created_at')
                ->label('Date')
                ->date()
                ->columnFilter(ColumnFilter::date()),

            TextColumn::make('status')
                ->columnFilter(
                    ColumnFilter::select()
                        ->options([
                            'open' => 'Open',
                            'closed' => 'Closed',
                        ])
                        ->multiple(),
                ),
        ]);
}
```

That's it. Each configured column gets a filter icon in its header, and a matching filter is automatically registered on the table behind the scenes. The auto-registered filter shows up as a regular filter indicator when active (removable as usual), but it does not appear in the standard filters dropdown — the popup is its only editing UI. If you want it in the dropdown too, define a regular filter yourself and connect the two with `syncWith()`.

### Filter types

#### Search

```php
ColumnFilter::search()
    ->placeholder('Search Name') // optional, defaults to "Search {label}"
```

Performs a `LIKE %value%` search on the column.

#### Date

```php
ColumnFilter::date()
    ->presets(['today', 'yesterday', 'this_week', 'last_7_days']) // optional, defaults to all presets
    ->weekStartsOn(0) // 0 = Sunday (default), 1 = Monday
```

Filters records between the chosen `from` / `until` dates (each side optional). The quick-select presets fill the custom range for you.

#### Select

```php
ColumnFilter::select()
    ->options(['a' => 'Option A', 'b' => 'Option B']) // array or closure
    ->multiple() // default: true; pass false for single select
    ->searchable() // force the option search field on (or off with false)
    ->searchThreshold(8) // options count above which the search field shows automatically (default: 8)
```

When there are many options, a search field appears at the top of the popup to filter the option list (client-side).

#### Range

```php
TextColumn::make('amount')
    ->columnFilter(
        ColumnFilter::range()
            ->step(0.01), // optional step for the number inputs
    ),
```

Filters records between the entered minimum / maximum values (each side optional).

### Syncing with an existing table filter

If the table already has a regular Filament filter for the same value, the column filter syncs with it instead of registering its own — the header popup reads and writes that filter's state, so both stay in sync and only one indicator shows.

This happens **automatically** when a regular filter matches the column: a filter named exactly like the column, or — for `select` filters — any `SelectFilter` on the same attribute. In that case options and single/multiple mode are read from the existing filter too, and no configuration is needed:

```php
$table
    ->columns([
        TextColumn::make('status')
            ->columnFilter(ColumnFilter::select()), // auto-syncs with the "status" filter below
    ])
    ->filters([
        SelectFilter::make('status')
            ->options([...])
            ->multiple(),
    ]);
```

When the names don't line up (or the filter's state keys differ), point the column filter at the right filter by name with `syncWith()`:

```php
use Filament\Tables\Filters\SelectFilter;

$table
    ->columns([
        TextColumn::make('status')
            ->columnFilter(ColumnFilter::select()->syncWith('status')),
    ])
    ->filters([
        SelectFilter::make('status')
            ->options([
                'open' => 'Open',
                'closed' => 'Closed',
            ])
            ->multiple(),
    ]);
```

For a `select` sync, the options and single/multiple mode are read automatically from the existing `SelectFilter` (you can still override with `->options()`).

For filters with custom form field names, map the popup's fields to your filter's state keys:

```php
TextColumn::make('created_at')
    ->columnFilter(
        ColumnFilter::date()->syncWith('created', [
            'from' => 'created_from',
            'until' => 'created_until',
        ]),
    ),

// with a regular filter like:
Filter::make('created')
    ->schema([
        DatePicker::make('created_from'),
        DatePicker::make('created_until'),
    ])
    ->query(/* ... */),
```

The `search` filter maps its single field the same way: `->syncWith('name', ['value' => 'q'])`.

### Common options

All filter types support:

```php
ColumnFilter::search()
    ->filterName('my_filter')        // name of the auto-registered filter (default: "cf_{column}")
    ->attribute('some_column')       // database column / dotted relation path (default: the column name)
    ->label('Custom label')          // label used for the filter + indicators
    ->applyUsing(fn (Builder $query, array $data) => $query->where(/* ... */)), // custom query logic
```

Columns whose name contains a dot (e.g. `author.name`) are filtered through the relationship automatically using `whereHas()`.

## Translations

English and Hebrew translations are included. Publish them to customize:

```bash
php artisan vendor:publish --tag=filament-column-filters-translations
```

## Development

```bash
npm install
npm run build   # build resources/dist assets
composer test   # run the test suite
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
