<?php

namespace Zvizvi\FilamentColumnFilters\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donor extends Model
{
    protected $guarded = [];

    public function getDisplayNameAttribute(): string
    {
        return 'Donor: ' . $this->name;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
