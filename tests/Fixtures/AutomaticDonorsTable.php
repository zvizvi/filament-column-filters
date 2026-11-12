<?php

namespace Zvizvi\FilamentColumnFilters\Tests\Fixtures;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Zvizvi\FilamentColumnFilters\Filters\ColumnFilter;

/**
 * A table that names (almost) no column filters: automatic mode decides.
 */
class AutomaticDonorsTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(Donor::query())
            ->columns([
                // A text database column -> search.
                TextColumn::make('name'),
                // A regular SelectFilter on the same attribute -> synced select.
                TextColumn::make('status'),
                // A date database column -> date range.
                TextColumn::make('created_at'),
                // A numeric database column -> number range.
                TextColumn::make('amount'),
                // A key -> nothing.
                TextColumn::make('category_id'),
                // An enum cast -> select over the enum's cases.
                TextColumn::make('kind'),
                // A relationship's text column -> search.
                TextColumn::make('category.name'),
                // An accessor: nothing to filter on, unless it is searchable.
                TextColumn::make('display_name'),
                TextColumn::make('searchable_name')
                    ->state(fn (Donor $record): string => $record->name)
                    ->searchable(['name']),
                // Not a text column -> nothing.
                ImageColumn::make('avatar'),
                // Opted out.
                TextColumn::make('updated_at')
                    ->withoutColumnFilter(),
                // Names its own filter, which automatic mode leaves alone.
                TextColumn::make('id')
                    ->columnFilter(ColumnFilter::search()),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'closed' => 'Closed',
                    ])
                    ->multiple(),
            ]);
    }

    public function render(): View
    {
        return view('filament-column-filters-tests::donors-table');
    }
}
