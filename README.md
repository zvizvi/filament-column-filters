# Filament Column Tools

Excel-style column header filters for [Filament](https://filamentphp.com) tables.

Adds a small filter icon to the header of any table column. Clicking it opens a popup toolbar — just like the column filters you know from Excel — with a filter type you choose per column:

- **Search** — a free-text search on the column.
- **Date** — a date range with quick presets (today, yesterday, this week, last week, this month, last month, last 7 days, last 30 days, this year, last year) and a custom from/until range.
- **Select** — a single or multi value picker.

The header filters are backed by *real* Filament table filters, so they apply to the table query like any other filter — but they live only in the column header popup and do not clutter the standard filters dropdown or indicators. When you **sync with an existing filter** you already have on the table (via `syncWith()`), the popup reads and writes that filter's state, so choosing a value in the header popup updates the regular filter — indicators included — and vice versa.

RTL is fully supported and Hebrew translations are included.

## Installation

```bash
composer require zvizvi/filament-column-tools
```

The package registers itself automatically. No panel configuration is required, but you may register the plugin on a panel if you prefer being explicit:

```php
use Zvizvi\FilamentColumnTools\FilamentColumnToolsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugin(FilamentColumnToolsPlugin::make());
}
```

## Usage

Attach a filter to any table column with the `columnFilter()` method:

```php
use Filament\Tables\Columns\TextColumn;
use Zvizvi\FilamentColumnTools\Filters\ColumnFilter;

public function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('donor_name')
                ->label('שם התורם')
                ->columnFilter(ColumnFilter::search()),

            TextColumn::make('created_at')
                ->label('תאריך')
                ->date()
                ->columnFilter(ColumnFilter::date()),

            TextColumn::make('status')
                ->columnFilter(
                    ColumnFilter::select()
                        ->options([
                            'open' => 'פתוח',
                            'closed' => 'סגור',
                        ])
                        ->multiple(),
                ),
        ]);
}
```

That's it. Each configured column gets a filter icon in its header, and a matching filter is automatically registered on the table behind the scenes. The auto-registered filter exists only in the header popup — it does not show up in the standard filters dropdown or as an indicator. If you want the standard filter UI too, define a regular filter yourself and connect the two with `syncWith()`.

### Filter types

#### Search

```php
ColumnFilter::search()
    ->placeholder('חפש שם התורם') // optional, defaults to "Search {label}"
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
```

### Syncing with an existing table filter

If the table already has a regular Filament filter for the same value, tell the column filter to sync with it by name — the header popup will then read and write that filter's state instead of registering its own:

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
                'open' => 'פתוח',
                'closed' => 'סגור',
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
php artisan vendor:publish --tag=filament-column-tools-translations
```

## Development

```bash
npm install
npm run build   # build resources/dist assets
composer test   # run the test suite
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
