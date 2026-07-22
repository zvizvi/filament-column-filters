<?php

namespace Zvizvi\FilamentColumnTools\Tests\Fixtures;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Zvizvi\FilamentColumnTools\Filters\ColumnFilter;

class DonorsTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(Donor::query())
            ->columns([
                TextColumn::make('name')
                    ->translateLabel()
                    ->columnFilter(ColumnFilter::search()),
                TextColumn::make('status')
                    ->columnFilter(ColumnFilter::select()),
                TextColumn::make('created_at')
                    ->date()
                    ->columnFilter(ColumnFilter::date()),
                TextColumn::make('display_name')
                    ->searchable(['name'])
                    ->columnFilter(ColumnFilter::search()),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'closed' => 'Closed',
                    ])
                    ->multiple(),
                SelectFilter::make('name_single')
                    ->options([
                        'Alice' => 'Alice',
                        'Bob' => 'Bob',
                    ])
                    ->attribute('name'),
            ]);
    }

    public function render(): View
    {
        return view('filament-column-tools-tests::donors-table');
    }
}
