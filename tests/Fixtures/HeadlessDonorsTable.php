<?php

namespace Zvizvi\FilamentColumnFilters\Tests\Fixtures;

use Zvizvi\FilamentColumnFilters\Concerns\HasColumnFilters;

/**
 * The same table, opted into registration from the component's own boot —
 * what a page needs when something builds its table with no Livewire request
 * behind it.
 */
class HeadlessDonorsTable extends DonorsTable
{
    use HasColumnFilters;
}
