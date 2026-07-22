<?php

namespace Zvizvi\FilamentColumnTools\Tests\Fixtures;

use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DonorsTableWithCustomDateFilter extends DonorsTable
{
    public function table(Table $table): Table
    {
        return parent::table($table)->pushFilters([
            // A regular date filter named like the created_at column, but
            // with state keys that do not match the column filter's
            // from/until fields.
            Filter::make('created_at')
                ->schema([
                    DatePicker::make('created_from'),
                    DatePicker::make('created_until'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['created_from'] ?? null,
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['created_until'] ?? null,
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                        );
                }),
        ]);
    }
}
