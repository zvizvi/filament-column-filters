<?php

namespace Zvizvi\FilamentColumnTools\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class Donor extends Model
{
    protected $guarded = [];

    public function getDisplayNameAttribute(): string
    {
        return 'Donor: ' . $this->name;
    }
}
