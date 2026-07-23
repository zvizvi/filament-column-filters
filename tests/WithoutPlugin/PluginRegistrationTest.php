<?php

use Zvizvi\FilamentColumnFilters\Tests\Fixtures\DonorsTable;

use function Pest\Livewire\livewire;

it('does not inject generated filters when the plugin is not registered on a panel', function () {
    $table = livewire(DonorsTable::class)->instance()->getTable();

    expect($table->getFilter('cf_name'))->toBeNull()
        ->and($table->getFilter('cf_created_at'))->toBeNull();
});

it('does not decorate column headers when the plugin is not registered on a panel', function () {
    livewire(DonorsTable::class)
        ->assertSuccessful()
        ->assertDontSeeHtml('fcf-trigger')
        ->assertDontSeeHtml('filamentColumnFilters(');
});
