<?php

namespace Zvizvi\FilamentColumnFilters;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\HtmlString;

/**
 * The label a decorated column header carries: the filter trigger and popup
 * as HTML for the table header, and the column's own label, as plain text, for
 * everything else that reads a column's label - an export's heading row, a
 * column manager, an accessible name. Without it they all read the popup's
 * markup (its options, its placeholders) as the column's name.
 */
class ColumnFilterHeaderLabel extends HtmlString implements HasLabel
{
    public function __construct(string $html, protected string $plainLabel)
    {
        parent::__construct($html);
    }

    public function getLabel(): string
    {
        return $this->plainLabel;
    }
}
