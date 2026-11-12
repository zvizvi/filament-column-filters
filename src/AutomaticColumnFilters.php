<?php

namespace Zvizvi\FilamentColumnFilters;

use BackedEnum;
use Filament\Support\Contracts\HasLabel;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;
use UnitEnum;
use Zvizvi\FilamentColumnFilters\Filters\ColumnFilter;

/**
 * Picks a column filter for a column that names none, when the plugin runs in
 * automatic mode ({@see FilamentColumnFiltersPlugin::automatic()}).
 *
 * The rule is "never offer a filter that cannot work", so every choice rests
 * on something that already exists rather than on a guess about the column:
 *
 * 1. a regular SelectFilter on the same attribute, or an enum cast on it -> select
 *    (the first is synced with, the second lists the enum's cases);
 * 2. a date/time database column -> date range;
 * 3. a numeric database column, keys (`id`, `*_id`) excluded -> number range;
 * 4. a searchable column (its own search definition) or a text database
 *    column -> search;
 * 5. anything else (a computed state, an image, a boolean) -> nothing.
 *
 * Only text columns are considered: an icon, image or custom view column has
 * no value a header popup could sensibly filter by.
 */
class AutomaticColumnFilters
{
    /**
     * @var array<string, array<string, string>>
     */
    protected static array $columnTypes = [];

    public static function for(Column $column, Table $table): ?ColumnFilter
    {
        if (! $column instanceof TextColumn) {
            return null;
        }

        $select = ColumnFilter::select();

        if ($select->findExistingFilter($table, $column) !== null) {
            return $select;
        }

        $resolved = static::resolveAttribute($table, $column->getName());

        if ($resolved !== null) {
            [$model, $attribute] = $resolved;

            if (($enum = static::enumCast($model, $attribute)) !== null) {
                return ColumnFilter::select()->options(static::enumOptions($enum));
            }

            $type = static::databaseType($model, $attribute);

            if ($type !== null && static::isDateType($type)) {
                return ColumnFilter::date();
            }

            if ($type !== null && static::isNumericType($type) && ! static::isKey($attribute)) {
                return ColumnFilter::range();
            }

            if ($column->isSearchable() || ($type !== null && static::isTextType($type))) {
                return ColumnFilter::search();
            }

            return null;
        }

        return $column->isSearchable() ? ColumnFilter::search() : null;
    }

    /**
     * The model and attribute a (possibly dotted) column name points at, or
     * null when it is no relationship path of the table's model.
     *
     * @return array{0: Model, 1: string}|null
     */
    protected static function resolveAttribute(Table $table, string $name): ?array
    {
        try {
            $model = app($table->getModel());
        } catch (Throwable) {
            return null;
        }

        if (! $model instanceof Model) {
            return null;
        }

        $segments = explode('.', $name);
        $attribute = array_pop($segments);

        foreach ($segments as $segment) {
            if (! method_exists($model, $segment)) {
                return null;
            }

            try {
                $relation = $model->{$segment}();
            } catch (Throwable) {
                return null;
            }

            if (! $relation instanceof Relation) {
                return null;
            }

            $model = $relation->getRelated();
        }

        return [$model, $attribute];
    }

    /**
     * @return class-string<UnitEnum>|null
     */
    protected static function enumCast(Model $model, string $attribute): ?string
    {
        $cast = $model->getCasts()[$attribute] ?? null;

        if (! is_string($cast)) {
            return null;
        }

        $cast = Str::before($cast, ':');

        return enum_exists($cast) && is_subclass_of($cast, BackedEnum::class) ? $cast : null;
    }

    /**
     * @param  class-string<UnitEnum>  $enum
     * @return array<string, string>
     */
    protected static function enumOptions(string $enum): array
    {
        $options = [];

        foreach ($enum::cases() as $case) {
            $label = $case instanceof HasLabel ? $case->getLabel() : $case->name;

            $options[(string) $case->value] = (string) ($label ?? $case->name);
        }

        return $options;
    }

    /**
     * The column's type as the database reports it, lower-cased, or null when
     * the table has no such column (an accessor, a count, a computed state).
     * Asked once per table per process.
     */
    protected static function databaseType(Model $model, string $attribute): ?string
    {
        $key = ($model->getConnectionName() ?? 'default') . '|' . $model->getTable();

        if (! array_key_exists($key, static::$columnTypes)) {
            static::$columnTypes[$key] = [];

            try {
                foreach (Schema::connection($model->getConnectionName())->getColumns($model->getTable()) as $definition) {
                    static::$columnTypes[$key][$definition['name']] = strtolower((string) ($definition['type_name'] ?? $definition['type'] ?? ''));
                }
            } catch (Throwable) {
                // An unreadable table answers "no such column", never an error.
            }
        }

        return static::$columnTypes[$key][$attribute] ?? null;
    }

    protected static function isDateType(string $type): bool
    {
        return str_contains($type, 'date') || str_starts_with($type, 'timestamp');
    }

    protected static function isNumericType(string $type): bool
    {
        return (bool) preg_match('/^(tiny|small|medium|big)?int(eger)?\d*$|^(int[248]|decimal|numeric|float\d*|double( precision)?|real|money)$/', $type);
    }

    protected static function isTextType(string $type): bool
    {
        return (bool) preg_match('/^(var)?char(acter)?( varying)?$|^(tiny|medium|long)?text$|^string$|^citext$|^bpchar$/', $type);
    }

    protected static function isKey(string $attribute): bool
    {
        return $attribute === 'id' || str_ends_with($attribute, '_id');
    }

    /**
     * For tests: forget the cached table definitions.
     */
    public static function flushCache(): void
    {
        static::$columnTypes = [];
    }
}
