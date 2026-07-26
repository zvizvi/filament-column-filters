<?php

use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Livewire\LivewireManager;
use Zvizvi\FilamentColumnFilters\Tests\Fixtures\DonorsTable;
use Zvizvi\FilamentColumnFilters\Tests\Fixtures\HeadlessDonorsTable;

/**
 * Builds a component's table the way a headless driver does: instantiate the
 * class, replay Livewire's boot hooks, read the table. No Livewire request, so
 * none of the plugin's mount/hydrate/call/render listeners fire.
 */
function bootHeadless(string $componentClass): Table
{
    $component = app(LivewireManager::class)->new($componentClass);

    foreach (class_uses_recursive($component) as $trait) {
        $method = 'booted' . class_basename($trait);

        if (method_exists($component, $method)) {
            $component->{$method}();
        }
    }

    return $component->getTable();
}

it('does not register generated filters without the trait, since no livewire event fires', function () {
    // Not a wish, a warning: this is the state a headless driver sees, and why
    // applying the filter there fails with "The filter [cf_name] does not exist."
    expect(bootHeadless(DonorsTable::class)->getFilter('cf_name'))->toBeNull();
});

it('registers them from the component boot when the page uses the trait', function () {
    $table = bootHeadless(HeadlessDonorsTable::class);

    expect($table->getFilter('cf_name'))->not->toBeNull()
        ->and($table->getFilter('cf_created_at'))->not->toBeNull();
});

it('decorates the headers too, so a headless schema matches the rendered one', function () {
    $table = bootHeadless(HeadlessDonorsTable::class);

    expect($table->getColumn('name')->getLabel())
        ->toBeInstanceOf(HtmlString::class);
});

it('stays idempotent when livewire fires its own listeners on top of the trait', function () {
    $component = Livewire\Livewire::test(HeadlessDonorsTable::class);
    $table = $component->instance()->getTable();

    $component->assertSuccessful();

    // One filter, one decoration — not one per lifecycle event.
    expect($table->getFilter('cf_name'))->not->toBeNull()
        ->and(substr_count($component->html(), 'fcf-header-label'))
        ->toBe(substr_count(Livewire\Livewire::test(DonorsTable::class)->html(), 'fcf-header-label'));
});
