<?php

namespace Zvizvi\FilamentColumnFilters\Concerns;

use Zvizvi\FilamentColumnFilters\FilamentColumnFilters;

/**
 * Registers the column filters from the component's own boot, for pages whose
 * table is built OUTSIDE a Livewire request.
 *
 * The plugin normally needs nothing from you: it hooks Livewire's mount,
 * hydrate, call and render events and processes the table on each. That covers
 * every path Filament itself takes. It does not cover code that builds the
 * table headlessly — instantiating the page class and calling getTable() with
 * no Livewire request behind it. None of those events fire there, so the
 * generated filters never get pushed onto the table, and applying one fails
 * with "The filter [cf_x] does not exist."
 *
 * A `booted<Trait>` hook runs on both paths — Livewire fires it on a real
 * request, a headless driver replays it — and processComponent() is
 * idempotent, so the work is free on the page and load-bearing off it.
 *
 * The trait has to be applied to the PAGE CLASS, not to a parent: Livewire
 * resolves these hooks in class_uses_recursive() order, and it is being last
 * that guarantees InteractsWithTable already built $this->table.
 *
 *     class ListDonors extends ListRecords
 *     {
 *         use HasColumnFilters;
 *     }
 */
trait HasColumnFilters
{
    public function bootedHasColumnFilters(): void
    {
        // decorate: true so the header decoration happens here too. A headless
        // driver that hashes the table schema to detect drift would otherwise
        // see the page's decorated labels and its own undecorated ones as two
        // different schemas.
        FilamentColumnFilters::processComponent($this, decorate: true);
    }
}
