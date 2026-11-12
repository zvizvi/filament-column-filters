<?php

namespace Zvizvi\FilamentColumnFilters\Tests\Fixtures;

use Filament\Support\Contracts\HasLabel;

enum DonorKind: string implements HasLabel
{
    case Private = 'private';
    case Company = 'company';

    public function getLabel(): string
    {
        return match ($this) {
            self::Private => 'Private person',
            self::Company => 'Company',
        };
    }
}
